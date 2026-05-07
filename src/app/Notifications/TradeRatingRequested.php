<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TradeRatingRequested extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $itemName,
        public string $buyerName,
        public int $stars,
    ) {
    }

    /**
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * @param mixed $notifiable
     */
    public function toMail($notifiable)
    {
        $starsText = str_repeat('★', max(0, min(5, $this->stars)));

        return (new MailMessage)
            ->subject('【取引評価のお願い】' . $this->itemName)
            ->greeting($notifiable->name . 'さん')
            ->line('購入者（' . $this->buyerName . 'さん）から評価が送信されました。')
            ->line('商品名：' . $this->itemName)
            ->line('購入者の評価：' . $starsText)
            ->line('取引画面から、購入者（' . $this->buyerName . 'さん)に対する評価の送信をお願いします。');
    }
}
