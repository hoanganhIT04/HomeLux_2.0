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
        // --- 1. VALIDATION ---
        $request->validate([
            'name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id', // Đảm bảo danh mục hợp lệ
            'price' => 'required|numeric|min:1000',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'images.0' => 'required|image|max:2048', // Bắt buộc ảnh chính, tối đa 2MB
            'images.*' => 'nullable|image|max:2048', // Các ảnh phụ (nếu có)
            'model_file' => 'required|file|mimetypes:model/gltf-binary|max:10240', // Bắt buộc model 3D (.glb), tối đa 10MB
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.min' => 'Giá bán tối thiểu là 1,000 VNĐ.',
            'quantity.min' => 'Tồn kho không được nhỏ hơn 0.',
            'images.0.required' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'images.0.image' => 'Ảnh chính phải là định dạng hình ảnh.',
            'model_file.required' => 'Vui lòng tải lên mô hình 3D (.glb).',
            'model_file.mimetypes' => 'Mô hình phải có định dạng .glb.',
        ]);

        // Xử lý mặc định tồn kho
        $data = $request->except(['images', 'model_file', 'category_ids']);
        $data['quantity'] = $request->input('quantity', 0); // Gán 0 nếu trống

        // --- 2. LƯU DỮ LIỆU ---
        $product = Product::create($data);
        $product->categories()->attach($request->category_ids);

        if ($request->hasFile('model_file')) {
            $product->model_url = $this->uploadFile($request->file('model_file'), "model3d/{$product->id}");
            $product->save();
        }

        $images = $request->file('images', []);
        $validImages = array_values(array_filter($images));

        foreach ($validImages as $index => $file) {
            $path = $this->uploadFile($file, "product_images/{$product->id}");
            $product->images()->create([
                'image_url' => $path,
                'is_primary' => ($index === 0),
                'display_order' => $index + 1
            ]);
        }

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // --- 1. VALIDATION ---
        $request->validate([
            'name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'price' => 'required|numeric|min:1000',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            // Ảnh chính: Bắt buộc phải có file mới HOẶC URL cũ tồn tại ở vị trí 0
            'images.0' => 'nullable|image|max:2048',
            'existing_images.0' => 'required_without:images.0',
            'model_file' => 'nullable|file|mimetypes:model/gltf-binary|max:10240',
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.min' => 'Giá bán tối thiểu là 1,000 VNĐ.',
            'quantity.min' => 'Tồn kho không được nhỏ hơn 0.',
            'existing_images.0.required_without' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'model_file.mimetypes' => 'Mô hình phải có định dạng .glb.',
        ]);

        // Custom validation logic cho model 3D khi update
        // Nếu user tick clear_model VÀ không up file mới -> Lỗi
        if ($request->boolean('clear_model') && !$request->hasFile('model_file')) {
            return back()->withErrors(['model_file' => 'Sản phẩm bắt buộc phải có mô hình 3D.']);
        }

        // --- 2. CẬP NHẬT DỮ LIỆU ---
        $data = $request->except(['images', 'existing_images', 'model_file', 'clear_model', 'category_ids', '_method']);
        $data['quantity'] = $request->input('quantity', 0); // Gán 0 nếu trống

        $product->update($data);
        $product->categories()->sync($request->category_ids);

        // Xử lý Model 3D
        if ($request->hasFile('model_file')) {
            $this->deleteFile($product->model_url);
            $product->model_url = $this->uploadFile($request->file('model_file'), "model3d/{$product->id}");
            $product->save();
        }
        // Đã chặn việc xóa model mà không up mới ở bước validate
        // elseif ($request->boolean('clear_model')) { ... } 

        // Xử lý Ảnh
        $newFiles = $request->file('images', []);
        $existingUrls = $request->input('existing_images', []);
        $finalPaths = [];

        for ($i = 0; $i < 4; $i++) {
            if (isset($newFiles[$i]) && $newFiles[$i]) {
                $finalPaths[] = $this->uploadFile($newFiles[$i], "product_images/{$product->id}");
            } elseif (isset($existingUrls[$i]) && $existingUrls[$i]) {
                $cleanUrl = '/' . ltrim(str_replace(asset(''), '', $existingUrls[$i]), '/');
                $finalPaths[] = $cleanUrl;
            }
        }

        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->image_url, $finalPaths)) {
                $this->deleteFile($oldImage->image_url);
            }
        }

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
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // 1. Xóa toàn bộ ảnh vật lý của sản phẩm này
        foreach ($product->images as $image) {
            $this->deleteFile($image->image_url);
        }

        // 2. Xóa Model 3D vật lý (nếu có)
        if ($product->model_url) {
            $this->deleteFile($product->model_url);
        }

        // 3. Xóa các thư mục rỗng chứa ID sản phẩm (tùy chọn để sạch server)
        $imageFolder = public_path("uploads/product_images/{$id}");
        $modelFolder = public_path("uploads/model3d/{$id}");
        if (\Illuminate\Support\Facades\File::isDirectory($imageFolder)) {
            \Illuminate\Support\Facades\File::deleteDirectory($imageFolder);
        }
        if (\Illuminate\Support\Facades\File::isDirectory($modelFolder)) {
            \Illuminate\Support\Facades\File::deleteDirectory($modelFolder);
        }

        // 4. Xóa liên kết danh mục trong bảng trung gian (nếu CSDL chưa có tính năng onDelete('cascade'))
        $product->categories()->detach();

        // 5. Xóa thông tin ảnh trong CSDL
        $product->images()->delete();

        // 6. Xóa sản phẩm khỏi CSDL
        $product->delete();

        return redirect()->back();
    }
}