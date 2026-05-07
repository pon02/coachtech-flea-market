<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\Rating;
use App\Models\User;

class ChatMessagePolicy
{
    public function update(User $user, ChatMessage $message): bool
    {
        return $this->canEditOrDelete($user, $message);
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        return $this->canEditOrDelete($user, $message);
    }

    private function canEditOrDelete(User $user, ChatMessage $message): bool
    {
        if ((int) $message->user_id !== (int) $user->id) {
            return false;
        }

        $message->loadMissing('chat.order.item');
        $order = $message->chat?->order;
        if (!$order || !$order->item) {
            return false;
        }

        $buyerId = (int) $order->user_id;
        $sellerId = (int) $order->item->user_id;
        $userId = (int) $user->id;

        if ($userId !== $buyerId && $userId !== $sellerId) {
            return false;
        }

        if ($userId === $buyerId) {
            $buyerRated = Rating::where('order_id', $order->id)
                ->where('rater_id', $buyerId)
                ->exists();

            if ($buyerRated) {
                return false;
            }
        }

        $latestId = ChatMessage::where('chat_id', $message->chat_id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        return (int) $latestId === (int) $message->id;
    }
}
