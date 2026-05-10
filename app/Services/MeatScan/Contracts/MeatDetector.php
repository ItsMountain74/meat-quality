<?php

namespace App\Services\MeatScan\Contracts;

use App\Services\MeatScan\Dto\MeatScanResultDto;

interface MeatDetector
{
    public function detect(string $absoluteFilePath): MeatScanResultDto;
}

