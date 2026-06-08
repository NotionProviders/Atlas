<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'client_name',
        'notes',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Snapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    /** @return HasMany<DatabaseMapping, $this> */
    public function databaseMappings(): HasMany
    {
        return $this->hasMany(DatabaseMapping::class);
    }

    /** @return HasMany<CanonicalPlacement, $this> */
    public function canonicalPlacements(): HasMany
    {
        return $this->hasMany(CanonicalPlacement::class);
    }

    /** @return HasMany<ProjectTeamspace, $this> */
    public function teamspaces(): HasMany
    {
        return $this->hasMany(ProjectTeamspace::class)->orderBy('sort_order')->orderBy('name');
    }
}
