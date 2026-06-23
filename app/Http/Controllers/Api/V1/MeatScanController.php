<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\MeatScanUploadRequest;
use App\Http\Resources\MeatScanResource;
use App\Models\MeatScan;
use App\Services\MeatScan\MeatScanService;
use Illuminate\Http\Request;

class MeatScanController extends ApiController
{
    public function upload(MeatScanUploadRequest $request, MeatScanService $service)
    {
        $scan = $service->upload($request->user(), $request->file('image'));

        return $this->success(new MeatScanResource($scan), 'Image uploaded successfully.', 201);
    }

    public function analyze(Request $request, MeatScan $meatScan, MeatScanService $service)
    {
        if ($response = $this->authorizeScan($request, $meatScan)) {
            return $response;
        }

        try {
            $scan = $service->analyze($meatScan);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new MeatScanResource($scan), 'Image analyzed successfully.');
    }

    public function history(Request $request)
    {
        $scans = MeatScan::query()
            ->where('status', MeatScan::STATUS_COMPLETED)
            ->where('user_id', $request->user()->id)
            ->latest('scanned_at')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->success(MeatScanResource::collection($scans), 'Scan history fetched successfully.');
    }

    public function show(Request $request, MeatScan $meatScan)
    {
        if ($response = $this->authorizeScan($request, $meatScan)) {
            return $response;
        }

        return $this->success(new MeatScanResource($meatScan), 'Meat scan fetched successfully.');
    }

    public function destroy(Request $request, MeatScan $meatScan)
    {
        if ($response = $this->authorizeScan($request, $meatScan)) {
            return $response;
        }

        $disk = $meatScan->image_disk ?: 'public';
        if ($meatScan->image_path) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($meatScan->image_path);
        }

        $meatScan->delete();

        return $this->success((object) [], 'Meat scan deleted successfully.');
    }

    private function authorizeScan(Request $request, MeatScan $meatScan)
    {
        if ($meatScan->user_id !== $request->user()->id) {
            return $this->error('Scan not found.', 404);
        }

        return null;
    }
}
