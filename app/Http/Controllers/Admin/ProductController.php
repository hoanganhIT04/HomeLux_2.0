<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Services\Admin\ProductService;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Admin\ProductResource;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $products = Product::with(['primaryImage', 'categories', 'images'])
            ->withCount('orderItems as sold')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category);
                });
            })
            ->when($request->sort_sold, function ($query, $sort_sold) {
                $query->orderBy('sold', $sort_sold);
            }, function ($query) {
                $query->orderBy('id', 'asc');
            })
            ->paginate(10)
            ->withQueryString()
            ->through(fn($product) => (new ProductResource($product))->toArray($request));

        return Inertia::render('Admin/Products/Index', [
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'sort_sold' => $request->sort_sold
            ],
            'products' => $products,
            'categories' => Category::select('id', 'name')->get(),
            'lowStockCount' => Product::where('quantity', '<', 10)->count(),
            'bestSellingCount' => $this->productService->getBestSellingCount(),
            'popularCount' => $this->productService->getPopularCount(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->except(['images', 'model_file', 'category_ids']);
        $data['quantity'] = $request->input('quantity', 0);

        $product = Product::create($data);
        $product->categories()->attach($request->category_ids);

        $this->productService->handleModelFile($request->file('model_file'), $product);
        $this->productService->storeImages($request->file('images', []), $product);

        return redirect()->back()->with('success', 'Sản phẩm đã được tạo thành công.');
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($request->boolean('clear_model') && !$request->hasFile('model_file')) {
            return back()->withErrors(['model_file' => 'Sản phẩm bắt buộc phải có mô hình 3D.']);
        }

        $data = $request->except(['images', 'existing_images', 'model_file', 'clear_model', 'category_ids', '_method']);
        $data['quantity'] = $request->input('quantity', 0);

        $product->update($data);
        $product->categories()->sync($request->category_ids);

        $this->productService->handleModelFile($request->file('model_file'), $product);
        $this->productService->updateImages(
            $request->file('images', []),
            $request->input('existing_images', []),
            $product
        );

        return redirect()->back()->with('success', 'Sản phẩm đã được cập nhật.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $this->productService->deleteProductData($product);

        return redirect()->back()->with('success', 'Sản phẩm đã bị xóa.');
    }

    public function getLowStock()
    {
        $lowStockProducts = Product::select('id', 'name', 'quantity')
            ->where('quantity', '<', 10)
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json($lowStockProducts);
    }
}