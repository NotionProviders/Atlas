<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseMappingProperty extends Model
{
    public const SOURCE_CANONICAL = 'canonical';

    public const SOURCE_CLIENT = 'client';

    public const SOURCE_CUSTOM = 'custom';

    protected $fillable = [
        'database_mapping_id',
        'name',
        'property_type',
        'source',
        'is_locked',
        'source_canonical_property_id',
        'source_client_property_id',
        'merge_notes',
        'is_title',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_title' => 'boolean',
        ];
    }

    /** @return BelongsTo<DatabaseMapping, $this> */
    public function databaseMapping(): BelongsTo
    {
        return $this->belongsTo(DatabaseMapping::class);
    }

    /** @return BelongsTo<CanonicalDatabaseProperty, $this> */
    public function sourceCanonicalProperty(): BelongsTo
    {
        return $this->belongsTo(CanonicalDatabaseProperty::class, 'source_canonical_property_id');
    }

    /** @return BelongsTo<DatabaseProperty, $this> */
    public function sourceClientProperty(): BelongsTo
    {
        return $this->belongsTo(DatabaseProperty::class, 'source_client_property_id');
    }
}
