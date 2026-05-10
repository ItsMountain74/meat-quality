<?php

namespace App\Services\MeatScan\Detectors;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Dto\MeatScanResultDto;
use Throwable;

class FailoverMeatDetector implements MeatDetector
{
    public function __construct(
        private readonly MeatDetector $primary,
        private readonly MeatDetector $fallback,
    ) {
    }

    public function detect(string $absoluteFilePath): MeatScanResultDto
    {
        try {
            return $this->primary->detect($absoluteFilePath);
        } catch (Throwable) {
            return $this->fallback->detect($absoluteFilePath);
        }
    }
}

