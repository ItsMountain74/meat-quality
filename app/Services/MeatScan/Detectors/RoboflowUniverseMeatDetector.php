<?php

namespace App\Services\MeatScan\Detectors;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Dto\MeatScanResultDto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RoboflowUniverseMeatDetector implements MeatDetector
{
    public function detect(string $absoluteFilePath): MeatScanResultDto
    {
        $apiKey = (string) config('meatscan.roboflow.api_key');
        $model = (string) config('meatscan.roboflow.model');
        $version = (string) config('meatscan.roboflow.version', 1);
        $base = rtrim((string) config('meatscan.roboflow.endpoint_base', 'https://classify.roboflow.com'), '/');
        $timeout = (int) config('meatscan.roboflow.timeout_seconds', 45);
        $maxBytes = (int) config('meatscan.roboflow.max_image_bytes', 3_000_000);

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('Roboflow is not configured (ROBOFLOW_API_KEY / ROBOFLOW_MODEL).');
        }

        $bytes = @file_get_contents($absoluteFilePath);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }
        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException('Image too large for Roboflow request.');
        }

        $b64 = base64_encode($bytes);

        // Roboflow hosted classification endpoint
        $url = $base.'/'.rawurlencode($model).'/'.rawurlencode($version).'?api_key='.rawurlencode($apiKey);

        // Roboflow examples accept base64 image in request body with form content-type.
        $res = Http::timeout($timeout)
            ->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withBody($b64, 'application/x-www-form-urlencoded')
            ->post($url);

        if (! $res->successful()) {
            throw new RuntimeException('Roboflow request failed: HTTP '.$res->status());
        }

        $json = $res->json();
        if (! is_array($json)) {
            throw new RuntimeException('Roboflow returned an invalid response.');
        }

        // Classification responses commonly include "top" and "confidence" (0..1).
        $rawTop = (string) ($json['top'] ?? '');
        $rawConfidence = (float) ($json['confidence'] ?? 0);

        // Some responses only include predictions[].
        if ($rawTop === '' && isset($json['predictions']) && is_array($json['predictions']) && count($json['predictions']) > 0) {
            $best = $json['predictions'][0];
            if (is_array($best)) {
                $rawTop = (string) ($best['class'] ?? '');
                $rawConfidence = (float) ($best['confidence'] ?? $rawConfidence);
            }
        }

        $label = $this->normalizeLabel($rawTop);
        $confidencePct = max(0, min(100, $rawConfidence * 100));

        [$explanation, $recs] = $this->explain($label);

        return new MeatScanResultDto(
            label: $label,
            confidence: round($confidencePct, 2),
            explanation: $explanation,
            recommendations: $recs,
            scannedAt: CarbonImmutable::now(),
        );
    }

    private function normalizeLabel(string $raw): string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') return 'uncertain';

        // If the Roboflow model uses different class names, map common variants.
        if (str_contains($raw, 'fresh') || $raw === 'good' || $raw === 'ok') return 'fresh';
        if (str_contains($raw, 'spoil') || str_contains($raw, 'rotten') || $raw === 'bad') return 'spoiled';
        if (str_contains($raw, 'uncertain') || str_contains($raw, 'unknown') || str_contains($raw, 'unclear')) return 'uncertain';

        // Default: if class isn't recognized, treat as uncertain to avoid false safety claims.
        return 'uncertain';
    }

    /**
     * @return array{0:string,1:list<string>}
     */
    private function explain(string $label): array
    {
        return match ($label) {
            'fresh' => [
                'Roboflow model classification indicates the sample is likely fresh based on visual patterns in the image.',
                [
                    'Keep refrigerated at 0–4°C and consume soon.',
                    'Store in an airtight container to prevent cross‑contamination.',
                    'If odor or slime appears, do not consume.',
                ],
            ],
            'spoiled' => [
                'Roboflow model classification indicates higher spoilage risk based on visual patterns in the image.',
                [
                    'Do not consume; discard safely to avoid foodborne illness.',
                    'Sanitize any surfaces/utensils that contacted the meat.',
                    'If in doubt, follow local food safety guidance.',
                ],
            ],
            default => [
                'The model could not confidently classify the image, which may be due to lighting, focus, or occlusions.',
                [
                    'Retake the photo in bright, even lighting (no harsh shadows).',
                    'Capture closer and keep the camera steady and in focus.',
                    'If smell/texture is suspicious, do not consume.',
                ],
            ],
        };
    }
}

