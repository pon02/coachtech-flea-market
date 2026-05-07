<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Rating;
use App\Services\TradeChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    private TradeChatService $tradeChatService;

    public function __construct(TradeChatService $tradeChatService)
    {
        $this->tradeChatService = $tradeChatService;
    }

    public function show(Request $request, $orderId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        $viewerId = (int) Auth::id();
        $data = $this->tradeChatService->buildShowData($request, $order, $viewerId);

        $chat = $data['chat'];
        $messages = $data['messages'];
        $sidebarOrders = $data['sidebarOrders'];
        $latestMessageId = $data['latestMessageId'];
        $partnerUser = $data['partnerUser'];
        $shouldShowRatingModal = $data['shouldShowRatingModal'];
        $buyerHasRated = $data['buyerHasRated'];

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

        $this->abortIfBuyerHasRated($order);

        $chat = $this->tradeChatService->getOrCreateChat($order);
        $this->tradeChatService->ensureParticipants($chat, $order);

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

        $this->tradeChatService->touchLastReadAt($chat->id, Auth::id());

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

        $chat = Chat::where('order_id', $order->id)->firstOrFail();

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chat->id)
            ->firstOrFail();

        $this->authorize('update', $message);

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

        $chat = Chat::where('order_id', $order->id)->firstOrFail();

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chat->id)
            ->firstOrFail();

        $this->authorize('delete', $message);

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

    private function abortIfBuyerHasRated(Order $order)
    {
        if ((int) Auth::id() !== (int) $order->user_id) {
            return;
        }

        $buyerRated = Rating::where('order_id', $order->id)
            ->where('rater_id', $order->user_id)
            ->exists();

        if ($buyerRated) {
            abort(403);
        }
    }

}
