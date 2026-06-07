<?php

namespace App\Models;

use App\Enums\NodeKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtlasNode extends Model
{
    protected $fillable = [
        'snapshot_id',
        'parent_id',
        'label',
        'kind',
        'notion_page_id',
        'notion_data_source_id',
        'color',
        'note',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'kind' => NodeKind::class,
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Snapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }

    /** @return BelongsTo<AtlasNode, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<AtlasNode, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<DatabaseProperty, $this> */
    public function databaseProperties(): HasMany
    {
        return $this->hasMany(DatabaseProperty::class)->orderBy('sort_order');
    }
}
