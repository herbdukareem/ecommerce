<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'title', 'slug', 'description', 'base_price', 'status',
        'name', 'price', 'image', // New fields for admin product management
    ];

    /**
     * A product belongs to a vendor (User).
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * A product has many SKUs (variants).
     */
    public function skus()
    {
        return $this->hasMany(Sku::class);
    }

    /**
     * A product has many images.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    /**
     * Categories associated with the product.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Attributes applicable to this product.
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }

    /**
     * Reviews for this product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get average rating for this product.
     */
    public function averageRating()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating');
    }
}