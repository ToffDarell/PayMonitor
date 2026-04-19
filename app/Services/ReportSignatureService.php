<?php

declare(strict_types=1);

namespace App\Services;

class ReportSignatureService
{
    public function generateSignature(array $data): string
    {
        $payload = json_encode([
            'tenant_id' => (string) (tenant()?->id ?? ''),
            'tenant_name' => $data['coop_name'] ?? '',
            'generated_at' => $data['generated_at'] ?? '',
            'report_type' => $data['report_type'] ?? 'lending_report',
            'filters' => $this->normalizeArray($data['filters'] ?? []),
            'checksum' => hash('sha256', (string) json_encode(
                $this->normalizeArray($data['summary'] ?? []),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            )),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $signature = hash_hmac(
            'sha512',
            $payload === false ? '' : $payload,
            (string) config('app.key'),
        );

        $formatted = strtoupper(chunk_split($signature, 8, '-'));
        $formatted = rtrim($formatted, '-');

        return 'PM-'.substr($formatted, 0, 63);
    }

    public function verifySignature(string $signature, array $data): bool
    {
        return hash_equals($this->generateSignature($data), $signature);
    }

    public function generateVerificationCode(string $signature): string
    {
        $hash = strtoupper(substr(hash('md5', $signature), 0, 12));

        return implode('-', str_split($hash, 4));
    }

    private function normalizeArray(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalizeArray($item);
            }
        }

        if ($this->isAssociative($value)) {
            ksort($value);
        }

        return $value;
    }

    private function isAssociative(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}
