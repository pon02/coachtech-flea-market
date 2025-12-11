<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id', 
        'text'
    ];

    /**
     * コメントとユーザーの多対一リレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * コメントと商品の多対一リレーション
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
