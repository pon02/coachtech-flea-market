<?php

namespace App\Http\Controllers;

use App\Http\Requests\RatingRequest;
use App\Notifications\TradeRatingRequested;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(RatingRequest $request, $orderId)
    {
        $order = Order::with(['user', 'item.user'])->findOrFail($orderId);
        $this->abortUnlessParticipant($order);

        if (!$order->completed_requested_at) {
            abort(403);
        }

        $alreadyRated = Rating::where('order_id', $order->id)
            ->where('rater_id', Auth::id())
            ->exists();
        if ($alreadyRated) {
            abort(403);
        }

        $validated = $request->validated();

        $buyerId = $order->user_id;
        $sellerId = $order->item->user_id;
        $raterId = Auth::id();
        $rateeId = $raterId === $buyerId ? $sellerId : $buyerId;

        Rating::updateOrCreate(
            ['order_id' => $order->id, 'rater_id' => $raterId],
            ['ratee_id' => $rateeId, 'stars' => $validated['stars']]
        );

        if ($raterId === $buyerId) {
            $seller = $order->item->user;
            $buyer = $order->user;
            if ($seller && !empty($seller->email) && $buyer) {
                $seller->notify(new TradeRatingRequested(
                    orderId: (int) $order->id,
                    itemName: (string) ($order->item->name ?? '商品'),
                    buyerName: (string) $buyer->name,
                    stars: (int) $validated['stars'],
                ));
            }
        }

        $this->completeOrderIfBothRated($order);

        return redirect('/')
            ->with('success', '評価を送信しました。');
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

    private function completeOrderIfBothRated(Order $order)
    {
        $buyerId = $order->user_id;
        $sellerId = $order->item->user_id;

        $count = Rating::where('order_id', $order->id)
            ->whereIn('rater_id', [$buyerId, $sellerId])
            ->count();

        if ($count < 2) {
            return;
        }

        if ($order->status !== 'completed') {
            $order->status = 'completed';
        }

        if (!$order->completed_at) {
            $order->completed_at = now();
        }

        $order->save();
    }
}
