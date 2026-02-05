<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'postal_code',
        'address',
        'building',
        'profile_image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ユーザーと商品の1対多リレーション（出品者）
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * ユーザーと注文の1対多リレーション（購入者）
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * ユーザーがいいねした商品（多対多リレーション）
     */
    public function likedItems()
    {
        return $this->belongsToMany(Item::class, 'likes', 'user_id', 'item_id')->withTimestamps();
    }

    /**
     * ユーザーのいいね（1対多リレーション）
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * ユーザーのコメント（1対多リレーション）
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * ユーザーとチャット参加者の1対多リレーション
     */
    public function chatParticipants()
    {
        return $this->hasMany(ChatParticipant::class);
    }

    /**
     * ユーザーと評価の1対多リレーション（評価を受ける側）
     */
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'ratee_id');
    }

    /**
     * ユーザーと評価の1対多リレーション（評価する側）
     */
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    /**
     * ユーザーとメッセージの1対多リレーション
     */
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}