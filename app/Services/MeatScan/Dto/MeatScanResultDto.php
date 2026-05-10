<?php

namespace App\Services\MeatScan\Dto;

use Carbon\CarbonImmutable;

class MeatScanResultDto
{
    public function __construct(
        public readonly string $label,
        public readonly float $confidence,
        public readonly string $explanation,
        /** @var list<string> */
        public readonly array $recommendations,
        public readonly CarbonImmutable $scannedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'confidence' => $this->confidence,
            'explanation' => $this->explanation,
            'recommendations' => $this->recommendations,
            'scanned_at' => $this->scannedAt->toISOString(),
        ];
    }
}

