<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => 'required|string|max:255|unique:categories,name,' . $categoryId,
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $categoryId,
            'image' => 'nullable|image|max:2048',
            'existing_image' => 'required_without:image',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
            'parent_id.not_in' => 'Danh mục cha không hợp lệ.',
            'existing_image.required_without' => 'Vui lòng chọn ảnh đại diện.',
            'image.image' => 'File tải lên phải là hình ảnh.'
        ];
    }
}
