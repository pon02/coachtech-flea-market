<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function show(Request $request, $orderId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        $chat = Chat::firstOrCreate([
            'order_id' => $order->id,
        ]);

        $this->ensureParticipants($chat, $order);
        $this->touchLastReadAt($chat->id, Auth::id());

        $messages = ChatMessage::with('user')
            ->where('chat_id', $chat->id)
            ->orderBy('created_at')
            ->get();

        $latestMessageId = ChatMessage::where('chat_id', $chat->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        $partnerUser = Auth::id() === $order->user_id ? $order->item->user : $order->user;

        $buyerId = (int) $order->user_id;
        $viewerId = (int) Auth::id();
        $buyerRated = Rating::where('order_id', $order->id)
            ->where('rater_id', $buyerId)
            ->exists();
        $buyerHasRated = ($viewerId === $buyerId) && $buyerRated;

        $shouldShowRatingModal = false;
        if ($order->completed_requested_at) {
            $alreadyRated = Rating::where('order_id', $order->id)
                ->where('rater_id', Auth::id())
                ->exists();

            if (!$alreadyRated) {
                $shouldShowRatingModal = true;
            }
        }

        if (session()->has('force_show_rating_modal')) {
            $shouldShowRatingModal = true;
        }

        $sidebarOrders = null;
        if (Auth::id() === $order->item->user_id) {
            $sidebarOrders = Order::with(['item', 'chat'])
                ->where('status', 'pending')
                ->whereHas('item', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->whereHas('chat')
                ->addSelect([
                    'last_message_at' => ChatMessage::query()
                        ->selectRaw('MAX(chat_messages.created_at)')
                        ->join('chats', 'chats.id', '=', 'chat_messages.chat_id')
                        ->whereColumn('chats.order_id', 'orders.id'),
                ])
                ->orderByDesc('last_message_at')
                ->orderByDesc('created_at')
                ->get();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'order' => $order,
                'chat' => $chat,
                'messages' => $messages,
                'sidebar_orders' => $sidebarOrders,
                'latest_message_id' => $latestMessageId,
                'partner_user' => $partnerUser,
                'should_show_rating_modal' => $shouldShowRatingModal,
            ]);
        }

        return view('trade.chat', compact('order', 'chat', 'messages', 'sidebarOrders', 'latestMessageId', 'partnerUser', 'shouldShowRatingModal', 'buyerHasRated'));
    }

    public function storeMessage(ChatMessageRequest $request, $orderId)
    {
        $order = Order::with(['item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        if ((int) Auth::id() === (int) $order->user_id) {
            $buyerRated = Rating::where('order_id', $order->id)
                ->where('rater_id', $order->user_id)
                ->exists();
            if ($buyerRated) {
                abort(403);
            }
        }

        $chat = Chat::firstOrCreate([
            'order_id' => $order->id,
        ]);

        $this->ensureParticipants($chat, $order);

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat_messages', 'public');
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'image_path' => $imagePath,
        ]);

        $this->touchLastReadAt($chat->id, Auth::id());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message->load('user'),
            ], 201);
        }

        return redirect()->route('trade.chat.show', $order->id);
    }

    public function requestComplete($orderId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessBuyer($order);

        $alreadyRated = Rating::where('order_id', $order->id)
            ->where('rater_id', Auth::id())
            ->exists();
        if ($alreadyRated) {
            abort(403);
        }

        if (!$order->completed_requested_at) {
            $order->completed_requested_at = now();
            $order->save();
        }

        return redirect()->route('trade.chat.show', $order->id)
            ->with('force_show_rating_modal', true);
    }

    public function updateMessage(Request $request, $orderId, $messageId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        if ((int) Auth::id() === (int) $order->user_id) {
            $buyerRated = Rating::where('order_id', $order->id)
                ->where('rater_id', $order->user_id)
                ->exists();
            if ($buyerRated) {
                abort(403);
            }
        }

        $chat = Chat::where('order_id', $order->id)->firstOrFail();

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chat->id)
            ->firstOrFail();

        if ((int) $message->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $latestId = ChatMessage::where('chat_id', $chat->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        if ((int) $latestId !== (int) $message->id) {
            abort(403);
        }

        $validated = $request->validate(
            ['message' => 'required|string|max:400'],
            [
                'message.required' => '本文を入力してください',
                'message.max' => '本文は400文字以内で入力してください',
            ]
        );

        $message->message = $validated['message'];
        $message->save();

        return redirect()->route('trade.chat.show', $order->id);
    }

    public function destroyMessage($orderId, $messageId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        if ((int) Auth::id() === (int) $order->user_id) {
            $buyerRated = Rating::where('order_id', $order->id)
                ->where('rater_id', $order->user_id)
                ->exists();
            if ($buyerRated) {
                abort(403);
            }
        }

        $chat = Chat::where('order_id', $order->id)->firstOrFail();

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chat->id)
            ->firstOrFail();

        if ((int) $message->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $latestId = ChatMessage::where('chat_id', $chat->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        if ((int) $latestId !== (int) $message->id) {
            abort(403);
        }

        if (!empty($message->image_path)) {
            Storage::disk('public')->delete($message->image_path);
        }

        $message->delete();

        return redirect()->route('trade.chat.show', $order->id);
    }

    private function abortUnlessParticipant(Order $order)
    {
        $userId = Auth::id();
        $buyerId = $order->user_id;
        $sellerId = $order->item->user_id;

        if ($userId !== $buyerId && $userId !== $sellerId) {
            abort(403);
        }
    }

    private function abortUnlessBuyer(Order $order)
    {
        if (Auth::id() !== $order->user_id) {
            abort(403);
        }
    }

    private function ensureParticipants(Chat $chat, Order $order)
    {
        $buyerId = $order->user_id;
        $sellerId = $order->item->user_id;

        ChatParticipant::firstOrCreate(
            ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_BUYER],
            ['user_id' => $buyerId]
        );

        ChatParticipant::firstOrCreate(
            ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_SELLER],
            ['user_id' => $sellerId]
        );
    }

    private function touchLastReadAt($chatId, $userId)
    {
        ChatParticipant::where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}
