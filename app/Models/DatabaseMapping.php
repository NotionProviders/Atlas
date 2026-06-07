<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseMapping extends Model
{
    protected $fillable = [
        'project_id',
        'atlas_node_id',
        'canonical_database_id',
        'notes',
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
}
