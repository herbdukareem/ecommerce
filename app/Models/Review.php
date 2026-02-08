<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'verified_purchase',
        'is_approved',
    ];

    protected $casts = [
        'verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'rating' => 'integer',
    ];

    protected $appends = ['helpful_count', 'not_helpful_count'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function votes()
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function getHelpfulCountAttribute()
    {
        return $this->votes()->where('is_helpful', true)->count();
    }

    public function getNotHelpfulCountAttribute()
    {
        return $this->votes()->where('is_helpful', false)->count();
    }
}

