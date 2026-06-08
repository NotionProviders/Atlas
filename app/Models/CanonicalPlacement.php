<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalPlacement extends Model
{
    protected $fillable = [
        'project_id',
        'canonical_database_id',
        'teamspace_node_id',
        'placement_notes',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function isAssignedToTeamspace(): bool
    {
        return $this->teamspace_node_id !== null;
    }
}
