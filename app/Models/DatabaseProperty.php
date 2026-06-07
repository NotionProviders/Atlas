<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseProperty extends Model
{
    protected $fillable = [
        'atlas_node_id',
        'name',
        'property_type',
        'options',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    /** @return BelongsTo<AtlasNode, $this> */
    public function atlasNode(): BelongsTo
    {
        return $this->belongsTo(AtlasNode::class);
    }
}
