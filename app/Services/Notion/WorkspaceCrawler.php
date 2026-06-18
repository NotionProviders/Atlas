<?php

namespace App\Services\Notion;

use Closure;

/**
 * Recursively maps a Notion workspace into the Atlas tree format.
 *
 * The walk mirrors how a person explores a workspace:
 *   teamspace → its pages (recursively) → then its databases
 *   database  → each row is a page → recurse into that page's subpages,
 *               then into any databases nested inside it
 *
 * At every level pages are expanded before databases, so the resulting tree
 * reads "pages first, then databases", all the way down. A visited set keeps
 * the crawl finite when the same object is referenced from several places.
 */
class WorkspaceCrawler
{
    /** Container blocks that may hold nested child pages/databases. */
    private const CONTAINER_TYPES = [
        'column_list', 'column', 'toggle', 'synced_block',
        'callout', 'quote', 'bulleted_list_item', 'numbered_list_item',
        'to_do', 'template',
    ];

    private const COLORS = [
        'workspace' => '#ffd66b',
        'teamspace' => '#c094ff',
        'database' => '#5eb0ff',
        'page' => '#4aebb0',
        'row' => '#ffb05a',
    ];

    /** @var array<string, true> */
    private array $visited = [];

    private int $nodeCount = 0;

    private int $maxDepth;

    private int $maxNodes;

    private Closure $progress;

    public function __construct(private readonly NotionClient $client)
    {
        $this->maxDepth = (int) config('notion.crawl.max_depth', 12);
        $this->maxNodes = (int) config('notion.crawl.max_nodes', 20000);
        $this->progress = fn (string $message) => null;
    }

    /** Register a callback invoked with a short status line as the crawl runs. */
    public function onProgress(Closure $callback): self
    {
        $this->progress = $callback;

        return $this;
    }

    /**
     * Build a full Atlas map for a workspace.
     *
     * @param  string  $workspaceName  Human label for the root node.
     * @param  array<int, array{id: string, name?: string, type?: string}>  $teamspaces
     *                                                                                   Teamspace roots (from MCP get-teams) or any seed roots. Each is a
     *                                                                                   page/database id, optionally named. The REST API cannot list
     *                                                                                   teamspaces itself, hence this seed.
     * @return array{palette: array, tree: array, legend: array, meta: array}
     */
    public function crawl(string $workspaceName, array $teamspaces): array
    {
        $this->visited = [];
        $this->nodeCount = 0;

        $children = [];
        foreach ($teamspaces as $seed) {
            $id = $this->normalizeId($seed['id'] ?? '');
            if ($id === '') {
                continue;
            }

            ($this->progress)('Teamspace: '.($seed['name'] ?? $id));

            $type = $seed['type'] ?? 'page';
            $node = $type === 'database'
                ? $this->crawlDatabase($id, 1)
                : $this->crawlPage($id, 1);

            if ($node === null) {
                continue;
            }

            // Promote a seeded root to a teamspace node.
            $node['kind'] = 'domain';
            $node['c'] = self::COLORS['teamspace'];
            $node['t'] = 'teamspace';
            if (isset($seed['name'])) {
                $node['label'] = $seed['name'];
            }
            $node['note'] = 'Teamspace · '.count($node['children'] ?? []).' top-level items';
            $children[] = $node;
        }

        $tree = [
            'label' => $workspaceName,
            'kind' => 'core',
            'c' => self::COLORS['workspace'],
            'id' => 'workspace',
            't' => 'workspace',
            'note' => 'Notion workspace mapped from teamspaces down through every page and database.',
            'children' => $children,
        ];

        return [
            'palette' => [
                'core' => '#ffd66b', 'blue' => '#5eb0ff', 'amber' => '#f5a623',
                'violet' => '#c094ff', 'green' => '#4aebb0', 'pink' => '#ff85a0',
                'cyan' => '#45e0f5', 'red' => '#ff7080', 'orange' => '#ffb05a',
            ],
            'tree' => $tree,
            'legend' => [
                ['teamspace', 'Teamspaces'],
                ['database', 'Databases'],
                ['page', 'Pages'],
                ['row', 'Database pages'],
            ],
            'meta' => [
                'name' => $workspaceName,
                'source' => 'notion-crawl',
                'generatedAt' => now()->toIso8601String(),
                'nodeCount' => $this->nodeCount,
                'teamspaces' => count($children),
            ],
        ];
    }

