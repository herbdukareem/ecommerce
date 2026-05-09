<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body_html',
        'status',
        'recipient_count',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'recipient_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
