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
        $query = Product::with(['primaryImage'])
            ->withCount('orderItems as sold'); // Đếm số lượng đã bán

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sắp xếp mặc định hoặc theo yêu cầu
        $products = $query->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Transform data cho giống với Vue mong đợi (nếu cần)
        $transformedProducts = $products->through(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->quantity,
                'image' => $product->primaryImage
                    ? asset($product->primaryImage->image_url)
                    : asset('assets/img/default.jpg'),
            ];
        });

        return Inertia::render('Admin/Products/Index', [
            'filters' => ['search' => $request->search],
            'products' => $transformedProducts, // Truyền Paginator object (đã transform items)
            'categories' => Category::select('id', 'name')->get(),
        ]);
    }
}
