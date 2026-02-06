<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Order;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Rating;
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

        $tradeOrders = Order::with(['item', 'item.user', 'user', 'chat'])
            ->where('status', 'pending')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('item', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
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

        // 未読数（相手のメッセージのみカウント）
        $tradeUnreadCounts = collect();
        $tradeUnreadTotal = 0;

        if ($tradeOrders->isNotEmpty()) {
            $orderIds = $tradeOrders->pluck('id');
            $userId = $user->id;

            $tradeUnreadCounts = DB::table('chat_messages')
                ->join('chats', 'chats.id', '=', 'chat_messages.chat_id')
                ->leftJoin('chat_participants as cp', function ($join) use ($userId) {
                    $join->on('cp.chat_id', '=', 'chat_messages.chat_id')
                        ->where('cp.user_id', '=', $userId);
                })
                ->whereIn('chats.order_id', $orderIds)
                ->where('chat_messages.user_id', '!=', $userId)
                ->where(function ($query) {
                    $query->whereNull('cp.last_read_at')
                        ->orWhereColumn('chat_messages.created_at', '>', 'cp.last_read_at');
                })
                ->groupBy('chats.order_id')
                ->selectRaw('chats.order_id as order_id, COUNT(*) as unread_count')
                ->pluck('unread_count', 'order_id');

            $tradeUnreadTotal = (int) $tradeUnreadCounts->sum();
        }

        $tradeOrders->each(function ($order) use ($tradeUnreadCounts) {
            $order->unread_count = (int) ($tradeUnreadCounts[$order->id] ?? 0);
        });

        $avgRating = Rating::where('ratee_id', $user->id)->avg('stars');
        $hasAnyRating = $avgRating !== null;
        $roundedAverageRating = $hasAnyRating ? (int) round($avgRating) : 0;

        return view('mypage.mypage', compact('user', 'listedItems', 'purchasedItems', 'tradeOrders', 'tradeUnreadTotal', 'roundedAverageRating', 'hasAnyRating', 'page'));
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

        // 初回プロフィール設定かどうかを判定
        $isFirstTimeSetup = session('first_time_profile_setup', false);
        $returnToMypage = session('profile_return_to_mypage', false);
        $referer = request()->headers->get('referer');
        $previousUrl = url()->previous();

        if ($isFirstTimeSetup) {
            session()->forget('first_time_profile_setup');
            session()->forget('profile_return_to_mypage');
            return redirect()->route('home')->with('success', 'プロフィール設定が完了しました');
        }

        if ($returnToMypage || ($previousUrl && str_contains($previousUrl, '/mypage'))) {
            session()->forget('profile_return_to_mypage');
            return redirect()->route('mypage')->with('success', 'プロフィールを更新しました');
        } else {
            session()->forget('profile_return_to_mypage');
            return redirect()->route('home')->with('success', 'プロフィールを更新しました');
        }
    }

    public function deleteProfileImage()
    {
        $user = Auth::user();

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
            $user->update(['profile_image' => null]);
        }

        return redirect()->route('mypage.profile')->with('success', 'プロフィール画像を削除しました');
    }
}
