<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'price' => 'required|numeric|min:1000',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.0' => 'nullable|image|max:2048',
            'images.*' => 'nullable|image|max:2048',
            'existing_images' => 'nullable|array',
            'existing_images.0' => 'required_without:images.0',
            'model_file' => 'nullable|file|mimetypes:model/gltf-binary|max:10240',
            'clear_model' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.min' => 'Giá bán tối thiểu là 1,000 VNĐ.',
            'quantity.min' => 'Tồn kho không được nhỏ hơn 0.',
            'existing_images.0.required_without' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'model_file.mimetypes' => 'Mô hình phải có định dạng .glb.',
        ];
    }
}
