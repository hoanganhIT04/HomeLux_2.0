<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;

class ProductController extends Controller
{
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
}