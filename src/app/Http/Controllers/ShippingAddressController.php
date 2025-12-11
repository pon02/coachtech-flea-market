<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Http\Requests\AddressRequest;

class ShippingAddressController extends Controller
{
    public function edit($id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $id)
                ->with('error', '自分の商品は購入できません。');
        }

        $shippingData = session('shipping_address', [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
        ]);

        return view('order.shippingAddress', compact('item', 'user', 'shippingData'));
    }

    public function update(AddressRequest $request, $id)
    {
        $item = Item::findOrFail($id);

        session(['shipping_address' => [
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'building' => $request->building,
        ]]);

        return redirect()->route('orders.show', $id)
            ->with('success', '配送先住所を変更しました。');
    }
}