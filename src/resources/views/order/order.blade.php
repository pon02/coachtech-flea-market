@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/order/order.css') }}">
@endsection
@section('content')

<main class="order-main">
    <div class="order-container">
        <div class="order-form">
            <form action="{{ route('orders.store', $item->id) }}" method="POST" id="orderForm">
                @csrf
                <!-- 配送先住所の隠しフィールド -->
                <input type="hidden" name="postal_code" value="{{ $shippingData['postal_code'] ?? '' }}">
                <input type="hidden" name="address" value="{{ $shippingData['address'] ?? '' }}">
                <input type="hidden" name="building" value="{{ $shippingData['building'] ?? '' }}">

                <div class="product-summary">
                    <div class="product-image">
                        @if($item->item_image)
                            <img src="{{ asset('storage/' . $item->item_image) }}" alt="{{ $item->name }}">
                        @else
                            <div class="no-image">画像なし</div>
                        @endif
                    </div>
                    <div class="product-info">
                        <p class="product-name">{{ $item->name }}</p>
                        <p class="product-price"><span>¥</span>{{ number_format($item->price) }}</p>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-section">
                    <h3 class="section-heading">支払い方法</h3>
                    <div class="payment-select">
                        <select name="payment_id" id="payment_id" class="form-select">
                            <option class="form-select-option--disabled" value="" disabled selected>選択してください</option>
                            @foreach($payments as $payment)
                                <option value="{{ $payment->id }}">
                                    {{ $payment->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_id')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-section">
                    <div class="section-header">
                        <h3 class="section-heading">配送先</h3>
                        <a href="{{ route('shipping.edit', $item->id) }}" class="address-change-link">変更する</a>
                    </div>
                    <div class="address-info">
                        @if($shippingData['postal_code'] && $shippingData['address'])
                            <p class="address-line">〒{{ $shippingData['postal_code'] }}</p>
                            <p class="address-line">{{ $shippingData['address'] }}</p>
                            @if($shippingData['building'])
                                <p class="address-line">{{ $shippingData['building'] }}</p>
                            @endif
                        @else
                            <p class="address-notice">住所が登録されていません。<a href="{{ route('mypage.profile') }}">プロフィール設定</a>で住所を登録してください。</p>
                        @endif
                    </div>
                </div>

                <div class="divider"></div>

            </form>
        </div>

        <div class="order-summary">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">商品代金</td>
                        <td class="summary-value">¥{{ number_format($item->price) }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">支払い方法</td>
                        <td class="summary-value" id="selectedPaymentMethod">ー</td>
                    </tr>
                </table>
            </div>

            @if($shippingData['postal_code'] && $shippingData['address'])
                <button type="submit" form="orderForm" class="purchase-final-btn">購入する</button>
            @else
                <button type="button" class="purchase-final-btn disabled" disabled>住所を登録してください</button>
            @endif
        </div>
    </div>
</main>

<script>
    function updatePaymentMethod() {
        try {
            const select = document.getElementById('payment_id');
            const display = document.getElementById('selectedPaymentMethod');

            if (!select || !display) {
                console.warn('Required elements not found for payment method update');
                return;
            }

            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                display.textContent = selectedOption.text;
            } else {
                display.textContent = 'ー';
            }
        } catch (error) {
            console.error('Error updating payment method:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('payment_id');
        if (select) {
            updatePaymentMethod();
            select.addEventListener('change', updatePaymentMethod);
        }
    });
</script>

@endsection