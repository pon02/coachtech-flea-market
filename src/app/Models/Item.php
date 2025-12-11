<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'user_id',
        'name',
        'brand_name',
        'condition_id',
        'description',
        'price',
        'item_image',
        'is_sold',
    ];

    /**
     * 商品とユーザーの1対多リレーション（出品者）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 商品とカテゴリの多対多リレーション
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'item_category', 'item_id', 'category_id');
    }

    /**
     * 商品と状態の1対多リレーション
     */
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    /**
     * 商品と注文の1対多リレーション
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'item_id');
    }

    /**
     * 商品をいいねしたユーザー（多対多リレーション）
     */
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes', 'item_id', 'user_id')->withTimestamps();
    }

    /**
     * 商品のいいね（1対多リレーション）
     */
    public function likes()
    {
        return $this->hasMany(Like::class, 'item_id');
    }

    /**
     * いいね数を取得
     */
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    /**
     * 指定ユーザーがこの商品をいいねしているかチェック
     */
    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * 商品のコメント（1対多リレーション）
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'item_id')->with('user')->latest();
    }
}
