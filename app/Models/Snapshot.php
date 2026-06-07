<?php

namespace App\Models;

use App\Enums\SnapshotType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Snapshot extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'imported_at',
        'meta',
        'tree_json',
        'palette',
        'legend',
    ];

    protected function casts(): array
    {
        return [
            'type' => SnapshotType::class,
            'imported_at' => 'datetime',
            'meta' => 'array',
            'tree_json' => 'array',
            'palette' => 'array',
            'legend' => 'array',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<AtlasNode, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(AtlasNode::class)->orderBy('sort_order');
    }

    public function isEmpty(): bool
    {
        return $this->imported_at === null && $this->nodes()->count() === 0;
    }
}
