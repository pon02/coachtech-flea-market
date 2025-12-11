<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Like;

class LikeController extends Controller
{
    public function toggleLike($productId)
    {
        $user = Auth::user();
        $item = Item::findOrFail($productId);

        if ($item->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分の商品にはいいねできません。'
            ], 403);
        }

        $existingLike = Like::where('user_id', $user->id)
                           ->where('item_id', $productId)
                           ->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'item_id' => $productId,
            ]);
            $liked = true;
        }

        $likesCount = Like::where('item_id', $productId)->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }
}
