@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/main.css') }}">
@endsection

@section('content')
<main class="main">
    <div class="tab-container">
        <div class="tab-navigation">
            <button class="tab-button {{ $tab === 'recommended' ? 'active' : '' }}" data-tab="recommended">おすすめ</button>
            <button class="tab-button {{ $tab === 'mylist' ? 'active' : '' }}" data-tab="mylist">マイリスト</button>
        </div>
        <div class="tab-divider"></div>
    </div>

    <div class="tab-content-wrapper">
        <div class="tab-content {{ $tab === 'recommended' ? 'active' : '' }}" id="recommended">
            <div class="product-grid">
                @if(isset($items) && count($items) > 0)
                    @foreach($items as $item)
                    <a href="{{ route('items.show', $item->id) }}" class="product-card">
                        <div class="product-image">
                            <img src="{{ asset('storage/' . $item->item_image) }}" alt="{{ $item->name }}">
                            @if($item->is_sold)
                                <div class="sold-out-overlay">
                                    <span class="sold-out-text">Sold</span>
                                </div>
                            @endif
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">{{ $item->name }}</h3>
                        </div>
                    </a>
                    @endforeach
                @else
                    <p class="no-products">まだ商品が出品されていません</p>
                @endif
            </div>
        </div>

        <div class="tab-content {{ $tab === 'mylist' ? 'active' : '' }}" id="mylist">
            <div class="product-grid">
                @if($tab === 'mylist')
                    @auth
                        @if(isset($items) && count($items) > 0)
                            @foreach($items as $item)
                            <a href="{{ route('items.show', $item->id) }}" class="product-card">
                                <div class="product-image">
                                    <img src="{{ asset('storage/' . $item->item_image) }}" alt="{{ $item->name }}">
                                    @if($item->is_sold)
                                        <div class="sold-out-overlay">
                                            <span class="sold-out-text">Sold</span>
                                        </div>
                                    @endif
                                    <div class="like-badge">
                                        <img src="{{ asset('img/like.png') }}" alt="いいね" class="like-badge-icon">
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name">{{ $item->name }}</h3>
                                </div>
                            </a>
                            @endforeach
                        @else
                            <p class="no-products">いいねした商品がありません</p>
                        @endif
                    @else
                        <div class="login-prompt">
                            <p class="no-products">マイリストを使用するには<a href="{{ route('login') }}" class="login-link">ログイン</a>してください</p>
                        </div>
                    @endauth
                @else
                    <!-- タブが切り替わった時にJavaScriptで表示される -->
                @endif
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.dataset.tab;

            const keyword = new URLSearchParams(window.location.search).get('keyword') || '';

            let url = '/';
            let params = [];

            if (targetTab === 'mylist') {
                params.push('tab=mylist');
            }

            if (keyword) {
                params.push('keyword=' + encodeURIComponent(keyword));
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            window.location.href = url;
        });
    });
});
</script>
@endsection
