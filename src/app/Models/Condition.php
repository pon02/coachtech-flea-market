<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * 状態と商品の1対多リレーション
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
