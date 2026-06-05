<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nano Banana (Gemini 2.5 Flash Image) via KIE API — pola async task + poll.
 *
 *  1. POST {base}/api/v1/jobs/createTask  (Bearer)  → data.taskId
 *  2. GET  {base}/api/v1/jobs/recordInfo?taskId=... → data.state + data.resultJson
 *  3. resultJson (string) → resultUrls[0] = URL gambar
 *  4. download URL → binary bytes
 *
 * Gambar dari API KIE TIDAK ada logo Gemini terlihat (hanya SynthID invisible).
 */
class KieImageService
{
    public function isActive(): bool
    {
        return ! empty(config('services.kie.api_key'));
    }

    private function base(): string
    {
        return rtrim(config('services.kie.base_url', 'https://api.kie.ai'), '/');
    }

    /**
     * Generate gambar dari prompt. Return binary bytes (PNG) atau null kalau gagal.
     */
    public function generateImage(string $prompt, string $aspect = '16:9'): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        $token = config('services.kie.api_key');
        $model = config('services.kie.image_model', 'google/nano-banana');

        // 1. Create task
        try {
            $create = Http::timeout(30)
                ->withToken($token)
                ->asJson()
                ->post($this->base().'/api/v1/jobs/createTask', [
                    'model' => $model,
                    'input' => [
                        'prompt'        => mb_substr($prompt, 0, 5000),
                        'output_format' => 'png',
                        'aspect_ratio'  => $aspect,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('[KIE] createTask exception', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $create->successful()) {
            Log::warning('[KIE] createTask gagal', [
                'status' => $create->status(),
                'body'   => $create->json() ?? $create->body(),
            ]);
            return null;
        }

        $taskId = $create->json('data.taskId');
        if (! $taskId) {
            Log::warning('[KIE] taskId kosong', ['body' => $create->json()]);
            return null;
        }

        // 2. Poll recordInfo sampai state success/fail (max ~120 dtk)
        $deadline = time() + 120;
        $resultUrl = null;

        while (time() < $deadline) {
            sleep(4);

            try {
                $info = Http::timeout(30)
                    ->withToken($token)
                    ->get($this->base().'/api/v1/jobs/recordInfo', ['taskId' => $taskId]);
            } catch (\Throwable $e) {
                continue; // network blip — coba lagi
            }

            if (! $info->successful()) {
                continue;
            }

            $state = strtolower((string) $info->json('data.state'));

            if (in_array($state, ['success', 'completed', 'succeeded'], true)) {
                $resultJson = $info->json('data.resultJson');
                $resultUrl = $this->extractResultUrl($resultJson);
                break;
            }

            if (in_array($state, ['fail', 'failed', 'error'], true)) {
                Log::warning('[KIE] task gagal', [
                    'taskId'  => $taskId,
                    'failMsg' => $info->json('data.failMsg'),
                ]);
                return null;
            }
            // else: waiting/queuing/generating → ulangi
        }

        if (! $resultUrl) {
            Log::warning('[KIE] timeout / tidak ada resultUrl', ['taskId' => $taskId]);
            return null;
        }

        // 3. Download gambar
        try {
            $img = Http::timeout(60)->get($resultUrl);
            if ($img->successful()) {
                return $img->body();
            }
            Log::warning('[KIE] download gambar gagal', ['status' => $img->status(), 'url' => $resultUrl]);
        } catch (\Throwable $e) {
            Log::warning('[KIE] download exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * resultJson bisa berupa string JSON atau array. Ambil resultUrls[0].
     */
    private function extractResultUrl($resultJson): ?string
    {
        $data = is_string($resultJson) ? json_decode($resultJson, true) : $resultJson;
        if (! is_array($data)) {
            return null;
        }

        $urls = $data['resultUrls'] ?? $data['result_urls'] ?? $data['urls'] ?? null;
        if (is_array($urls) && ! empty($urls)) {
            return (string) $urls[0];
        }
        // fallback: kadang single url
        if (! empty($data['resultUrl'])) {
            return (string) $data['resultUrl'];
        }

        return null;
    }
}
