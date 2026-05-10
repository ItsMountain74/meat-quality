<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\MeatScanStoreRequest;
use App\Http\Resources\MeatScanResource;
use App\Models\MeatScan;
use App\Services\MeatScan\MeatScanService;

class MeatScanController extends ApiController
{
    public function index()
    {
        $scans = MeatScan::query()
            ->latest('scanned_at')
            ->paginate(15);

        return $this->success(MeatScanResource::collection($scans), 'Meat scans fetched successfully.');
    }

    public function store(MeatScanStoreRequest $request, MeatScanService $service)
    {
        $scan = $service->create(null, $request->file('image'));

        return $this->success(new MeatScanResource($scan), 'Scan completed successfully.', 201);
    }

    public function show(MeatScan $meatScan)
    {
        return $this->success(new MeatScanResource($meatScan), 'Meat scan fetched successfully.');
    }

    public function destroy(MeatScan $meatScan)
    {
        $meatScan->delete();

        return $this->success((object) [], 'Meat scan deleted successfully.');
    }
}

