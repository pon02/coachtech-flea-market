<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id',
        'payment_id',
        'price',
        'status',
        'shipping_postal_code',
        'shipping_address',
    ];

    /**
     * 注文とユーザーの多対1リレーション（購入者）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 注文と商品の多対1リレーション
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * 注文と支払い方法の多対1リレーション
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
