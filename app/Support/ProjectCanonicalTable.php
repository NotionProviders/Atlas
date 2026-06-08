<?php

namespace App\Support;

use App\Models\CanonicalDatabase;
use App\Models\DatabaseMapping;
use App\Models\Project;

class ProjectCanonicalTable
{
    /** @return \Illuminate\Support\Collection<int, object{canonical: CanonicalDatabase, mappings: \Illuminate\Support\Collection<int, DatabaseMapping>}> */
    public static function rows(Project $project)
    {
        $mappingsByNodeId = $project->databaseMappings()
            ->with(['canonicalDatabase', 'atlasNode'])
            ->get()
            ->keyBy('atlas_node_id');

        $mappedCanonicalIds = $mappingsByNodeId->pluck('canonical_database_id')->unique();

        return CanonicalDatabase::query()
            ->whereIn('id', $mappedCanonicalIds)
            ->orderBy('is_custom')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (CanonicalDatabase $db) use ($mappingsByNodeId) {
                $mappings = $mappingsByNodeId
                    ->filter(fn (DatabaseMapping $m) => $m->canonical_database_id === $db->id)
                    ->values();

                return (object) [
                    'canonical' => $db,
                    'mappings' => $mappings,
                ];
            })
            ->values();
    }
}
