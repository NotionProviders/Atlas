<?php

namespace App\Services\Migration;

/**
 * The catalogue of source tools Atlas can migrate out of. Backed entirely by
 * config/migration.php, so adding a connector is a config entry plus a matching
 * extension adapter — no code change here.
 */
class SourceRegistry
{
    /**
     * @return array<string, array{key: string, label: string, blurb: string, host: string, status: string}>
     */
    public function all(): array
    {
        return (array) config('migration.sources', []);
    }

    /**
     * Connectors that can actually be run today.
     *
     * @return array<string, array{key: string, label: string, blurb: string, host: string, status: string}>
     */
    public function ready(): array
    {
        return array_filter($this->all(), fn ($s) => ($s['status'] ?? null) === 'ready');
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function isReady(string $key): bool
    {
        return ($this->all()[$key]['status'] ?? null) === 'ready';
    }

    /**
     * @return array{key: string, label: string, blurb: string, host: string, status: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function label(string $key): string
    {
        return $this->all()[$key]['label'] ?? ucfirst($key);
    }
}
