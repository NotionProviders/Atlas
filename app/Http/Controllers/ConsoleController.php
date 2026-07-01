<?php

namespace App\Http\Controllers;

use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;
use RuntimeException;

/**
 * Private backend ("Atlas Console"): login, the workspace dashboard, and
 * viewing real crawled workspace maps. Everything except login is gated by
 * the console.auth middleware.
 */
class ConsoleController extends Controller
{
    public function __construct(private readonly WorkspaceMapRepository $maps) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('console_authed') === true) {
            return redirect()->route('console.index');
        }

        return view('console.login', [
            'locked' => trim((string) config('console.password')) === '',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $expected = (string) config('console.password');
        if (trim($expected) === '') {
            return back()->withErrors(['password' => 'The console is locked. Set CONSOLE_PASSWORD in .env to enable login.']);
        }

        if (! hash_equals($expected, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('console_authed', true);

        return redirect()->intended(route('console.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('console_authed');
        $request->session()->regenerate();

        return redirect()->route('console.login');
    }

    public function index(): View
    {
        $workspaces = $this->maps->list();
        $sources = array_count_values(array_map(
            fn ($w) => (string) ($w['source'] ?? 'built-in'),
            $workspaces
        ));

        return view('console.index', [
            'workspaces' => $workspaces,
            'hasToken' => trim((string) config('notion.token')) !== '',
            'discoveredSeed' => $this->discoveredSeed(),
            'intakeMethods' => $this->intakeMethods($sources, trim((string) config('notion.token')) !== ''),
        ]);
    }

    /**
     * The teamspaces discovered via the Notion MCP, ready to drop into the
     * crawl seed box. Returns the raw JSON string, or null if none seeded.
     */
    private function discoveredSeed(): ?string
    {
        $path = resource_path('data/seeds/teamspaces.json');
        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || empty($decoded['joinedTeams'])) {
            return null;
        }

        return json_encode($decoded['joinedTeams'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The intake "coverage checklist": every way to get a workspace into
     * Atlas, whether it's wired up, and how many maps have come in through it.
     *
     * @param  array<string, int>  $sources
     * @return array<int, array{key: string, label: string, blurb: string, status: string, count: int}>
     */
    private function intakeMethods(array $sources, bool $hasToken): array
    {
        return [
            [
                'key' => 'export-upload',
                'label' => 'Upload a Notion export',
                'blurb' => 'Markdown & CSV (or HTML) zip — fully offline, reaches every teamspace.',
                'status' => 'ready',
                'count' => $sources['export-upload'] ?? 0,
            ],
            [
                'key' => 'notion-crawl',
                'label' => 'Crawl via Notion API',
                'blurb' => 'Live recursive crawl with a full-access integration token + seed roots.',
                'status' => $hasToken ? 'ready' : 'needs-token',
                'count' => $sources['notion-crawl'] ?? 0,
            ],
            [
                'key' => 'mcp-seed',
                'label' => 'Seed teamspaces (MCP)',
                'blurb' => 'Paste get-teams output so the crawl can reach teamspaces the API can\'t list.',
                'status' => $this->discoveredSeed() !== null ? 'ready' : 'available',
                'count' => 0,
            ],
            [
                'key' => 'oauth',
                'label' => 'Connect with OAuth',
                'blurb' => 'One-click authorize so Atlas scans everything you can see. No pasted keys.',
                'status' => 'planned',
                'count' => 0,
            ],
        ];
    }

    /**
     * Render the interactive atlas for a (possibly private) workspace map.
     * Protected by console.auth, so private maps are only viewable here.
     */
    public function viewMap(string $slug): View
    {
        if (! $this->maps->exists($slug)) {
            $slug = $this->maps->consoleDefaultSlug();
        }

        $map = $this->maps->load($slug);
        $meta = config('atlas');

        return view('atlas.index', [
            'pageTitle' => ($map['meta']['name'] ?? $meta['title']).' · Console',
            'kicker' => $map['meta']['name'] ?? $meta['kicker'],
            'atlasConfigScript' => $this->buildConfigScript($map),
            'context' => 'console',
            'workspaces' => $this->maps->list(),
            'activeSlug' => $slug,
            'switchBase' => '/console/map/',
        ]);
    }

    private function buildConfigScript(?array $data): string
    {
        if ($data === null) {
            throw new RuntimeException('No atlas map available to render.');
        }

        try {
            $json = json_encode([
                'palette' => $data['palette'],
                'tree' => $data['tree'],
                'legend' => $data['legend'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode atlas config.', 0, $e);
        }

        return 'window.ATLAS_CONFIG='.$json.';';
    }
}
