<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalDatabaseProperty extends Model
{
    protected $fillable = [
        'canonical_database_id',
        'name',
        'property_type',
        'options',
        'is_title',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_title' => 'boolean',
        ];
    }

    /** @return BelongsTo<CanonicalDatabase, $this> */
    public function canonicalDatabase(): BelongsTo
    {
        return $this->belongsTo(CanonicalDatabase::class);
    }
}
