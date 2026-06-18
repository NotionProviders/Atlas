<?php

namespace App\Http\Controllers;

use App\Services\Intake\CsvInspector;
use App\Services\Intake\IntakeRepository;
use App\Services\Notion\NotionClient;
use App\Services\Notion\WorkspaceCrawler;
use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * The private intake hub: one checklist per workspace covering every way a
 * Notion footprint can enter Atlas (admin CSV exports, audit log, content
 * analytics, members, workspace ZIP, and a live API scan). Console-only.
 */
class IntakeController extends Controller
{
    public function __construct(
        private readonly IntakeRepository $intake,
        private readonly WorkspaceMapRepository $maps,
    ) {}

    public function createWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $slug = $this->intake->createWorkspace($data['name']);

        return redirect()->route('intake.show', $slug)
            ->with('status', "Workspace '{$data['name']}' created. Start importing sources below.");
    }

    public function show(string $slug): View|RedirectResponse
    {
        if (! $this->intake->exists($slug)) {
            return redirect()->route('console.index')->withErrors(['workspace' => 'Unknown workspace.']);
        }

        return view('console.intake', [
            'slug' => $slug,
            'manifest' => $this->intake->getManifest($slug),
            'catalog' => $this->intake->catalog(),
            'uploadKeys' => $this->intake->uploadSourceKeys(),
            'hasMap' => $this->maps->exists($slug),
            'oauthConfigured' => trim((string) config('services.notion_oauth.client_id')) !== '',
        ]);
    }

    public function upload(Request $request, CsvInspector $inspector, string $slug): RedirectResponse
    {
        $catalogKeys = $this->intake->uploadSourceKeys();
        $data = $request->validate([
            'source' => ['required', 'string', 'in:'.implode(',', $catalogKeys)],
            'file' => ['required', 'file', 'max:1048576', 'mimes:csv,txt,zip'], // up to 1 GB
        ]);

        $file = $request->file('file');
        $size = $file->getSize();
        $stats = $inspector->inspect($file->getRealPath());
        $stats['size'] = $size;

        $this->intake->recordUpload($slug, $data['source'], $file, $stats);

        return back()->with('status', "Imported {$stats['metric']} from {$file->getClientOriginalName()}.");
    }

    public function scan(Request $request, string $slug): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'mode' => ['required', 'in:discover,teamspaces'],
            'teamspaces' => ['nullable', 'string'],
        ]);

        try {
            // Ephemeral: the token lives only for this request and is never stored.
            $client = NotionClient::fromConfig($data['token']);
            $client->whoAmI();

            $teamspaces = $data['mode'] === 'discover'
                ? $this->discoverRoots($client)
                : $this->parseTeamspaces($data['teamspaces'] ?? '');

            if ($teamspaces === []) {
                return back()->withErrors(['token' => 'No roots to crawl. Paste teamspace roots or use auto-discover.']);
            }

            @set_time_limit(0);
            $name = $this->intake->getManifest($slug)['name'];
            $map = (new WorkspaceCrawler($client))->crawl($name, $teamspaces);
            $this->maps->save($name, $map);
            $this->intake->recordApiScan($slug, number_format($map['meta']['nodeCount']).' nodes');
        } catch (Throwable $e) {
            return back()->withErrors(['token' => 'Scan failed: '.$e->getMessage()]);
        } finally {
            unset($data['token']); // belt and suspenders — drop the token reference
        }

        return back()->with('status', "Live API scan complete — mapped {$map['meta']['nodeCount']} nodes.");
    }

    public function removeSource(string $slug, string $sourceKey): RedirectResponse
    {
        $this->intake->removeSource($slug, $sourceKey);

        return back()->with('status', 'Source removed.');
    }

    public function destroyWorkspace(string $slug): RedirectResponse
    {
        $this->intake->deleteWorkspace($slug);

        return redirect()->route('console.index')->with('status', 'Workspace removed.');
    }

    public function guide(): View
    {
        return view('console.guide', [
            'catalog' => $this->intake->catalog(),
        ]);
    }

    /**
     * @return array<int, array{id:string,name:string,type:string}>
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
     * @return array<int, array{id:string,name?:string,type?:string}>
     */
    private function parseTeamspaces(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            if (isset($decoded['joinedTeams']) && is_array($decoded['joinedTeams'])) {
                return array_map(fn ($t) => [
                    'id' => $t['id'],
                    'name' => $t['name'] ?? null,
                    'type' => 'page',
                ], $decoded['joinedTeams']);
            }

            return array_values($decoded);
        }

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
