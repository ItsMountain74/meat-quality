<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\MeatScan */
class MeatScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = $this->image_disk ?: 'public';

        return [
            'id' => $this->id,
            'status' => $this->status,
            'image_url' => Storage::disk($disk)->url($this->image_path),
            'label' => $this->label,
            'confidence' => $this->confidence !== null ? (float) $this->confidence : null,
            'explanation' => $this->explanation,
            'recommendations' => $this->recommendations ?? [],
            'scanned_at' => optional($this->scanned_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}

