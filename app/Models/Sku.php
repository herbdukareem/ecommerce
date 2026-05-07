<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Stock;
use App\Models\Warehouse;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku_code', 'price', 'cost', 'weight', 'length', 'width', 'height', 'active',
        'stock_quantity', 'attributes',
        'option_label', 'option_code', 'compare_at_price', 'cost_price', 'low_stock_threshold',
        'image_path', 'sort_order', 'unit', 'metadata', 'created_by', 'updated_by', // New fields for variant management
    ];

    protected $casts = [
        'active' => 'boolean',
        'attributes' => 'array',
        'metadata' => 'array',
        'sort_order' => 'integer',
        'stock_quantity' => 'integer',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    protected $appends = [
        'display_label',
        'primary_image_url',
        'available_stock',
    ];

    protected static function booted(): void
    {
        static::created(function (Sku $sku) {
            $sku->loadMissing('product.vendor');

            $vendorId = $sku->product?->vendor_id;
            if (!$vendorId) {
                return;
            }

            $warehouse = Warehouse::firstOrCreate(
                ['vendor_id' => $vendorId, 'name' => 'Default Warehouse'],
                ['location_id' => null]
            );

            Stock::firstOrCreate(
                ['sku_id' => $sku->id, 'warehouse_id' => $warehouse->id],
                ['on_hand' => max(0, (int) ($sku->stock_quantity ?? 0)), 'reserved' => 0]
            );
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Attribute values defining this SKU.
     */
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'sku_attribute_value');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'variant_id');
    }

    public function inventoryLedgerEntries()
    {
        return $this->hasMany(InventoryLedgerEntry::class, 'variant_id');
    }

    public function images()
    {
        return $this->hasMany(SkuImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage()
    {
        return $this->hasOne(SkuImage::class)
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Alias for option naming in UI and APIs.
     */
    public function getDisplayLabelAttribute(): string
    {
        $variantAttributes = $this->getAttribute('attributes');
        $rawAttributeName = is_array($variantAttributes) ? data_get($variantAttributes, 'name') : null;
        return (string) ($this->option_label ?: $rawAttributeName ?: $this->sku_code ?: ('Option #' . $this->id));
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?: $this->images->sortBy('sort_order')->first()
            : $this->primaryImage()->first();

        if ($primary) {
            return $primary->image_url;
        }

        return $this->image_path ? \Storage::url($this->image_path) : null;
    }

    public function getAvailableStockAttribute(): int
    {
        $stocks = $this->relationLoaded('stocks') ? $this->stocks : $this->stocks()->get();

        return (int) $stocks->sum(fn (Stock $stock) => max(0, (int) $stock->on_hand - (int) $stock->reserved));
    }
}
