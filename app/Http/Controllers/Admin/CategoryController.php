<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HandleUploadTrait;
use Illuminate\Support\Str;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;

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

    public function store(StoreCategoryRequest $request)
    {
        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
        ];

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadFile($request->file('image'), 'category_image');
        }

        Category::create($data);

        return redirect()->back();
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);

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

    public function getCategoryTree()
    {
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        return response()->json($categories);
    }
}