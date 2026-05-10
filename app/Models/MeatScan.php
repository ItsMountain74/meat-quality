<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeatScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_disk',
        'image_path',
        'label',
        'confidence',
        'explanation',
        'recommendations',
        'scanned_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'recommendations' => 'array',
        'scanned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

