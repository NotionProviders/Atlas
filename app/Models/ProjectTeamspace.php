<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTeamspace extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'sort_order',
        'notes',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<DatabaseMapping, $this> */
    public function databaseMappings(): HasMany
    {
        return $this->hasMany(DatabaseMapping::class);
    }
}
