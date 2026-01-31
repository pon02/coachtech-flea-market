<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\Item;
use App\Http\Requests\CommentRequest;

class CommentController extends Controller
{
    public function store(CommentRequest $request, $productId)
    {
        Item::findOrFail($productId);

        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $productId,
            'text' => $request->text,
        ]);

        return redirect()->route('items.show', $productId)
            ->with('success', 'コメントを投稿しました。');
    }
}
