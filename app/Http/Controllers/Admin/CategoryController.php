<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HandleUploadTrait;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use HandleUploadTrait;

    public function index(Request $request)
    {
        $query = Category::with('parent')->withCount('products'); 

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        $transformedCategories = $categories->through(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'parent_name' => $cat->parent ? $cat->parent->name : null,
                'products_count' => $cat->products_count ?? 0,
                'image_url' => $cat->image_url ? asset($cat->image_url) : asset('assets/img/default-category.jpg'),
                'raw_image' => $cat->image_url 
            ];
        });

        $allCategories = Category::select('id', 'name')->orderBy('name')->get();
        $totalCategories = Category::count();

        return Inertia::render('Admin/Categories/Index', [
            'filters' => ['search' => $request->search],
            'categories' => $transformedCategories,
            'allCategories' => $allCategories, 
            'totalCategories' => $totalCategories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'required|image|max:2048', 
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
            'image.required' => 'Vui lòng chọn ảnh đại diện.',
            'image.image' => 'File tải lên phải là hình ảnh.'
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
        ];

        // Upload ảnh giữ nguyên tên gốc vào thư mục uploads/category_image
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadFile($request->file('image'), 'category_image');
        }

        Category::create($data);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id, 
            'image' => 'nullable|image|max:2048',
            'existing_image' => 'required_without:image', 
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
            'parent_id.not_in' => 'Danh mục cha không hợp lệ.',
            'existing_image.required_without' => 'Vui lòng chọn ảnh đại diện.',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
        ];

        if ($request->hasFile('image')) {
            $this->deleteFile($category->image_url);
            $data['image_url'] = $this->uploadFile($request->file('image'), 'category_image');
        } elseif (!$request->existing_image) {
            $this->deleteFile($category->image_url);
            $data['image_url'] = null;
        }

        $category->update($data);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if (Category::where('parent_id', $id)->exists()) {
             return redirect()->back()->withErrors(['message' => 'Không thể xóa vì chứa danh mục con!']);
        }

        $this->deleteFile($category->image_url);
        $category->delete();

        return redirect()->back();
    }
}