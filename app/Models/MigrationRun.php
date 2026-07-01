<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One migration run — a workspace pulled out of a source tool by the extension
 * and exported to a markdown tree.
 *
 * @property int $id
 * @property string $slug
 * @property string $source
 * @property string $name
 * @property ?string $source_ref
 * @property string $status
 * @property int $total
 * @property int $succeeded
 * @property int $failed
 * @property int $attachments
 * @property ?array $options
 * @property ?array $report
 * @property ?int $token_id
 * @property ?Carbon $started_at
 * @property ?Carbon $finished_at
 */
class MigrationRun extends Model
{
    protected $fillable = [
        'slug', 'source', 'name', 'source_ref', 'status',
        'total', 'succeeded', 'failed', 'attachments',
        'options', 'report', 'token_id', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'report' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MigrationItem::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(ExtensionToken::class, 'token_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'canceled'], true);
    }

    public function sourceLabel(): string
    {
        return (string) config("migration.sources.{$this->source}.label", ucfirst($this->source));
    }

    /**
     * Percent complete for the progress bar (0–100), based on processed vs total.
     */
    public function progressPercent(): int
    {
        if ($this->isTerminal()) {
            return 100;
        }

        if ($this->total <= 0) {
            return 0;
        }

        $done = min($this->succeeded + $this->failed + $this->attachments, $this->total);

        return (int) floor($done / $this->total * 100);
    }

    /**
     * Compact status payload for polling (console + extension).
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'slug' => $this->slug,
            'source' => $this->source,
            'sourceLabel' => $this->sourceLabel(),
            'name' => $this->name,
            'sourceRef' => $this->source_ref,
            'status' => $this->status,
            'total' => $this->total,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'attachments' => $this->attachments,
            'progress' => $this->progressPercent(),
            'startedAt' => $this->started_at?->toIso8601String(),
            'finishedAt' => $this->finished_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Full payload including the punch-list report (for the detail view).
     *
     * @return array<string, mixed>
     */
    public function detail(): array
    {
        return $this->summary() + ['report' => $this->report];
    }
}
