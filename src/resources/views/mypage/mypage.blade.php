@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage/mypage.css') }}">
@endsection

@section('content')
<section class="profile-section">
    <div class="profile-info">
        <div class="profile-info__avatar">
            @if($user->profile_image)
                <img src="{{ asset('storage/' . $user->profile_image) }}" alt="プロフィール画像" class="avatar-image">
            @else
                <div class="avatar-placeholder">
                    <span>{{ mb_substr($user->name, 0, 1) }}</span>
                </div>
            @endif
        </div>

        <div class="profile-info__details">
            <h1 class="profile-info__name">{{ $user->name }}</h1>
        </div>

        <div class="profile-info__actions">
            <a href="{{ route('mypage.profile') }}" class="profile-edit-btn">プロフィールを編集</a>
        </div>
    </div>
</section>

<section class="tabs-section">
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-button {{ $page === 'sell' ? 'active' : '' }}" data-tab="listed">出品した商品</button>
            <button class="tab-button {{ $page === 'buy' ? 'active' : '' }}" data-tab="purchased">購入した商品</button>
        </div>
        <div class="tab-divider"></div>
    </div>

    <div class="tab-content">
        <div id="listed" class="tab-panel {{ $page === 'sell' ? 'active' : '' }}">
                @if($listedItems->count() > 0)
                    <div class="products-grid">
                        @foreach($listedItems as $product)
                            <a href="{{ route('items.show', $product->id) }}" class="product-card">
                                <div class="product-image">
                                    @if($product->item_image)
                                        <img src="{{ asset('storage/' . $product->item_image) }}" alt="{{ $product->name }}">
                                    @else
                                        <div class="product-no-image">画像なし</div>
                                    @endif
                                    @if($product->is_sold)
                                        <div class="sold-out-overlay">
                                            <span class="sold-out-text">Sold</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name">{{ $product->name }}</h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <p>出品した商品がありません</p>
                    </div>
                @endif
            </div>

            <div id="purchased" class="tab-panel {{ $page === 'buy' ? 'active' : '' }}">
                @if($purchasedItems->count() > 0)
                    <div class="products-grid">
                        @foreach($purchasedItems as $product)
                            <a href="{{ route('items.show', $product->id) }}" class="product-card">
                                <div class="product-image">
                                    @if($product->item_image)
                                        <img src="{{ asset('storage/' . $product->item_image) }}" alt="{{ $product->name }}">
                                    @else
                                        <div class="product-no-image">画像なし</div>
                                    @endif
                                    @if($product->is_sold)
                                        <div class="sold-out-overlay">
                                            <span class="sold-out-text">Sold</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name">{{ $product->name }}</h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <p>購入した商品がありません</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('js/mypage.js') }}"></script>
@endsection