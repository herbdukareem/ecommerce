<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'title', 'slug', 'description', 'base_price', 'status',
        'name', 'price', 'image', 'has_options', 'product_type', // New fields for admin product management
    ];

    protected $casts = [
        'has_options' => 'boolean',
    ];

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIANT = 'variant';
    public const TYPE_BASKET = 'basket';

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
     * Active product options sorted for customer display.
     */
    public function productOptions()
    {
        return $this->hasMany(Sku::class)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function basketComponents()
    {
        return $this->hasMany(BasketComponent::class, 'basket_product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function basketParentSku()
    {
        return $this->hasOne(Sku::class)->where('active', true)->oldestOfMany();
    }

    public function isBasket(): bool
    {
        return $this->product_type === self::TYPE_BASKET;
    }

    public function isVariantProduct(): bool
    {
        return $this->product_type === self::TYPE_VARIANT || (bool) $this->has_options;
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

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function inventoryLedgerEntries()
    {
        return $this->hasMany(InventoryLedgerEntry::class);
    }

    /**
     * Get average rating for this product.
     */
    public function averageRating()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating');
    }
}
