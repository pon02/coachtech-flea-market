<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;

class TradeChatService
{
    public function getOrCreateChat(Order $order): Chat
    {
        return Chat::firstOrCreate([
            'order_id' => $order->id,
        ]);
    }

    public function ensureParticipants(Chat $chat, Order $order): void
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

    public function touchLastReadAt(int $chatId, int $userId): void
    {
        ChatParticipant::where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    /**
     * @return array{
     *   chat: Chat,
     *   messages: \Illuminate\Support\Collection<int, ChatMessage>,
        *   sidebarOrders: \Illuminate\Support\Collection<int, Order>,
     *   latestMessageId: int|null,
     *   partnerUser: \App\Models\User,
     *   shouldShowRatingModal: bool,
     *   buyerHasRated: bool
     * }
     */
    public function buildShowData(Request $request, Order $order, int $viewerId): array
    {
        $order->loadMissing(['user', 'item.user']);

        $chat = $this->getOrCreateChat($order);
        $this->ensureParticipants($chat, $order);
        $this->touchLastReadAt($chat->id, $viewerId);

        $messages = ChatMessage::with('user')
            ->where('chat_id', $chat->id)
            ->orderBy('created_at')
            ->get();

        $latestMessageId = ChatMessage::where('chat_id', $chat->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        $partnerUser = $viewerId === (int) $order->user_id ? $order->item->user : $order->user;

        $buyerId = (int) $order->user_id;
        $buyerRated = Rating::where('order_id', $order->id)
            ->where('rater_id', $buyerId)
            ->exists();
        $buyerHasRated = ($viewerId === $buyerId) && $buyerRated;

        $shouldShowRatingModal = false;
        if ($order->completed_requested_at) {
            $alreadyRated = Rating::where('order_id', $order->id)
                ->where('rater_id', $viewerId)
                ->exists();

            if (!$alreadyRated) {
                $shouldShowRatingModal = true;
            }
        }

        if (session()->has('force_show_rating_modal')) {
            $shouldShowRatingModal = true;
        }

        // - 購入者: orders.user_id が自分
        // - 出品者: orders.item.user_id が自分
        $sidebarOrders = Order::with(['item', 'chat'])
            ->where('status', 'pending')
            ->whereHas('chat')
            ->where(function ($query) use ($viewerId) {
                $query->where('user_id', $viewerId)
                    ->orWhereHas('item', function ($q) use ($viewerId) {
                        $q->where('user_id', $viewerId);
                    });
            })
            ->addSelect([
                'last_message_at' => ChatMessage::query()
                    ->selectRaw('MAX(chat_messages.created_at)')
                    ->join('chats', 'chats.id', '=', 'chat_messages.chat_id')
                    ->whereColumn('chats.order_id', 'orders.id'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        return [
            'chat' => $chat,
            'messages' => $messages,
            'sidebarOrders' => $sidebarOrders,
            'latestMessageId' => $latestMessageId,
            'partnerUser' => $partnerUser,
            'shouldShowRatingModal' => $shouldShowRatingModal,
            'buyerHasRated' => $buyerHasRated,
        ];
    }
}
