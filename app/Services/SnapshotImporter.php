<?php

namespace App\Services;

use App\Enums\NodeKind;
use App\Enums\SnapshotType;
use App\Models\AtlasNode;
use App\Models\DatabaseProperty;
use App\Models\Project;
use App\Models\Snapshot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SnapshotImporter
{
    public function __construct(
        private readonly AtlasTreeBuilder $treeBuilder,
    ) {}

    public function import(Project $project, SnapshotType $type, string $jsonPath): Snapshot
    {
        if (! is_readable($jsonPath)) {
            throw new InvalidArgumentException("Cannot read JSON file: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        return $this->importPayload($project, $type, $payload);
    }

    /** @param  array<string, mixed>  $payload */
    public function importPayload(Project $project, SnapshotType $type, array $payload): Snapshot
    {
        return DB::transaction(function () use ($project, $type, $payload) {
            $snapshot = Snapshot::query()->firstOrCreate(
                ['project_id' => $project->id, 'type' => $type],
                ['meta' => []],
            );

            $snapshot->nodes()->each(function (AtlasNode $node): void {
                $node->databaseProperties()->delete();
            });
            $snapshot->nodes()->delete();

            if (isset($payload['tree'])) {
                $this->importAtlasBundle($snapshot, $payload);
            } elseif (isset($payload['nodes'])) {
                $this->importNormalizedNodes($snapshot, $payload['nodes']);
            } else {
                throw new InvalidArgumentException('JSON must contain "tree" (atlas bundle) or "nodes" (normalized list).');
            }

            $built = $this->treeBuilder->build($snapshot->fresh(['nodes']));

            $snapshot->update([
                'imported_at' => now(),
                'palette' => $built['palette'],
                'legend' => $built['legend'],
                'tree_json' => $built['tree'],
                'meta' => array_merge($snapshot->meta ?? [], [
                    'import_source' => isset($payload['tree']) ? 'atlas_bundle' : 'normalized_nodes',
                ]),
            ]);

            return $snapshot->fresh(['nodes.databaseProperties']);
        });
    }

    /** @param  array<string, mixed>  $payload */
    private function importAtlasBundle(Snapshot $snapshot, array $payload): void
    {
        $palette = $payload['palette'] ?? null;
        $legend = $payload['legend'] ?? null;

        if ($palette) {
            $snapshot->palette = $palette;
        }
        if ($legend) {
            $snapshot->legend = $legend;
        }

        $this->importTreeNode($snapshot, $payload['tree'], null, 0, NodeKind::Workspace);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function importTreeNode(
        Snapshot $snapshot,
        array $node,
        ?int $parentId,
        int $sortOrder,
        NodeKind $defaultKind,
    ): AtlasNode {
        $kind = $this->inferKind($node, $defaultKind);

        $atlasNode = $snapshot->nodes()->create([
            'parent_id' => $parentId,
            'label' => $node['label'] ?? 'Untitled',
            'kind' => $kind,
            'notion_page_id' => $node['meta']['notion_page_id'] ?? ($node['notion_page_id'] ?? null),
            'notion_data_source_id' => $node['meta']['notion_data_source_id'] ?? ($node['notion_data_source_id'] ?? null),
            'color' => $node['c'] ?? null,
            'note' => $node['note'] ?? null,
            'sort_order' => $sortOrder,
            'meta' => array_filter([
                'id' => $node['id'] ?? null,
                'portal' => $node['portal'] ?? null,
                'legend_id' => $node['legend_id'] ?? null,
            ]),
        ]);

        foreach ($node['properties'] ?? [] as $i => $property) {
            if (is_string($property)) {
                $atlasNode->databaseProperties()->create([
                    'name' => $property,
                    'property_type' => 'unknown',
                    'sort_order' => $i,
                ]);
            } elseif (is_array($property)) {
                $atlasNode->databaseProperties()->create([
                    'name' => $property['name'] ?? 'Property',
                    'property_type' => $property['type'] ?? $property['property_type'] ?? 'unknown',
                    'options' => $property['options'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        foreach ($node['children'] ?? [] as $i => $child) {
            if (! is_array($child)) {
                continue;
            }
            $this->importTreeNode($snapshot, $child, $atlasNode->id, $i, $this->childKind($kind));
        }

        return $atlasNode;
    }

    /** @param  list<array<string, mixed>>  $nodes */
    private function importNormalizedNodes(Snapshot $snapshot, array $nodes): void
    {
        $idMap = [];

        foreach ($nodes as $i => $row) {
            $atlasNode = $snapshot->nodes()->create([
                'parent_id' => null,
                'label' => $row['label'] ?? 'Untitled',
                'kind' => NodeKind::from($row['kind'] ?? 'page'),
                'notion_page_id' => $row['notion_page_id'] ?? null,
                'notion_data_source_id' => $row['notion_data_source_id'] ?? null,
                'color' => $row['color'] ?? null,
                'note' => $row['note'] ?? null,
                'sort_order' => $row['sort_order'] ?? $i,
                'meta' => $row['meta'] ?? null,
            ]);
            $idMap[$row['ref'] ?? $row['id'] ?? $i] = $atlasNode->id;

            foreach ($row['properties'] ?? [] as $j => $property) {
                $atlasNode->databaseProperties()->create([
                    'name' => is_array($property) ? ($property['name'] ?? 'Property') : $property,
                    'property_type' => is_array($property) ? ($property['type'] ?? 'unknown') : 'unknown',
                    'options' => is_array($property) ? ($property['options'] ?? null) : null,
                    'sort_order' => $j,
                ]);
            }
        }

        foreach ($nodes as $i => $row) {
            $parentRef = $row['parent_ref'] ?? $row['parent_id'] ?? null;
            if ($parentRef === null) {
                continue;
            }

            $nodeId = $idMap[$row['ref'] ?? $row['id'] ?? $i] ?? null;
            $parentNodeId = $idMap[$parentRef] ?? (is_numeric($parentRef) ? (int) $parentRef : null);

            if ($nodeId && $parentNodeId) {
                AtlasNode::query()->whereKey($nodeId)->update(['parent_id' => $parentNodeId]);
            }
        }
    }

    /** @param  array<string, mixed>  $node */
    private function inferKind(array $node, NodeKind $defaultKind): NodeKind
    {
        if (isset($node['kind']) && in_array($node['kind'], array_column(NodeKind::cases(), 'value'), true)) {
            return NodeKind::from($node['kind']);
        }

        return match ($node['kind'] ?? null) {
            'core' => NodeKind::Workspace,
            'domain' => $defaultKind === NodeKind::Workspace ? NodeKind::Teamspace : NodeKind::Database,
            'sub' => NodeKind::Page,
            'leaf', 'item' => NodeKind::Row,
            default => $defaultKind,
        };
    }

    private function childKind(NodeKind $parent): NodeKind
    {
        return match ($parent) {
            NodeKind::Workspace => NodeKind::Teamspace,
            NodeKind::Teamspace => NodeKind::Page,
            NodeKind::Page => NodeKind::Page,
            NodeKind::Database => NodeKind::Row,
            default => NodeKind::Page,
        };
    }
}
