<?php

namespace App\Services\MeatScan;

use App\Models\MeatScan;
use App\Models\User;
use App\Services\MeatScan\Contracts\MeatDetector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MeatScanService
{
    public function __construct(
        private readonly MeatDetector $detector,
    ) {
    }

    public function upload(?User $user, UploadedFile $image): MeatScan
    {
        $disk = 'public';
        $path = $image->store('meat-scans', $disk);

        /** @var MeatScan $scan */
        $scan = MeatScan::query()->create([
            'user_id' => $user?->id,
            'image_disk' => $disk,
            'image_path' => $path,
            'status' => MeatScan::STATUS_PENDING,
        ]);

        return $scan;
    }

    public function analyze(MeatScan $scan): MeatScan
    {
        if ($scan->status === MeatScan::STATUS_COMPLETED) {
            return $scan;
        }

        $disk = $scan->image_disk ?: 'public';
        $absolutePath = Storage::disk($disk)->path($scan->image_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Uploaded image file is missing.');
        }

        try {
            $result = $this->detector->detect($absolutePath);

            $scan->fill([
                'label' => $result->label,
                'confidence' => $result->confidence,
                'explanation' => $result->explanation,
                'recommendations' => $result->recommendations,
                'scanned_at' => $result->scannedAt,
                'status' => MeatScan::STATUS_COMPLETED,
            ])->save();
        } catch (\Throwable $e) {
            $scan->update(['status' => MeatScan::STATUS_FAILED]);

            throw $e;
        }

        return $scan->refresh();
    }

    public function create(?User $user, UploadedFile $image): MeatScan
    {
        $scan = $this->upload($user, $image);

        return $this->analyze($scan);
    }
}
