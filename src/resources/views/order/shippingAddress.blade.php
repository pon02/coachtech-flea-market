@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/order/shippingAddress.css') }}">
@endsection
@section('content')
<main class="shipping-main">
    <div class="shipping-container">
        <h1 class="page-title">住所の変更</h1>
        <form action="{{ route('shipping.update', $item->id) }}" method="POST" class="shipping-form">
            @csrf
            <div class="form-group">
                <label for="postal_code" class="form-label">郵便番号</label>
                <input type="text"
                       id="postal_code"
                       name="postal_code"
                       class="form-input {{ $errors->has('postal_code') ? 'error' : '' }}"
                       placeholder="1234567"
                       value="{{ old('postal_code', $shippingData['postal_code']) }}"
                       maxlength="7">
                @error('postal_code')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="address" class="form-label">住所</label>
                <input type="text"
                       id="address"
                       name="address"
                       class="form-input {{ $errors->has('address') ? 'error' : '' }}"
                       placeholder="東京都渋谷区千駄ヶ谷1-2-3"
                       value="{{ old('address', $shippingData['address']) }}">
                @error('address')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="building" class="form-label">建物名</label>
                <input type="text"
                       id="building"
                       name="building"
                       class="form-input {{ $errors->has('building') ? 'error' : '' }}"
                       placeholder="コーチテックビル101"
                       value="{{ old('building', $shippingData['building']) }}">
                @error('building')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="update-btn">更新する</button>
        </form>
    </div>
</main>

@endsection