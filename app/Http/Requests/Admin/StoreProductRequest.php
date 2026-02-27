<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'images' => 'required|array',
            'images.0' => 'required|image|max:2048',
            'images.*' => 'nullable|image|max:2048',
            'model_file' => 'required|file|mimetypes:model/gltf-binary|max:10240',
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
            'images.0.required' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'images.0.image' => 'Ảnh chính phải là định dạng hình ảnh.',
            'model_file.required' => 'Vui lòng tải lên mô hình 3D (.glb).',
            'model_file.mimetypes' => 'Mô hình phải có định dạng .glb.',
        ];
    }
}
