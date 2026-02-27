<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'required|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
            'image.required' => 'Vui lòng chọn ảnh đại diện.',
            'image.image' => 'File tải lên phải là hình ảnh.'
        ];
    }
}
