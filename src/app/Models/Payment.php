<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * 支払い方法と注文の1対多リレーション
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
