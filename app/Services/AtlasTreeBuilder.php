<?php

namespace App\Services;

use App\Enums\NodeKind;
use App\Models\AtlasNode;
use App\Models\Snapshot;

class AtlasTreeBuilder
{
    /** @return array<string, string> */
    public function defaultPalette(): array
    {
        return [
            'core' => '#ffd66b',
            'blue' => '#5eb0ff',
            'amber' => '#f5a623',
            'violet' => '#c094ff',
            'green' => '#4aebb0',
            'pink' => '#ff85a0',
            'cyan' => '#45e0f5',
            'red' => '#ff7080',
            'orange' => '#ffb05a',
        ];
    }

    /** @return array{palette: array<string, string>, tree: array<string, mixed>, legend: list<array{0: string, 1: string}>} */
    public function build(Snapshot $snapshot): array
    {
        if ($snapshot->tree_json && $snapshot->palette && $snapshot->legend) {
            return [
                'palette' => $snapshot->palette,
                'tree' => $snapshot->tree_json,
                'legend' => $snapshot->legend,
            ];
        }

        $nodes = $snapshot->nodes()->with('children')->get();
        $roots = $nodes->whereNull('parent_id')->sortBy('sort_order');

        $tree = $this->nestCollection($roots, $nodes);

        if ($tree === []) {
            $tree = [
                'label' => $snapshot->project->name,
                'kind' => 'core',
                'c' => '#ffd66b',
                'id' => 'workspace',
                'note' => 'No workspace data imported yet.',
                'children' => [],
            ];
        } elseif (count($tree) === 1) {
            $tree = $tree[0];
        } else {
            $tree = [
                'label' => $snapshot->project->name,
                'kind' => 'core',
                'c' => '#ffd66b',
                'id' => 'workspace',
                'children' => $tree,
            ];
        }

        $palette = $snapshot->palette ?? $this->defaultPalette();
        $legend = $snapshot->legend ?? $this->legendFromNodes($nodes);

        return compact('palette', 'tree', 'legend');
    }

    public function buildConfigScript(Snapshot $snapshot): string
    {
        $data = $this->build($snapshot);

        return 'window.ATLAS_CONFIG='.json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ).';';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AtlasNode>  $roots
     * @param  \Illuminate\Support\Collection<int, AtlasNode>  $all
     * @return list<array<string, mixed>>
     */
    private function nestCollection($roots, $all): array
    {
        return $roots->map(fn (AtlasNode $node) => $this->nodeToTree($node, $all))->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AtlasNode>  $all
     * @return array<string, mixed>
     */
    private function nodeToTree(AtlasNode $node, $all): array
    {
        $children = $all->where('parent_id', $node->id)->sortBy('sort_order');

        $tree = [
            'label' => $node->label,
            'kind' => $this->mapKind($node->kind),
        ];

        if ($node->color) {
            $tree['c'] = $node->color;
        }

        if ($node->notion_page_id) {
            $tree['id'] = str_replace('-', '', $node->notion_page_id);
        } elseif ($node->meta['id'] ?? null) {
            $tree['id'] = $node->meta['id'];
        }

        if ($node->note) {
            $tree['note'] = $node->note;
        }

        if ($node->meta['portal'] ?? null) {
            $tree['portal'] = $node->meta['portal'];
        }

        if ($children->isNotEmpty()) {
            $tree['children'] = $this->nestCollection($children, $all);
        }

        if ($node->notion_page_id || $node->notion_data_source_id) {
            $tree['meta'] = array_filter([
                'notion_page_id' => $node->notion_page_id,
                'notion_data_source_id' => $node->notion_data_source_id,
            ]);
        }

        return $tree;
    }

    private function mapKind(NodeKind $kind): string
    {
        return match ($kind) {
            NodeKind::Workspace => 'core',
            NodeKind::Teamspace => 'domain',
            NodeKind::Page => 'sub',
            NodeKind::Database => 'domain',
            NodeKind::Row => 'leaf',
            NodeKind::Property => 'leaf',
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AtlasNode>  $nodes
     * @return list<array{0: string, 1: string}>
     */
    private function legendFromNodes($nodes): array
    {
        return $nodes
            ->filter(fn (AtlasNode $n) => $n->kind === NodeKind::Teamspace)
            ->map(fn (AtlasNode $n) => [
                $n->meta['legend_id'] ?? ('ts-'.$n->id),
                $n->label,
            ])
            ->values()
            ->all();
    }
}
