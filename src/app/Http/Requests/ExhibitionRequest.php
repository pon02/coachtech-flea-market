<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'condition_id' => 'required|exists:conditions,id',
            'description' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'item_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => '商品名を入力してください',
            'name.max' => '商品名は255文字以内で入力してください',
            'brand_name.max' => 'ブランド名は255文字以内で入力してください',
            'categories.required' => 'カテゴリーを選択してください',
            'categories.min' => 'カテゴリーは最低1つ選択してください',
            'categories.*.exists' => '選択されたカテゴリーが無効です',
            'condition_id.required' => '商品の状態を選択してください',
            'condition_id.exists' => '選択された状態が無効です',
            'description.required' => '商品の説明を入力してください',
            'description.max' => '商品の説明は255文字以内で入力してください',
            'price.required' => '商品価格を入力してください',
            'price.numeric' => '商品価格は数値で入力してください',
            'price.min' => '商品価格は0円以上で入力してください',
            'item_image.required' => '商品画像をアップロードしてください',
            'item_image.image' => '商品画像は画像ファイルを選択してください',
            'item_image.mimes' => '商品画像はjpegもしくはpng形式でアップロードしてください',
            'item_image.max' => '商品画像のサイズは2MB以下にしてください',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'name' => '商品名',
            'brand_name' => 'ブランド名',
            'categories' => 'カテゴリー',
            'condition_id' => '商品の状態',
            'description' => '商品の説明',
            'price' => '商品価格',
            'item_image' => '商品画像',
        ];
    }
}
