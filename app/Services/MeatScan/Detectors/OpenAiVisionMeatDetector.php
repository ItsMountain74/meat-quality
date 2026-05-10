<?php

namespace App\Services\MeatScan\Detectors;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Dto\MeatScanResultDto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiVisionMeatDetector implements MeatDetector
{
    public function detect(string $absoluteFilePath): MeatScanResultDto
    {
        $apiKey = (string) config('meatscan.openai.api_key');
        $endpoint = (string) config('meatscan.openai.endpoint');
        $model = (string) config('meatscan.openai.model');
        $timeout = (int) config('meatscan.openai.timeout_seconds', 45);
        $maxBytes = (int) config('meatscan.openai.max_image_bytes', 3_000_000);

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $bytes = @file_get_contents($absoluteFilePath);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }
        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException('Image too large for vision request.');
        }

        $mime = $this->guessMime($absoluteFilePath, $bytes);
        $dataUrl = 'data:'.$mime.';base64,'.base64_encode($bytes);

        $system = implode("\n", [
            'You are MeatScan, an expert food-safety visual inspector.',
            'Analyze the meat image and classify freshness.',
            'Return ONLY valid JSON. No markdown, no extra text.',
            'Allowed labels: "fresh", "spoiled", "uncertain".',
            'confidence is a number 0-100.',
            'recommendations is an array of 3-6 short strings.',
            'explanation is 1-3 sentences.',
            'If image quality is insufficient, use label "uncertain" with lower confidence.',
        ]);

        $user = 'Classify the meat freshness from this image.';

        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $user],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ],
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        $res = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (! $res->successful()) {
            throw new RuntimeException('Vision request failed: HTTP '.$res->status());
        }

        $content = data_get($res->json(), 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Vision response missing content.');
        }

        $parsed = json_decode($content, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('Vision response was not valid JSON.');
        }

        $label = strtolower((string) ($parsed['label'] ?? ''));
        if (! in_array($label, ['fresh', 'spoiled', 'uncertain'], true)) {
            $label = 'uncertain';
        }

        $confidence = (float) ($parsed['confidence'] ?? 0);
        $confidence = max(0, min(100, $confidence));

        $explanation = trim((string) ($parsed['explanation'] ?? ''));
        if ($explanation === '') {
            $explanation = 'No explanation provided by the model.';
        }

        $recsRaw = $parsed['recommendations'] ?? [];
        $recommendations = [];
        if (is_array($recsRaw)) {
            foreach ($recsRaw as $r) {
                if (! is_string($r)) continue;
                $r = trim($r);
                if ($r === '') continue;
                $recommendations[] = Str::limit($r, 140, '…');
            }
        }
        $recommendations = array_values(array_unique($recommendations));
        if (count($recommendations) < 3) {
            $recommendations = array_values(array_unique(array_merge($recommendations, $this->defaultRecommendations($label))));
        }
        $recommendations = array_slice($recommendations, 0, 6);

        return new MeatScanResultDto(
            label: $label,
            confidence: round($confidence, 2),
            explanation: $explanation,
            recommendations: $recommendations,
            scannedAt: CarbonImmutable::now(),
        );
    }

    private function guessMime(string $path, string $bytes): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * @return list<string>
     */
    private function defaultRecommendations(string $label): array
    {
        return match ($label) {
            'fresh' => [
                'Keep refrigerated at 0–4°C and consume soon.',
                'Store in an airtight container to prevent cross‑contamination.',
                'If odor, discoloration, or slime appears, do not consume.',
            ],
            'spoiled' => [
                'Do not consume; discard safely to avoid foodborne illness.',
                'Sanitize any surfaces/utensils that contacted the meat.',
                'If in doubt, follow local food safety guidance.',
            ],
            default => [
                'Retake the photo in bright, even lighting (no harsh shadows).',
                'Capture closer and keep the camera steady and in focus.',
                'If smell/texture is suspicious, do not consume.',
            ],
        };
    }
}

