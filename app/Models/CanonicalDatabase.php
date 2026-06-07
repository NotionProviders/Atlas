<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalDatabase extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

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
}
