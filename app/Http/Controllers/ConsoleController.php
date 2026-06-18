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
        return view('console.index', [
            'workspaces' => $this->maps->list(),
            'hasToken' => trim((string) config('notion.token')) !== '',
        ]);
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
