@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage/profile.css') }}">
@endsection

@section('content')
<div class="profile-form__content">
    @if (session('status'))
        <div class="alert-success" style="margin-bottom: 20px; padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
            {{ session('status') }}
        </div>
    @endif
    <div class="profile-form__heading">
        <h2>プロフィール設定</h2>
    </div>
    <form action="{{ route('mypage.profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form" novalidate>
        @csrf
        <div class="profile-form__group">
            <div class="profile-image__container">
                <div class="profile-image__preview" id="imagePreview">
                    @if(auth()->user()->profile_image)
                        <img id="previewImg" src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="プロフィール画像" style="display:block;">
                    @else
                        <img id="previewImg" src="#" alt="プロフィール画像プレビュー" style="display:none;">
                    @endif
                </div>
                <input type="file" id="profile_image" name="profile_image" accept="image/*" class="profile-image__input">
                <label for="profile_image" class="profile-image__button">画像を選択する</label>
            </div>
            @error('profile_image')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>
        <div class="profile-form__group">
            <label for="name" class="profile-form__label">ユーザー名</label>
            <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" class="profile-form__input">
            @error('name')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>
        <div class="profile-form__group">
            <label for="postal_code" class="profile-form__label">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', auth()->user()->postal_code ?? '') }}" class="profile-form__input">
            @error('postal_code')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>
        <div class="profile-form__group">
            <label for="address" class="profile-form__label">住所</label>
            <input type="text" id="address" name="address" value="{{ old('address', auth()->user()->address ?? '') }}" class="profile-form__input">
            @error('address')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>
        <div class="profile-form__group">
            <label for="building" class="profile-form__label">建物名</label>
            <input type="text" id="building" name="building" value="{{ old('building', auth()->user()->building ?? '') }}" class="profile-form__input">
            @error('building')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>
        <div class="profile-form__button-group">
            <button type="submit" class="profile-form__button">更新する</button>
        </div>
    </form>
</div>

<script>
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('previewImg');
    const placeholder = document.querySelector('.profile-image__placeholder');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
});
</script>
@endsection