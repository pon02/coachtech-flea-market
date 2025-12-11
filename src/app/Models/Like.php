<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id',
    ];

    /**
     * いいねとユーザーの多対1リレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * いいねと商品の多対1リレーション
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
