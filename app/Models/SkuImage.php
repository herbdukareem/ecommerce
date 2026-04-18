<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SkuImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
        'created_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (str_starts_with((string) $this->image_path, 'http://') || str_starts_with((string) $this->image_path, 'https://')) {
            return (string) $this->image_path;
        }

        return Storage::url((string) $this->image_path);
    }
}
