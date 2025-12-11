@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection
@section('content')

<main class="detail-main">
    <div class="detail-container">
        <div class="detail-image">
            @if($item->item_image)
                <img src="{{ asset('storage/' . $item->item_image) }}" alt="{{ $item->name }}" class="product-main-image">
            @else
                <div class="no-image-placeholder">画像なし</div>
            @endif
        </div>

        <div class="detail-info">
            <h1 class="product-title">{{ $item->name }}</h1>
            @if($item->brand_name)
                <p class="product-brand">{{ $item->brand_name }}</p>
            @endif
            <p class="product-price">¥{{ number_format($item->price) }}<span>(税込)</span></p>
            <div class="product-actions">
                <div class="action-item">
                    @auth
                        <button type="button"
                                class="like-button {{ $isLiked ? 'liked' : '' }}"
                                data-product-id="{{ $item->id }}"
                                onclick="toggleLike({{ $item->id }})">
                            <img src="{{ asset('img/like.png') }}" alt="いいね" class="icon heart-icon">
                            <span class="count" id="likes-count">{{ $likesCount }}</span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="like-button">
                            <img src="{{ asset('img/like.png') }}" alt="いいね" class="icon heart-icon">
                            <span class="count">{{ $likesCount }}</span>
                        </a>
                    @endauth
                </div>
                <div class="action-item">
                    <img src="{{ asset('img/comment.png') }}" alt="コメント" class="icon comment-icon">
                    <span class="count">{{ $commentsCount }}</span>
                </div>
            </div>
            @if($item->is_sold)
                <button class="purchase-btn sold-out" disabled>SOLD OUT</button>
            @elseif(auth()->check() && $item->user_id === auth()->id())
                <button class="purchase-btn disabled" disabled>自分の商品です</button>
            @elseif(auth()->check())
                <a href="{{ route('orders.show', $item->id) }}" class="purchase-btn">購入手続きへ</a>
            @else
                <a href="{{ route('login') }}" class="purchase-btn">購入手続きへ</a>
            @endif
            <div class="product-section">
                <h3 class="section-title">商品説明</h3>
                <p class="product-description">{{ $item->description ?? '説明がありません。' }}</p>
            </div>
            <div class="product-section">
                <h3 class="section-title">商品の情報</h3>
                <div class="product-detail-item">
                    <span class="detail-label">カテゴリー</span>
                    <div class="detail-value">
                        @foreach($item->categories as $category)
                            <span class="category-tag">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="product-detail-item">
                    <span class="detail-label">商品の状態</span>
                    <span class="detail-value">{{ $item->condition->name }}</span>
                </div>
            </div>
            <div class="comments-section">
                <h3 class="section-title">コメント ({{ $commentsCount }})</h3>
                <div class="comments-list">
                    @forelse($comments as $comment)
                        <div class="comment-item">
                            <div class="comment-user">
                                @if($comment->user->profile_image)
                                    <img src="{{ asset('storage/' . $comment->user->profile_image) }}" alt="{{ $comment->user->name }}" class="comment-avatar">
                                @else
                                    <div class="comment-avatar-placeholder">
                                        {{ mb_substr($comment->user->name, 0, 1) }}
                                    </div>
                                @endif
                                <span class="comment-username">{{ $comment->user->name }}</span>
                            </div>
                            <p class="comment-content">{{ $comment->text }}</p>
                        </div>
                    @empty
                        <p class="no-comments">まだコメントがありません。</p>
                    @endforelse
                </div>
                <div class="comment-form">
                    <h4 class="form-title">商品へのコメント</h4>
                    <form action="{{ route('comments.store', $item->id) }}" method="POST">
                        @csrf
                        <textarea name="text" class="comment-textarea" rows="4" placeholder="コメントを入力してください">{{ old('text') }}</textarea>
                        @error('text')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="comment-submit-btn">コメントを送信する</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleLike(productId) {
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    const heartIcon = button.querySelector('.heart-icon');
    const likesCount = button.querySelector('.count');

    button.disabled = true;

    fetch(`/like/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('liked', data.liked);
            likesCount.textContent = data.likes_count;
        } else {
            alert(data.message || 'エラーが発生しました。');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('エラーが発生しました。');
    })
    .finally(() => {
        button.disabled = false;
    });
}
</script>

@endsection