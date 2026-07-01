<?php

namespace App\Http\Controllers;

use App\Services\Notion\ExportMapBuilder;
use App\Services\Notion\NotionClient;
use App\Services\Notion\WorkspaceCrawler;
use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Self-serve intake (console-only): connect a Notion token, point at teamspace
 * roots, and crawl a workspace into a map. Crawled maps are private.
 */
class WorkspacesController extends Controller
{
    public function __construct(private readonly WorkspaceMapRepository $maps) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'token' => ['nullable', 'string'],
            'mode' => ['required', 'in:discover,teamspaces'],
            'teamspaces' => ['nullable', 'string'],
        ]);

        $token = $data['token'] ?: (string) config('notion.token');
        if (trim($token) === '') {
            return back()->withInput()->withErrors([
                'token' => 'A Notion API token is required (or set NOTION_API_KEY in .env).',
            ]);
        }

        try {
            $client = NotionClient::fromConfig($token);
            $client->whoAmI();

            $teamspaces = $data['mode'] === 'discover'
                ? $this->discoverRoots($client)
                : $this->parseTeamspaces($data['teamspaces'] ?? '');

            if ($teamspaces === []) {
                return back()->withInput()->withErrors([
                    'teamspaces' => 'No roots to crawl. Paste teamspace roots or use auto-discover.',
                ]);
            }

            // Recursive crawls can be slow; give them room when run via the form.
            @set_time_limit(0);

            $map = (new WorkspaceCrawler($client))->crawl($data['name'], $teamspaces);
            $slug = $this->maps->save($data['name'], $map);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['token' => 'Crawl failed: '.$e->getMessage()]);
        }

        return redirect()->route('console.map', $slug)->with('status',
            "Mapped {$map['meta']['nodeCount']} nodes from {$data['name']}.");
    }

    /**
     * Intake by uploaded Notion export (Markdown & CSV zip, or a single
     * md/html/csv). Works offline and reaches teamspaces the REST API can't.
     */
    public function upload(Request $request, ExportMapBuilder $builder): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'export' => ['required', 'file', 'max:512000', // 500 MB
                'mimes:zip,csv,md,markdown,html,htm,txt,application/zip,application/octet-stream'],
        ]);

        try {
            @set_time_limit(0);
            $map = $builder->buildFromUpload($request->file('export')->getRealPath(), $data['name']);

            if (($map['meta']['nodeCount'] ?? 0) === 0) {
                return back()->withInput()->withErrors([
                    'export' => "Couldn't find any pages in that export. Use Notion's “Markdown & CSV” export (include subpages).",
                ]);
            }

            $slug = $this->maps->save($data['name'], $map);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['export' => 'Import failed: '.$e->getMessage()]);
        }

        return redirect()->route('console.map', $slug)->with('status',
            "Imported {$map['meta']['nodeCount']} nodes from the {$data['name']} export.");
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->maps->delete($slug);

        return redirect()->route('console.index')->with('status', "Removed map '{$slug}'.");
    }

    /**
     * @return array<int, array{id: string, name: string, type: string}>
     */
    private function discoverRoots(NotionClient $client): array
    {
        $roots = [];
        foreach ($client->search() as $object) {
            if (($object['parent']['type'] ?? '') !== 'workspace') {
                continue;
            }
            $roots[] = [
                'id' => $object['id'],
                'name' => $this->objectTitle($object),
                'type' => ($object['object'] ?? '') === 'database' ? 'database' : 'page',
            ];
        }

        return $roots;
    }

    /**
     * Accept either JSON (from MCP get-teams) or simple "name = id" lines.
     *
     * @return array<int, array{id: string, name?: string, type?: string}>
     */
    private function parseTeamspaces(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            // Support the raw get-teams payload too.
            if (isset($decoded['joinedTeams']) && is_array($decoded['joinedTeams'])) {
                return array_map(fn ($t) => [
                    'id' => $t['id'],
                    'name' => $t['name'] ?? null,
                    'type' => 'page',
                ], $decoded['joinedTeams']);
            }

            return array_values($decoded);
        }

        // Line-based: "Team Name = <id>" or just "<id>" per line.
        $roots = [];
        foreach (preg_split('/\r?\n/', $input) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, '=')) {
                [$name, $id] = array_map('trim', explode('=', $line, 2));
                $roots[] = ['id' => $id, 'name' => $name, 'type' => 'page'];
            } else {
                $roots[] = ['id' => $line, 'type' => 'page'];
            }
        }

        return $roots;
    }

    private function objectTitle(array $object): string
    {
        if (($object['object'] ?? '') === 'database') {
            return $this->plain($object['title'] ?? []) ?: 'Untitled database';
        }
        foreach (($object['properties'] ?? []) as $prop) {
            if (($prop['type'] ?? null) === 'title') {
                return $this->plain($prop['title'] ?? []) ?: 'Untitled';
            }
        }

        return 'Untitled';
    }

    private function plain(array $richText): string
    {
        return trim(implode('', array_map(
            fn ($s) => $s['plain_text'] ?? ($s['text']['content'] ?? ''),
            $richText
        )));
    }
}
