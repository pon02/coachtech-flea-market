<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Order;
use App\Http\Requests\ProfileRequest;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$request->has('page')) {
            return redirect()->route('mypage', ['page' => 'sell']);
        }

        $page = $request->get('page', 'sell');

        $listedItems = Item::where('user_id', $user->id)
            ->with(['categories', 'condition'])
            ->orderBy('created_at', 'desc')
            ->get();

        $listedItems->each(function ($item) {
            $item->is_sold = Order::where('item_id', $item->id)->exists();
        });

        $purchasedItems = Item::join('orders', 'items.id', '=', 'orders.item_id')
            ->where('orders.user_id', $user->id)
            ->with(['categories', 'condition'])
            ->select('items.*', 'orders.created_at as purchase_date', 'orders.price as purchase_price')
            ->orderBy('orders.created_at', 'desc')
            ->get();

        return view('mypage.mypage', compact('user', 'listedItems', 'purchasedItems', 'page'));
    }

    public function showProfile()
    {
        $previousUrl = url()->previous();
        if ($previousUrl && str_contains($previousUrl, '/mypage') && !str_contains($previousUrl, '/mypage/profile')) {
            session(['profile_return_to_mypage' => true]);
        } else {
            session(['profile_return_to_mypage' => false]);
        }

        return view('mypage.profile');
    }

    public function updateProfile(ProfileRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $imagePath;
        }

        $user->update($validated);

        $returnToMypage = session('profile_return_to_mypage', false);
        $referer = request()->headers->get('referer');
        $previousUrl = url()->previous();

        if ($returnToMypage || ($previousUrl && str_contains($previousUrl, '/mypage'))) {
            session()->forget('profile_return_to_mypage');
            return redirect()->route('mypage')->with('success', 'プロフィールを更新しました。');
        } else {
            session()->forget('profile_return_to_mypage');
            return redirect()->route('home')->with('success', 'プロフィールを更新しました。');
        }
    }

    public function deleteProfileImage()
    {
        $user = Auth::user();

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
            $user->update(['profile_image' => null]);
        }

        return redirect()->route('mypage.profile')->with('success', 'プロフィール画像を削除しました。');
    }
}
