<?php

namespace App\Http\Controllers;

use App\Models\ExtensionToken;
use App\Models\MigrationRun;
use App\Services\Migration\ExportArchive;
use App\Services\Migration\MigrationStorage;
use App\Services\Migration\SourceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The console-side Migrations module: pair the browser extension, kick off a
 * migration, watch it stream in, and download the Notion-ready export. All
 * session-gated by console.auth (registered in routes/web.php).
 */
class MigrationsController extends Controller
{
    public function __construct(
        private readonly SourceRegistry $sources,
        private readonly MigrationStorage $storage,
        private readonly ExportArchive $archive,
    ) {}

    public function index(): View
    {
        return view('console.migrations.index', [
            'runs' => MigrationRun::latest()->get(),
            'sources' => $this->sources->all(),
            'hasReadySource' => $this->sources->ready() !== [],
            'extensions' => ExtensionToken::where('status', 'active')->latest('last_used_at')->get(),
            'pendingCount' => MigrationRun::where('status', 'pending')->count(),
        ]);
    }

    /** Mint a short-lived pairing code (called by fetch from the console page). */
    public function pairCode(Request $request): JsonResponse
    {
        $ttl = (int) config('migration.pairing.code_ttl', 600);
        [, $code] = ExtensionToken::mintPairingCode($ttl, $request->input('name'));

        return response()->json([
            'code' => $code,
            'expiresIn' => $ttl,
            'apiBase' => url('/api'),
        ]);
    }

    /** Create a pending run from the console for the extension to pick up. */
    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        if (! $this->sources->isReady($data['source'])) {
            return back()->withInput()->withErrors(['source' => 'That source is not available yet.']);
        }

        $run = MigrationRun::create([
            'slug' => $this->uniqueSlug($data['name']),
            'source' => $data['source'],
            'name' => $data['name'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('console.migrations.show', $run)
            ->with('status', "Migration queued. Open {$this->sources->label($data['source'])} in your browser, then run it from the paired extension.");
    }

    public function show(MigrationRun $run): View
    {
        return view('console.migrations.show', [
            'run' => $run,
            'items' => $run->items()->orderBy('id')->get(),
            'hasZip' => $this->archive->exists($run),
        ]);
    }

    /** JSON status for the live-progress poller. */
    public function status(MigrationRun $run): JsonResponse
    {
        return response()->json([
            'run' => $run->detail(),
            'hasZip' => $this->archive->exists($run),
            'items' => $run->items()->orderBy('id')->get()->map(fn ($i) => [
                'title' => $i->title,
                'path' => $i->path,
                'kind' => $i->kind,
                'status' => $i->status,
                'error' => $i->error,
            ]),
        ]);
    }

    public function download(MigrationRun $run): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $zipPath = $this->storage->zipPath($run);

        if (! File::exists($zipPath)) {
            try {
                $this->archive->build($run);
            } catch (\Throwable $e) {
                return back()->withErrors(['download' => 'Could not build the export: '.$e->getMessage()]);
            }
        }

        return response()->download($zipPath, $run->slug.'.zip');
    }

    public function destroy(MigrationRun $run): RedirectResponse
    {
        $this->storage->delete($run);
        $run->delete();

        return redirect()->route('console.migrations.index')->with('status', 'Migration removed.');
    }

    public function revokeExtension(ExtensionToken $token): RedirectResponse
    {
        $token->forceFill(['status' => 'revoked', 'token_hash' => null])->save();

        return back()->with('status', 'Extension disconnected.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $n = 2;
        while (MigrationRun::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
