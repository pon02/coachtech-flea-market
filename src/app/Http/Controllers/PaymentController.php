<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends Controller
{
    public function createCheckoutSession(Request $request, $itemId)
    {
        $item = Item::findOrFail($itemId);
        $user = Auth::user();

        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $itemId)
                ->with('error', '自分の商品は購入できません');
        }

        if ($item->is_sold) {
            return redirect()->route('items.show', $itemId)
                ->with('error', 'この商品は既に販売済みです');
        }

        Stripe::setApiKey(config('stripe.secret_key'));

        try {
            $shippingData = session('shipping_address', [
                'postal_code' => $user->postal_code,
                'address' => $user->address,
                'building' => $user->building,
            ]);

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'jpy',
                            'product_data' => [
                                'name' => $item->name,
                                'description' => $item->description,
                                'images' => $item->item_image ? [asset('storage/' . $item->item_image)] : [],
                            ],
                            'unit_amount' => (int)$item->price,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('payment.success', ['item' => $itemId]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('orders.show', $itemId),
                'metadata' => [
                    'item_id' => $itemId,
                    'user_id' => $user->id,
                    'shipping_postal_code' => $shippingData['postal_code'],
                    'shipping_address' => $shippingData['address'] . ($shippingData['building'] ? ' ' . $shippingData['building'] : ''),
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            return redirect()->route('orders.show', $itemId)
                ->with('error', 'カード決済の処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    public function success(Request $request, $itemId)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('home')->with('error', '決済情報が見つかりません');
        }

        try {
            $item = Item::findOrFail($itemId);

            if ($item->is_sold) {
                return redirect()->route('home')->with('error', 'この商品は既に販売済みです');
            }

            Stripe::setApiKey(config('stripe.secret_key'));
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                return redirect()->route('orders.completeStripePayment', [
                    'item' => $itemId,
                    'session_id' => $sessionId
                ]);
            }

            return redirect()->route('orders.show', $itemId)->with('error', '決済が完了していません');

        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', '決済の確認中にエラーが発生しました');
        }
    }
}
