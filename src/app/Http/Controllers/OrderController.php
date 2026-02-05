<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Http\Requests\PurchaseRequest;

class OrderController extends Controller
{
    public function show($id)
    {
        $item = Item::with(['categories', 'condition', 'user'])->findOrFail($id);
        $user = Auth::user();

        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $id)
                ->with('error', '自分の商品は購入できません。');
        }

        $payments = Payment::all();

        $shippingData = session('shipping_address', [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
        ]);

        return view('order.order', compact('item', 'user', 'payments', 'shippingData'));
    }

    public function store(PurchaseRequest $request, $id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        if ($item->is_sold) {
            return redirect()->route('items.show', $id)
                ->with('error', 'この商品は既に売り切れています。');
        }

        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $id)
                ->with('error', '自分の商品は購入できません。');
        }

        if ($request->payment_id == 2) {
            return redirect()->route('payment.checkout', $id);
        }

        $shippingData = session('shipping_address', [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_id' => $request->payment_id,
            'price' => $item->price,
            'status' => 'pending',
            'shipping_postal_code' => $shippingData['postal_code'],
            'shipping_address' => $shippingData['address'] . ($shippingData['building'] ? ' ' . $shippingData['building'] : ''),
        ]);

        $chat = Chat::firstOrCreate(['order_id' => $order->id]);
        ChatParticipant::firstOrCreate(
            ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_BUYER],
            ['user_id' => $order->user_id]
        );
        ChatParticipant::firstOrCreate(
            ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_SELLER],
            ['user_id' => $item->user_id]
        );

        $item->update(['is_sold' => true]);

        session()->forget('shipping_address');

        return redirect()->route('trade.chat.show', $order->id)
            ->with('success', '商品を購入しました。');
    }

    public function completeStripePayment(Request $request, $id)
    {
        $sessionId = $request->get('session_id');
        $item = Item::findOrFail($id);
        $user = Auth::user();

        if ($item->is_sold) {
            return redirect()->route('home')
                ->with('error', 'この商品は既に売り切れています。');
        }

        if ($sessionId) {
            $shippingData = session('shipping_address', [
                'postal_code' => $user->postal_code,
                'address' => $user->address,
                'building' => $user->building,
            ]);

            $order = Order::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'payment_id' => 2,
                'price' => $item->price,
                'status' => 'pending',
                'shipping_postal_code' => $shippingData['postal_code'],
                'shipping_address' => $shippingData['address'] . ($shippingData['building'] ? ' ' . $shippingData['building'] : ''),
            ]);

            $chat = Chat::firstOrCreate(['order_id' => $order->id]);
            ChatParticipant::firstOrCreate(
                ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_BUYER],
                ['user_id' => $order->user_id]
            );
            ChatParticipant::firstOrCreate(
                ['chat_id' => $chat->id, 'role' => ChatParticipant::ROLE_SELLER],
                ['user_id' => $item->user_id]
            );

            $item->update(['is_sold' => true]);
            session()->forget('shipping_address');

            return redirect()->route('trade.chat.show', $order->id)
                ->with('success', 'カード決済が完了しました。');
        }

        return redirect()->route('home')
            ->with('error', '決済情報が見つかりません。');
    }
}
