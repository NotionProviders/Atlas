<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseMapping extends Model
{
    protected $fillable = [
        'project_id',
        'atlas_node_id',
        'canonical_database_id',
        'notes',
        'migration_details',
        'teamspace_node_id',
        'placement_notes',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<AtlasNode, $this> */
    public function atlasNode(): BelongsTo
    {
        return $this->belongsTo(AtlasNode::class);
    }

    /** @return BelongsTo<CanonicalDatabase, $this> */
    public function canonicalDatabase(): BelongsTo
    {
        return $this->belongsTo(CanonicalDatabase::class);
    }

    /** @return BelongsTo<AtlasNode, $this> */
    public function teamspaceNode(): BelongsTo
    {
        return $this->belongsTo(AtlasNode::class, 'teamspace_node_id');
    }

    /** @return HasMany<DatabaseMappingProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(DatabaseMappingProperty::class)->orderBy('sort_order');
    }

    public function isAssignedToTeamspace(): bool
    {
        return $this->teamspace_node_id !== null;
    }
}
