<?php

namespace App\Services\MeatScan\Detectors;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Dto\MeatScanResultDto;
use Carbon\CarbonImmutable;

class MockMeatDetector implements MeatDetector
{
    public function detect(string $absoluteFilePath): MeatScanResultDto
    {
        $hash = sha1_file($absoluteFilePath) ?: sha1($absoluteFilePath);
        $n = hexdec(substr($hash, 0, 6)) % 1000; // 0..999 stable per file

        // 10% uncertain, otherwise split between fresh/spoiled
        $label = match (true) {
            $n < 100 => 'uncertain',
            $n < 550 => 'fresh',
            default => 'spoiled',
        };

        $confidence = match ($label) {
            'fresh' => 90 + ($n % 90) / 10,      // 90.0 - 98.9
            'spoiled' => 85 + ($n % 120) / 10,   // 85.0 - 96.9
            default => 60 + ($n % 250) / 10,     // 60.0 - 84.9
        };

        [$explanation, $recs] = $this->explain($label);

        return new MeatScanResultDto(
            label: $label,
            confidence: round($confidence, 2),
            explanation: $explanation,
            recommendations: $recs,
            scannedAt: CarbonImmutable::now(),
        );
    }

    /**
     * @return array{0:string,1:list<string>}
     */
    private function explain(string $label): array
    {
        return match ($label) {
            'fresh' => [
                'Visual indicators suggest normal color distribution and texture consistency consistent with fresh meat.',
                [
                    'Keep refrigerated at 0–4°C and consume soon.',
                    'Store in an airtight container to reduce odor transfer.',
                    'If odor or slime appears, rescan or discard.',
                ],
            ],
            'spoiled' => [
                'Detected patterns consistent with spoilage risk (discoloration and surface irregularities).',
                [
                    'Do not consume; discard safely to avoid foodborne illness.',
                    'Sanitize any surfaces/utensils that contacted the meat.',
                    'If in doubt, follow local food safety guidance.',
                ],
            ],
            default => [
                'The image quality or lighting may be insufficient for a confident classification.',
                [
                    'Retake the photo in brighter, even lighting (no harsh shadows).',
                    'Capture the surface closer and keep the camera steady.',
                    'If smell/texture is suspicious, do not consume.',
                ],
            ],
        };
    }
}

