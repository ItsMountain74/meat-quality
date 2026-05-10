<?php

namespace App\Services\MeatScan\Detectors;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Dto\MeatScanResultDto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RoboflowWorkflowMeatDetector implements MeatDetector
{
    public function detect(string $absoluteFilePath): MeatScanResultDto
    {
        $cfg = (array) config('meatscan.roboflow_workflow', []);

        $apiKey = (string) ($cfg['api_key'] ?? '');
        $workspace = (string) ($cfg['workspace'] ?? '');
        $workflow = (string) ($cfg['workflow'] ?? '');
        $base = rtrim((string) ($cfg['endpoint_base'] ?? 'https://serverless.roboflow.com'), '/');
        $timeout = (int) ($cfg['timeout_seconds'] ?? 45);
        $maxBytes = (int) ($cfg['max_image_bytes'] ?? 3_000_000);
        $imageInputType = strtolower((string) ($cfg['image_input_type'] ?? 'base64'));
        $extraInputsJson = (string) ($cfg['extra_inputs_json'] ?? '');
        $classes = $cfg['classes'] ?? null;
        $classesFormat = strtolower((string) ($cfg['classes_format'] ?? 'string')); // string | list
        $labelMap = is_array($cfg['label_map'] ?? null) ? (array) $cfg['label_map'] : [];

        if ($apiKey === '' || $workspace === '' || $workflow === '') {
            throw new RuntimeException('Roboflow workflow is not configured (ROBOFLOW_API_KEY / ROBOFLOW_WORKSPACE / ROBOFLOW_WORKFLOW).');
        }

        $bytes = @file_get_contents($absoluteFilePath);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }
        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException('Image too large for Roboflow request.');
        }

        $inputs = [
            'image' => [
                'type' => $imageInputType === 'url' ? 'url' : 'base64',
                'value' => $imageInputType === 'url'
                    ? $this->toPublicUrlOrFail($absoluteFilePath)
                    : base64_encode($bytes),
            ],
        ];

        if ($extraInputsJson !== '') {
            $extra = json_decode($extraInputsJson, true);
            if (is_array($extra)) {
                foreach ($extra as $k => $v) {
                    // Do not send null/empty values (causes manifest validation errors)
                    if ($v === null) continue;
                    if (is_string($v) && trim($v) === '') continue;
                    $inputs[$k] = $v;
                }
            }
        }

        // Optional: workflow parameter used by SAM step ("class_names") is often wired from an input named "classes".
        // Only send it if actually provided.
        if ($classes !== null) {
            if (is_string($classes)) {
                $classes = trim($classes);
            }

            if ($classesFormat === 'list') {
                if (is_string($classes)) {
                    $list = array_values(array_filter(array_map('trim', explode(',', $classes)), fn ($s) => $s !== ''));
                    if ($list) $inputs['classes'] = $list;
                } elseif (is_array($classes)) {
                    $list = array_values(array_filter(array_map(fn ($x) => is_string($x) ? trim($x) : '', $classes), fn ($s) => $s !== ''));
                    if ($list) $inputs['classes'] = $list;
                }
            } else {
                // string format
                if (is_array($classes)) {
                    $list = array_values(array_filter(array_map(fn ($x) => is_string($x) ? trim($x) : '', $classes), fn ($s) => $s !== ''));
                    if ($list) $inputs['classes'] = implode(',', $list);
                } elseif (is_string($classes) && $classes !== '') {
                    $inputs['classes'] = $classes;
                }
            }
        }

        $url = $base.'/'.rawurlencode($workspace).'/workflows/'.rawurlencode($workflow);

        $res = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'api_key' => $apiKey,
                'inputs' => $inputs,
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('Roboflow workflow request failed: HTTP '.$res->status());
        }

        $json = $res->json();
        if (!is_array($json)) {
            throw new RuntimeException('Roboflow workflow returned an invalid response.');
        }

        [$extractedLabel, $confidence] = $this->extractTopLabelAndConfidence($json);
        // Fail only when the response has no class/label at all. Do not use normalized
        // "uncertain" + confidence: unknown classes normalize to uncertain, and missing
        // confidence often parses as 0 even when a label was found.
        if (trim($extractedLabel) === '') {
            throw new RuntimeException('Roboflow workflow response did not contain a recognizable classification.');
        }

        $label = $this->normalizeLabel($extractedLabel, $labelMap);

        $confidencePct = max(0, min(100, $confidence <= 1 ? $confidence * 100 : $confidence));

        [$explanation, $recs] = $this->explain($label);

        // dd(555, $label, $confidencePct, $explanation, $recs);
        return new MeatScanResultDto(
            label: $label,
            confidence: round($confidencePct, 2),
            explanation: $explanation,
            recommendations: $recs,
            scannedAt: CarbonImmutable::now(),
        );
    }

    private function toPublicUrlOrFail(string $absoluteFilePath): string
    {
        throw new RuntimeException('ROBOFLOW_IMAGE_INPUT_TYPE=url requires a publicly accessible image URL. Use base64 instead.');
    }

    /**
     * Tries multiple common Roboflow/Workflow shapes and finally scans recursively.
     *
     * @return array{0:string,1:float} label, confidence
     */
    private function extractTopLabelAndConfidence(array $json): array
    {
        // Common classification shapes (from Roboflow inference responses)
        $top = data_get($json, 'top');
        $conf = data_get($json, 'confidence');
        if (is_string($top)) {
            return [$top, (float) $conf];
        }

        // Sometimes nested under outputs/results
        foreach (['outputs', 'result', 'results', 'data', 'output'] as $k) {
            $candidate = data_get($json, $k);
            if (is_array($candidate)) {
                $top = data_get($candidate, 'top');
                $conf = data_get($candidate, 'confidence');
                if (is_string($top)) {
                    return [$top, (float) $conf];
                }
            }
        }

        // Predictions array with class/confidence
        $preds = data_get($json, 'predictions');
        if (is_array($preds) && count($preds) > 0) {
            // List of objects [{class, confidence}, ...]
            if (array_is_list($preds)) {
                $first = $preds[0];
                if (is_array($first)) {
                    $cls = (string) ($first['class'] ?? ($first['label'] ?? ''));
                    $c = (float) ($first['confidence'] ?? ($first['score'] ?? 0));
                    if ($cls !== '') return [$cls, $c];
                }
            } else {
                // Dict of class => {confidence,...} or class => confidence
                $bestLabel = '';
                $bestConf = 0.0;
                foreach ($preds as $k => $v) {
                    $cls = is_string($k) ? $k : '';
                    $c = 0.0;
                    if (is_array($v)) {
                        $c = (float) ($v['confidence'] ?? ($v['score'] ?? 0));
                    } elseif (is_numeric($v)) {
                        $c = (float) $v;
                    }
                    if ($cls !== '' && $c > $bestConf) {
                        $bestConf = $c;
                        $bestLabel = $cls;
                    }
                }
                if ($bestLabel !== '') return [$bestLabel, $bestConf];
            }
        }

        // Recursive scan for best guess: any array item with {class,label} + {confidence,score}
        $flat = $this->flattenArrays($json);
        $bestLabel = '';
        $bestConf = 0.0;
        foreach ($flat as $node) {
            if (! is_array($node)) continue;
            $cls = $node['class'] ?? ($node['label'] ?? null);
            $c = $node['confidence'] ?? ($node['score'] ?? null);
            if (! is_string($cls)) continue;
            $c = is_numeric($c) ? (float) $c : 0.0;
            if ($c > $bestConf) {
                $bestConf = $c;
                $bestLabel = $cls;
            }
        }

        return [$bestLabel, $bestConf];
    }

    /**
     * @return list<array>
     */
    private function flattenArrays(array $json): array
    {
        $out = [];
        $stack = [$json];
        while ($stack) {
            $cur = array_pop($stack);
            if (! is_array($cur)) continue;
            $out[] = $cur;
            foreach ($cur as $v) {
                if (is_array($v)) $stack[] = $v;
            }
        }
        return $out;
    }

    private function normalizeLabel(string $raw, array $labelMap = []): string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') return 'uncertain';

        // Explicit mapping (case-insensitive exact match)
        foreach ($labelMap as $from => $to) {
            if (! is_string($from) || ! is_string($to)) continue;
            if ($raw === strtolower(trim($from))) {
                $to = strtolower(trim($to));
                if (in_array($to, ['fresh', 'spoiled', 'uncertain'], true)) return $to;
            }
        }

        if (str_contains($raw, 'fresh') || $raw === 'good' || $raw === 'ok') return 'fresh';
        if (str_contains($raw, 'spoil') || str_contains($raw, 'rotten') || $raw === 'bad') return 'spoiled';
        if (str_contains($raw, 'uncertain') || str_contains($raw, 'unknown') || str_contains($raw, 'unclear')) return 'uncertain';
        return 'uncertain';
    }

    /**
     * @return array{0:string,1:list<string>}
     */
    private function explain(string $label): array
    {
        return match ($label) {
            'fresh' => [
                'Roboflow workflow output indicates the sample is likely fresh based on visual patterns in the image.',
                [
                    'Keep refrigerated at 0–4°C and consume soon.',
                    'Store in an airtight container to prevent cross‑contamination.',
                    'If odor or slime appears, do not consume.',
                ],
            ],
            'spoiled' => [
                'Roboflow workflow output indicates higher spoilage risk based on visual patterns in the image.',
                [
                    'Do not consume; discard safely to avoid foodborne illness.',
                    'Sanitize any surfaces/utensils that contacted the meat.',
                    'If in doubt, follow local food safety guidance.',
                ],
            ],
            default => [
                'The workflow could not confidently classify the image, which may be due to lighting, focus, or occlusions.',
                [
                    'Retake the photo in bright, even lighting (no harsh shadows).',
                    'Capture closer and keep the camera steady and in focus.',
                    'If smell/texture is suspicious, do not consume.',
                ],
            ],
        };
    }
}

