<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Traits\HandleUploadTrait;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    use HandleUploadTrait;

    public function index(Request $request)
    {
        $products = Product::with(['primaryImage', 'categories', 'images'])
            ->withCount('orderItems as sold')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($product) => $this->formatProduct($product));

        $bestSellingCount = Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('orders.payment_method', 'cod')
                            ->where('orders.status', 'completed');
                    })->orWhere(function ($sq) {
                        $sq->where('orders.payment_method', 'momo')
                            ->where('orders.status', '!=', 'cancelled');
                    });
                })
                ->groupBy('order_items.product_id')
                ->havingRaw('SUM(order_items.quantity) > 30');
        })->count();

        $popularCount = Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('product_reviews')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('product_id')
                ->havingRaw('COUNT(product_reviews.id) > 30')
                ->havingRaw('AVG(product_reviews.rating) > 4');
        })->count();

        return Inertia::render('Admin/Products/Index', [
            'filters' => ['search' => $request->search],
            'products' => $products,
            'categories' => Category::select('id', 'name')->get(),
            'lowStockCount' => Product::where('quantity', '<', 10)->count(),
            'bestSellingCount' => $bestSellingCount,
            'popularCount' => $popularCount,
        ]);
    }

    public function store(Request $request)
    {
        $this->validateProduct($request, isUpdate: false);

        $data = $request->except(['images', 'model_file', 'category_ids']);
        $data['quantity'] = $request->input('quantity', 0);

        $product = Product::create($data);
        $product->categories()->attach($request->category_ids);

        $this->handleModelFile($request, $product);
        $this->storeImages($request, $product);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $this->validateProduct($request, isUpdate: true);

        if ($request->boolean('clear_model') && !$request->hasFile('model_file')) {
            return back()->withErrors(['model_file' => 'Sản phẩm bắt buộc phải có mô hình 3D.']);
        }

        $data = $request->except(['images', 'existing_images', 'model_file', 'clear_model', 'category_ids', '_method']);
        $data['quantity'] = $request->input('quantity', 0);

        $product->update($data);
        $product->categories()->sync($request->category_ids);

        $this->handleModelFile($request, $product);
        $this->updateImages($request, $product);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        foreach ($product->images as $image) {
            $this->deleteFile($image->image_url);
        }

        if ($product->model_url) {
            $this->deleteFile($product->model_url);
        }

        $imageFolder = public_path("uploads/product_images/{$id}");
        $modelFolder = public_path("uploads/model3d/{$id}");

        if (File::isDirectory($imageFolder))
            File::deleteDirectory($imageFolder);
        if (File::isDirectory($modelFolder))
            File::deleteDirectory($modelFolder);

        $product->categories()->detach();
        $product->images()->delete();
        $product->delete();

        return redirect()->back();
    }

    public function getLowStock()
    {
        $lowStockProducts = Product::select('id', 'name', 'quantity')
            ->where('quantity', '<', 10)
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json($lowStockProducts);
    }

    /* =========================================================================
       CÁC HÀM HELPER (PRIVATE) HỖ TRỢ XỬ LÝ LOGIC CHI TIẾT
       ========================================================================= */

    private function formatProduct($product)
    {
        $allImages = $product->images
            ->sortByDesc('is_primary')
            ->sortBy('display_order')
            ->pluck('image_url')
            ->map(fn($url) => asset($url))
            ->values()
            ->toArray();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->quantity,
            'quantity' => $product->quantity,
            'category_ids' => $product->categories->pluck('id')->toArray(),
            'description' => $product->description,
            'sold' => $product->sold ?? 0,
            'image' => $product->primaryImage ? asset($product->primaryImage->image_url) : asset('assets/img/default.jpg'),
            'all_images' => $allImages,
            'model_url' => $product->model_url ? asset($product->model_url) : null,
        ];
    }

    private function validateProduct(Request $request, $isUpdate)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'price' => 'required|numeric|min:1000',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'images.*' => 'nullable|image|max:2048',
        ];

        if ($isUpdate) {
            $rules['images.0'] = 'nullable|image|max:2048';
            $rules['existing_images.0'] = 'required_without:images.0';
            $rules['model_file'] = 'nullable|file|mimetypes:model/gltf-binary|max:10240';
        } else {
            $rules['images.0'] = 'required|image|max:2048';
            $rules['model_file'] = 'required|file|mimetypes:model/gltf-binary|max:10240';
        }

        $request->validate($rules, [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.min' => 'Giá bán tối thiểu là 1,000 VNĐ.',
            'quantity.min' => 'Tồn kho không được nhỏ hơn 0.',
            'images.0.required' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'images.0.image' => 'Ảnh chính phải là định dạng hình ảnh.',
            'existing_images.0.required_without' => 'Vui lòng chọn ảnh chính cho sản phẩm.',
            'model_file.required' => 'Vui lòng tải lên mô hình 3D (.glb).',
            'model_file.mimetypes' => 'Mô hình phải có định dạng .glb.',
        ]);
    }

    private function handleModelFile(Request $request, Product $product)
    {
        if ($request->hasFile('model_file')) {
            if ($product->model_url) {
                $this->deleteFile($product->model_url);
            }
            $product->model_url = $this->uploadFile($request->file('model_file'), "model3d/{$product->id}");
            $product->save();
        }
    }

    private function storeImages(Request $request, Product $product)
    {
        $validImages = array_values(array_filter($request->file('images', [])));
        foreach ($validImages as $index => $file) {
            $product->images()->create([
                'image_url' => $this->uploadFile($file, "product_images/{$product->id}"),
                'is_primary' => ($index === 0),
                'display_order' => $index + 1
            ]);
        }
    }

    private function updateImages(Request $request, Product $product)
    {
        $newFiles = $request->file('images', []);
        $existingUrls = $request->input('existing_images', []);
        $finalPaths = [];

        for ($i = 0; $i < 4; $i++) {
            if (!empty($newFiles[$i])) {
                $finalPaths[] = $this->uploadFile($newFiles[$i], "product_images/{$product->id}");
            } elseif (!empty($existingUrls[$i])) {
                $finalPaths[] = '/' . ltrim(str_replace(asset(''), '', $existingUrls[$i]), '/');
            }
        }

        // Xóa ảnh vật lý cũ nếu không còn được sử dụng
        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->image_url, $finalPaths)) {
                $this->deleteFile($oldImage->image_url);
            }
        }

        // Cập nhật Database
        $product->images()->delete();
        foreach ($finalPaths as $index => $path) {
            $product->images()->create([
                'image_url' => $path,
                'is_primary' => ($index === 0),
                'display_order' => $index + 1
            ]);
        }
    }
}