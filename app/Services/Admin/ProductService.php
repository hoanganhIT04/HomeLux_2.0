<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Traits\HandleUploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ProductService
{
    use HandleUploadTrait;

    /**
     * Get best selling product count based on complex 30-day aggregate query
     */
    public function getBestSellingCount(): int
    {
        return Product::whereIn('id', function ($query) {
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
    }

    /**
     * Get popular product count based on 30-day reviews
     */
    public function getPopularCount(): int
    {
        return Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('product_reviews')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('product_id')
                ->havingRaw('COUNT(product_reviews.id) > 30')
                ->havingRaw('AVG(product_reviews.rating) > 4');
        })->count();
    }

    /**
     * Handle the upload and processing of 3D Models
     */
    public function handleModelFile(?UploadedFile $file, Product $product): void
    {
        if ($file) {
            if ($product->model_url) {
                $this->deleteFile($product->model_url);
            }
            $product->model_url = $this->uploadFile($file, "model3d/{$product->id}");
            $product->save();
        }
    }

    /**
     * Store brand new images for a product
     */
    public function storeImages(array $images, Product $product): void
    {
        $validImages = array_values(array_filter($images));
        foreach ($validImages as $index => $file) {
            if ($file instanceof UploadedFile) {
                $product->images()->create([
                    'image_url' => $this->uploadFile($file, "product_images/{$product->id}"),
                    'is_primary' => ($index === 0),
                    'display_order' => $index + 1
                ]);
            }
        }
    }

    /**
     * Update images for an existing product (Delete unused, add new)
     */
    public function updateImages(array $newFiles, array $existingUrls, Product $product): void
    {
        $finalPaths = [];

        for ($i = 0; $i < 4; $i++) {
            if (!empty($newFiles[$i]) && $newFiles[$i] instanceof UploadedFile) {
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

    /**
     * Complete cleanup of product files
     */
    public function deleteProductData(Product $product): void
    {
        foreach ($product->images as $image) {
            $this->deleteFile($image->image_url);
        }

        if ($product->model_url) {
            $this->deleteFile($product->model_url);
        }

        $imageFolder = public_path("uploads/product_images/{$product->id}");
        $modelFolder = public_path("uploads/model3d/{$product->id}");

        if (File::isDirectory($imageFolder)) {
            File::deleteDirectory($imageFolder);
        }
        if (File::isDirectory($modelFolder)) {
            File::deleteDirectory($modelFolder);
        }

        $product->categories()->detach();
        $product->images()->delete();
        $product->delete();
    }
}
