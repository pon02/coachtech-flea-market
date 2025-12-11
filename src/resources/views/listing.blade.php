@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/listing.css') }}">
@endsection

@section('content')
<main class="listing">
    <div class="listing-container">
        <div class="listing-title">
            <h1>商品の出品</h1>
        </div>
        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="listing-form" novalidate>
            @csrf
            <section class="listing-section">
                <h2 class="form-label">商品画像</h2>
                <div class="image-upload-area">
                    <div class="image-preview" id="imagePreview">
                        <input type="file" id="item_image" name="item_image" accept="image/*" class="image-input">
                        <label for="item_image" class="image-upload-button">画像を選択する</label>
                    </div>
                    @error('item_image')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </section>
            <section class="listing-section">
                <h2 class="section-title">商品の詳細</h2>
                <div class="form-group">
                    <label class="form-label">カテゴリー</label>
                    <div class="category-list">
                        @foreach($categories as $category)
                        <label class="category-item">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="category-checkbox">
                            <span class="category-label">{{ $category->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('categories')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="condition_id" class="form-label">商品の状態</label>
                    <select name="condition_id" id="condition_id" class="form-select">
                        <option value="">選択してください</option>
                        @foreach($conditions as $condition)
                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                        @endforeach
                    </select>
                    @error('condition_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </section>
            <section class="listing-section">
                <h2 class="section-title">商品名と説明</h2>
                <div class="form-group">
                    <label for="name" class="form-label">商品名</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input">
                    @error('name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="brand_name" class="form-label">ブランド名</label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name') }}" class="form-input">
                    @error('brand_name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">商品の説明</label>
                    <textarea id="description" name="description" rows="5" class="form-textarea">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="price" class="form-label">販売価格</label>
                    <div class="price-input-wrapper">
                        <span class="price-symbol">¥</span>
                        <input type="number" id="price" name="price" value="{{ old('price') }}" min="1" class="form-input price-input">
                    </div>
                    @error('price')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </section>
            <div class="form-actions">
                <button type="submit" class="submit-button">出品する</button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('item_image');
    const imagePreview = document.getElementById('imagePreview');
    let originalContent = imagePreview.innerHTML;

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = imagePreview.querySelector('.preview-image');
                if (previewImg) {
                    previewImg.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = '商品画像プレビュー';
                    img.className = 'preview-image';
                    imagePreview.insertBefore(img, imagePreview.firstChild);
                }

                const label = imagePreview.querySelector('label');
                if (label) {
                    label.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }
    });

    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.checked) {
                label.classList.add('selected');
            } else {
                label.classList.remove('selected');
            }
        });
    });
});
</script>
@endsection
