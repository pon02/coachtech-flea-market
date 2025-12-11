<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ExhibitionRequest;
use App\Models\Item;
use App\Models\Category;
use App\Models\Condition;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /** 出品画面の表示 */
    public function create()
    {
        $categories = Category::all();
        $conditions = Condition::all();

        return view('listing', compact('categories', 'conditions'));
    }

    /** 出品処理 */
    public function store(ExhibitionRequest $request)
    {
        \Log::info('=== 商品登録処理開始 ===');
        \Log::info('リクエストデータ', $request->all());
        \Log::info('ファイルデータ', $request->allFiles());
        \Log::info('hasFile check: ' . ($request->hasFile('item_image') ? 'true' : 'false'));

        $imagePath = null;
        if ($request->hasFile('item_image')) {
            \Log::info('ファイルが検出されました');
            $file = $request->file('item_image');
            \Log::info('ファイル情報', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'valid' => $file->isValid()
            ]);
            $imagePath = $file->store('items', 'public');
            \Log::info('画像保存完了: ' . $imagePath);
        } else {
            \Log::error('ファイルが検出されませんでした');
        }

        $item = Item::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'brand_name' => $request->brand_name,
            'condition_id' => $request->condition_id,
            'description' => $request->description,
            'price' => $request->price,
            'item_image' => $imagePath,
        ]);

        $item->categories()->attach($request->categories);

        return redirect()->route('mypage', ['page' => 'sell'])->with('success', '商品を出品しました！');
    }

    /** 商品一覧画面の表示（トップページ） */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'recommended');
        $search = $request->get('keyword');

        if ($tab === 'mylist' && Auth::check()) {
            $query = Auth::user()->likedItems()
                ->with(['categories', 'condition']);

            if ($search) {
                $query->where('items.name', 'LIKE', '%' . $search . '%');
            }

            $items = $query->orderBy('likes.created_at', 'desc')->get();
        } elseif ($tab === 'mylist' && !Auth::check()) {
            $items = collect();
        } else {
            $query = Item::with(['categories', 'condition']);

            if ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            }

            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }

            $items = $query->orderBy('id', 'desc')->get();
        }

        return view('main', compact('items', 'tab', 'search'));
    }

    /** 商品詳細画面の表示 */
    public function show($id)
    {
        $item = Item::with(['categories', 'condition', 'user', 'likes', 'comments'])->findOrFail($id);

        $likesCount = $item->likes->count();
        $commentsCount = $item->comments->count();
        $comments = $item->comments;

        $isLiked = false;
        if (Auth::check()) {
            $isLiked = $item->isLikedBy(Auth::user());
        }

        return view('detail', compact('item', 'likesCount', 'commentsCount', 'comments', 'isLiked'));
    }
}
