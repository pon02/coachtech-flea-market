@extends('layouts.logo')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/trade/chat-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/trade/chat-messages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/trade/chat-modals.css') }}">
@endsection

@section('content')
@php
    $isBuyer = auth()->id() === $order->user_id;
@endphp
<div class="trade-chat" data-order-id="{{ $order->id }}" data-user-id="{{ auth()->id() }}">
    <aside class="trade-chat__sidebar" aria-label="取引サイドバー">
        <h2 class="trade-chat__sidebar-title">その他の取引</h2>
        @if(!empty($sidebarOrders) && $sidebarOrders->isNotEmpty())
            <ul class="trade-chat__sidebar-list">
                @foreach($sidebarOrders as $sideOrder)
                    <li class="trade-chat__sidebar-item">
                        <a href="{{ route('trade.chat.show', $sideOrder->id) }}">
                            {{ $sideOrder->item->name ?? '商品' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </aside>

    <main class="trade-chat__main">
        <div class="trade-chat__header">
            <div class="trade-chat__partner">
                @if(!empty($partnerUser) && $partnerUser->profile_image)
                    <img class="trade-chat__partner-avatar" src="{{ asset('storage/' . $partnerUser->profile_image) }}" alt="{{ $partnerUser->name }}">
                @else
                    <div class="trade-chat__partner-avatar-placeholder">
                        {{ !empty($partnerUser) ? mb_substr($partnerUser->name, 0, 1) : '？' }}
                    </div>
                @endif

                <h1 class="trade-chat__title">
                    {{ !empty($partnerUser) ? $partnerUser->name : 'ユーザー' }}さんとの取引画面
                </h1>
            </div>

            @if(auth()->id() === $order->user_id)
                <form method="POST" action="{{ route('trade.complete.request', $order->id) }}">
                    @csrf
                    <button type="submit" class="trade-chat__complete" {{ !empty($buyerHasRated) ? 'disabled' : '' }}>
                        {{ !empty($buyerHasRated) ? '相手の評価待ち' : '取引を完了する' }}
                    </button>
                </form>
            @endif
        </div>

        <div class="trade-chat__divider"></div>

        <div class="trade-chat__product">
            @if(!empty($order->item) && $order->item->item_image)
                <img class="trade-chat__product-image" src="{{ asset('storage/' . $order->item->item_image) }}" alt="{{ $order->item->name }}">
            @else
                <div class="trade-chat__product-image"></div>
            @endif

            <div class="trade-chat__product-info">
                <p class="trade-chat__product-name">{{ $order->item->name ?? '' }}</p>
                <p class="trade-chat__product-price">¥{{ number_format($order->price) }}</p>
            </div>
        </div>

        <div class="trade-chat__divider"></div>

        <div class="trade-chat__messages" id="tradeChatMessages">
            @forelse($messages as $msg)
                @php
                    $isMe = auth()->id() === $msg->user_id;
                    $isEditing = (string) old('editing_message_id') === (string) $msg->id;
                @endphp

                <div class="trade-chat__message {{ $isMe ? 'trade-chat__message--me' : '' }} {{ $isEditing ? 'is-editing' : '' }}">
                    <div class="trade-chat__message-body">
                        <div class="trade-chat__message-meta">
                            @if(!$isMe)
                                @if($msg->user && $msg->user->profile_image)
                                    <img class="trade-chat__message-avatar" src="{{ asset('storage/' . $msg->user->profile_image) }}" alt="{{ $msg->user->name }}">
                                @else
                                    <div class="trade-chat__message-avatar-placeholder">
                                        {{ $msg->user ? mb_substr($msg->user->name, 0, 1) : '？' }}
                                    </div>
                                @endif
                                <span class="trade-chat__message-name">{{ $msg->user->name ?? 'ユーザー' }}</span>
                            @else
                                <span class="trade-chat__message-name">{{ $msg->user->name ?? 'ユーザー' }}</span>
                                @if($msg->user && $msg->user->profile_image)
                                    <img class="trade-chat__message-avatar" src="{{ asset('storage/' . $msg->user->profile_image) }}" alt="{{ $msg->user->name }}">
                                @else
                                    <div class="trade-chat__message-avatar-placeholder">
                                        {{ $msg->user ? mb_substr($msg->user->name, 0, 1) : '？' }}
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="trade-chat__bubble">
                            <div class="trade-chat__bubble-view">
                                <div class="trade-chat__bubble-text">{{ $msg->message }}</div>
                                @if(!empty($msg->image_path))
                                    @php
                                        $imageUrl = asset('storage/' . $msg->image_path);
                                    @endphp
                                    <div class="trade-chat__bubble-image">
                                        <button
                                            type="button"
                                            class="trade-chat__thumb-button js-chat-image-open"
                                            data-src="{{ $imageUrl }}"
                                            aria-label="添付画像を拡大表示"
                                        >
                                            <img class="trade-chat__thumb" src="{{ $imageUrl }}" alt="添付画像" loading="lazy">
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if($isMe && empty($buyerHasRated) && !empty($latestMessageId) && $latestMessageId === $msg->id)
                                <div class="trade-chat__bubble-edit">
                                    <form class="trade-chat__inline-edit-form" method="POST" action="{{ route('trade.messages.update', [$order->id, $msg->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="editing_message_id" value="{{ $msg->id }}">

                                        <textarea
                                            name="message"
                                            class="trade-chat__inline-textarea"
                                            rows="3"
                                            data-original="{{ $msg->message }}"
                                        >{{ $isEditing ? old('message', $msg->message) : $msg->message }}</textarea>

                                        @if($isEditing)
                                            @error('message')
                                                <div class="trade-chat__error">{{ $message }}</div>
                                            @enderror
                                        @endif

                                        <div class="trade-chat__inline-actions">
                                            <button type="submit" class="trade-chat__inline-save">保存</button>
                                            <button type="button" class="trade-chat__inline-cancel js-chat-edit-cancel">キャンセル</button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>

                        @if($isMe && empty($buyerHasRated) && !empty($latestMessageId) && $latestMessageId === $msg->id)
                            <div class="trade-chat__message-actions">
                                <button type="button" class="trade-chat__link-button js-chat-edit-open">編集</button>

                                <form method="POST" action="{{ route('trade.messages.destroy', [$order->id, $msg->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="trade-chat__link-button">削除</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p>メッセージはまだありません。</p>
            @endforelse
        </div>

        @if($isBuyer && !empty($buyerHasRated))
            <div class="trade-chat__send-disabled">相手の評価待ちのため、メッセージ送信はできません。</div>
        @else
            <form method="POST" action="{{ route('trade.messages.store', $order->id) }}" enctype="multipart/form-data" class="trade-chat__send">
                @csrf

                <input class="trade-chat__input" type="text" name="message" placeholder="取引メッセージを記入してください" value="{{ old('message') }}">

                <input id="tradeChatImage" class="trade-chat__file" type="file" name="image">
                <label for="tradeChatImage" class="trade-chat__file-button">画像を追加</label>

                <div class="trade-chat__attachment" id="tradeChatAttachment" aria-live="polite">
                    <img class="trade-chat__attachment-thumb" id="tradeChatAttachmentThumb" alt="添付画像プレビュー">
                    <span class="trade-chat__attachment-name" id="tradeChatAttachmentName"></span>
                    <button type="button" class="trade-chat__attachment-remove" id="tradeChatAttachmentRemove">削除</button>
                </div>

                <button type="submit" class="trade-chat__send-button" aria-label="送信">
                    <img src="{{ asset('img/send.jpg') }}" alt="送信">
                </button>
            </form>
        @endif

        @error('message')
            @if(!old('editing_message_id'))
                <div class="trade-chat__error">{{ $message }}</div>
            @endif
        @enderror
        @error('image')
            <div class="trade-chat__error">{{ $message }}</div>
        @enderror
    </main>
</div>

<div class="trade-chat__img-modal" id="chatImageModal" aria-hidden="true">
    <div class="trade-chat__img-modal-backdrop js-chat-image-close"></div>
    <div class="trade-chat__img-modal-card" role="dialog" aria-modal="true" aria-label="画像プレビュー">
        <button type="button" class="trade-chat__img-modal-close js-chat-image-close" aria-label="閉じる">×</button>
        <img class="trade-chat__img-modal-image" id="chatImageModalImg" alt="添付画像の拡大">
        <div class="trade-chat__img-modal-actions">
            <a class="trade-chat__img-modal-open" id="chatImageModalOpen" href="#" target="_blank" rel="noopener">別タブで開く</a>
        </div>
    </div>
</div>

<div class="trade-chat__modal {{ $shouldShowRatingModal ? 'is-open' : '' }}" id="ratingModal">
    <div class="trade-chat__modal-card">
        <h3 class="trade-chat__modal-title">取引が完了しました。</h3>
        <div class="trade-chat__modal-divider" aria-hidden="true"></div>
        <p class="trade-chat__modal-text">今回の取引相手はどうでしたか？</p>

        <form method="POST" action="{{ route('trade.ratings.store', $order->id) }}" id="ratingForm">
            @csrf
            <input type="hidden" name="stars" id="ratingStars" value="{{ old('stars') }}">

            <div class="trade-chat__stars" id="ratingStarsUi" data-initial="{{ old('stars', 0) }}">
                @for($i = 1; $i <= 5; $i++)
                    <span class="trade-chat__star" data-value="{{ $i }}">★</span>
                @endfor
            </div>

            @error('stars')
                <div class="trade-chat__error">{{ $message }}</div>
            @enderror

            <div class="trade-chat__modal-divider" aria-hidden="true"></div>
            <div class="trade-chat__modal-actions">
                <button type="submit" class="trade-chat__modal-submit">送信する</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/trade/chat.js') }}" defer></script>

@endsection
