<?php

namespace App\Services\MeatScan;

use App\Models\MeatScan;
use App\Services\MeatScan\Contracts\MeatDetector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MeatScanService
{
    public function __construct(
        private readonly MeatDetector $detector,
    ) {
    }

    public function create(?\App\Models\User $user, UploadedFile $image): MeatScan
    {
        $disk = 'public';
        $path = $image->store('meat-scans', $disk);

        $absolutePath = Storage::disk($disk)->path($path);
        $result = $this->detector->detect($absolutePath);

        /** @var \App\Models\MeatScan $scan */
        $scan = MeatScan::query()->create([
            'user_id' => $user?->id,
            'image_disk' => $disk,
            'image_path' => $path,
            'label' => $result->label,
            'confidence' => $result->confidence,
            'explanation' => $result->explanation,
            'recommendations' => $result->recommendations,
            'scanned_at' => $result->scannedAt,
        ]);

        return $scan;
    }
}