    private function crawlPage(string $id, int $depth): ?array
    {
        if (! $this->enter($id, $depth)) {
            return null;
        }

        $page = $this->client->retrievePage($id);
        if ($page === null) {
            return null;
        }

        $label = $this->pageTitle($page);
        ($this->progress)(str_repeat('  ', min($depth, 8))."page · {$label}");

        [$childPages, $childDatabases] = $this->scanBlocks($id, $depth);

        $children = [];
        // Pages first...
        foreach ($childPages as $childId) {
            if ($node = $this->crawlPage($childId, $depth + 1)) {
                $children[] = $node;
            }
        }
        // ...then databases.
        foreach ($childDatabases as $childId) {
            if ($node = $this->crawlDatabase($childId, $depth + 1)) {
                $children[] = $node;
            }
        }

        return $this->makeNode($label, $children, [
            'type' => 'page',
            'url' => $page['url'] ?? null,
            'note' => $children === [] ? 'Page' : 'Page · '.count($children).' inside',
            'color' => self::COLORS['page'],
        ]);
    }

    private function crawlDatabase(string $id, int $depth): ?array
    {
        if (! $this->enter($id, $depth)) {
            return null;
        }

        $database = $this->client->retrieveDatabase($id);
        if ($database === null) {
            return null;
        }

        $label = $this->richTextPlain($database['title'] ?? []);
        if ($label === '') {
            $label = 'Untitled database';
        }
        ($this->progress)(str_repeat('  ', min($depth, 8))."db · {$label}");

        $rows = $this->client->queryDatabase($id);

        $children = [];
        foreach ($rows as $row) {
            $rowId = $this->normalizeId($row['id'] ?? '');
            if ($rowId === '' || isset($this->visited[$rowId])) {
                continue;
            }
            if ($node = $this->crawlPage($rowId, $depth + 1)) {
                // A database row is itself a page; tint it so rows read distinctly.
                $node['c'] = self::COLORS['row'];
                $node['t'] = 'row';
                $children[] = $node;
            }
        }

        return $this->makeNode($label, $children, [
            'type' => 'database',
            'url' => $database['url'] ?? null,
            'note' => 'Database · '.count($children).' pages',
            'color' => self::COLORS['database'],
            'kind' => 'sub',
        ]);
    }

    /**
     * Recursively walk a page's block tree and collect the ids of its child
     * pages and child databases (including ones nested inside columns,
     * toggles, callouts, etc.). Does not descend into child pages — those are
     * crawled on their own.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function scanBlocks(string $blockId, int $depth): array
    {
        $pages = [];
        $databases = [];

        foreach ($this->client->blockChildren($blockId) as $block) {
            $type = $block['type'] ?? '';
            $id = $this->normalizeId($block['id'] ?? '');
            if ($id === '') {
                continue;
            }

            if ($type === 'child_page') {
                $pages[] = $id;
            } elseif ($type === 'child_database') {
                $databases[] = $id;
            } elseif (($block['has_children'] ?? false) && in_array($type, self::CONTAINER_TYPES, true)) {
                [$nestedPages, $nestedDatabases] = $this->scanBlocks($id, $depth);
                $pages = array_merge($pages, $nestedPages);
                $databases = array_merge($databases, $nestedDatabases);
            }
        }

        return [$pages, $databases];
    }

    /**
     * Register an id as visited and enforce depth/node guards.
     */
    private function enter(string $id, int $depth): bool
    {
        if ($id === '' || isset($this->visited[$id])) {
            return false;
        }
        if ($depth > $this->maxDepth || $this->nodeCount >= $this->maxNodes) {
            return false;
        }

        $this->visited[$id] = true;
        $this->nodeCount++;

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    private function makeNode(string $label, array $children, array $opts): array
    {
        $hasChildren = $children !== [];
        $kind = $opts['kind'] ?? ($hasChildren ? 'sub' : 'leaf');

        $node = [
            'label' => $label === '' ? 'Untitled' : $label,
            'kind' => $kind,
            'c' => $opts['color'],
            't' => $opts['type'],
        ];

        if (! empty($opts['note'])) {
            $node['note'] = $opts['note'];
        }
        if (! empty($opts['url'])) {
            $node['url'] = $opts['url'];
        }
        if ($hasChildren) {
            $node['children'] = $children;
        }

        return $node;
    }

    private function pageTitle(array $page): string
    {
        foreach (($page['properties'] ?? []) as $prop) {
            if (($prop['type'] ?? null) === 'title') {
                return $this->richTextPlain($prop['title'] ?? []);
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $richText
     */
    private function richTextPlain(array $richText): string
    {
        $parts = [];
        foreach ($richText as $segment) {
            $parts[] = $segment['plain_text'] ?? ($segment['text']['content'] ?? '');
        }

        return trim(implode('', $parts));
    }

    private function normalizeId(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }

        // Strip dashes for a stable visited key; Notion accepts either form.
        return str_replace('-', '', $id);
    }
}
