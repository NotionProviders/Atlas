<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalDatabase extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name',
        'slug',
        'notion_export_id',
        'template_tag',
        'is_lookup',
        'is_custom',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_lookup' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    /** @return HasMany<CanonicalDatabaseProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(CanonicalDatabaseProperty::class)->orderBy('sort_order');
    }

    /** @return HasMany<DatabaseMapping, $this> */
    public function databaseMappings(): HasMany
    {
        return $this->hasMany(DatabaseMapping::class);
    }

    /** @return HasMany<CanonicalPlacement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(CanonicalPlacement::class);
    }
}
