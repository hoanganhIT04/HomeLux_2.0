<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Traits\HandleUploadTrait;

class ProductController extends Controller
{
    use HandleUploadTrait;
    public function index(Request $request)
    {
        // 1. Thêm 'images' vào để load toàn bộ ảnh phụ
        $query = Product::with(['primaryImage', 'categories', 'images'])
            ->withCount('orderItems as sold');

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sắp xếp mặc định hoặc theo yêu cầu
        $products = $query->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Transform data
        $transformedProducts = $products->through(function ($product) {

            // Lấy tất cả ảnh, ưu tiên ảnh chính (is_primary = 1) lên đầu, rồi tới ảnh phụ theo thứ tự (display_order)
            $allImages = $product->images->sortByDesc('is_primary')->sortBy('display_order')->map(function ($img) {
                return asset($img->image_url);
            })->toArray();

            // Đảm bảo mảng index liên tục từ 0
            $allImages = array_values($allImages);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->quantity,
                'quantity' => $product->quantity,
                'category_ids' => $product->categories->pluck('id')->toArray(),
                'description' => $product->description,
                'sold' => $product->sold ?? 0,

                // Vẫn giữ 'image' để hiển thị ảnh đại diện ngoài Bảng danh sách
                'image' => $product->primaryImage ? asset($product->primaryImage->image_url) : asset('assets/img/default.jpg'),

                // MỚI: Truyền mảng chứa tất cả url ảnh vào Modal
                'all_images' => $allImages,
                'model_url' => $product->model_url ? asset($product->model_url) : null,
            ];
        });

        return Inertia::render('Admin/Products/Index', [
            'filters' => ['search' => $request->search],
            'products' => $transformedProducts,
            'categories' => Category::select('id', 'name')->get(),
        ]);
    }
    public function store(Request $request)
    {
        // 1. Tạo SP & Gắn danh mục
        $product = Product::create($request->except(['images', 'model_file', 'category_ids']));
        if ($request->category_ids) {
            $product->categories()->attach($request->category_ids);
        }

        // 2. Upload Model 3D
        if ($request->hasFile('model_file')) {
            $product->model_url = $this->uploadFile($request->file('model_file'), "model3d/{$product->id}");
            $product->save();
        }

        // 3. Xử lý Ảnh (Tự động dồn hàng)
        $images = $request->file('images', []);
        $validImages = array_values(array_filter($images)); // Ép mảng [null, file, null, file] thành [file, file]

        foreach ($validImages as $index => $file) {
            $path = $this->uploadFile($file, "product_images/{$product->id}");
            $product->images()->create([
                'image_url' => $path,
                'is_primary' => ($index === 0), // Cái đầu tiên tự động thành ảnh chính
                'display_order' => $index + 1   // Thứ tự 1, 2, 3...
            ]);
        }

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 1. Cập nhật Info & Danh mục
        $product->update($request->except(['images', 'existing_images', 'model_file', 'clear_model', 'category_ids', '_method']));
        if ($request->category_ids) {
            $product->categories()->sync($request->category_ids); // sync tự động xóa cũ thêm mới
        }

        // 2. Xử lý Model 3D
        if ($request->hasFile('model_file')) {
            $this->deleteFile($product->model_url); // Xóa file 3d cũ
            $product->model_url = $this->uploadFile($request->file('model_file'), "model3d/{$product->id}");
            $product->save();
        } elseif ($request->boolean('clear_model')) { // Nếu user bấm nút (X) xóa model
            $this->deleteFile($product->model_url);
            $product->model_url = null;
            $product->save();
        }

        // 3. Xử lý logic dồn Ảnh phức tạp
        $newFiles = $request->file('images', []);
        $existingUrls = $request->input('existing_images', []);
        $finalPaths = [];

        // Duyệt 4 ô trên form, nhặt lấy file mới HOẶC file cũ đang giữ lại
        for ($i = 0; $i < 4; $i++) {
            if (isset($newFiles[$i]) && $newFiles[$i]) {
                $finalPaths[] = $this->uploadFile($newFiles[$i], "product_images/{$product->id}");
            } elseif (isset($existingUrls[$i]) && $existingUrls[$i]) {
                // Cắt bỏ phần "http://domain.com/" để lấy lại đường dẫn gốc trong DB
                $finalPaths[] = str_replace(asset(''), '', $existingUrls[$i]);
            }
        }

        // Xóa những ảnh vật lý trên Server nếu nó không còn nằm trong danh sách cuối cùng
        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->image_url, $finalPaths)) {
                $this->deleteFile($oldImage->image_url);
            }
        }

        // Reset bảng DB ảnh và ghi lại thứ tự mới chuẩn (1, 2, 3...)
        $product->images()->delete();
        foreach ($finalPaths as $index => $path) {
            $product->images()->create([
                'image_url' => $path,
                'is_primary' => ($index === 0),
                'display_order' => $index + 1
            ]);
        }

        return redirect()->back();
    }
}