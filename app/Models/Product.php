<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'avg_rating',
        'total_reviews',
        'model_url'
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')
            ->where('is_primary', 1);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function secondaryImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')
            ->where('is_primary', 0)
            ->orderBy('display_order');
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }
    public function isWishlistedByUser()
    {
        if (!Auth::check())
            return false;

        return $this->wishlistedBy()
            ->where('user_id', Auth::id())
            ->exists();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
