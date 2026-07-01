<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtensionToken;
use App\Models\MigrationRun;
use App\Services\Migration\MigrationIngestor;
use App\Services\Migration\SourceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The ingest API the extension drives: create a run, announce the tree size,
 * stream page batches, then complete. Also serves the console-created "pending"
 * runs so the extension can pick them up.
 */
class MigrationApiController extends Controller
{
    public function __construct(
        private readonly MigrationIngestor $ingestor,
        private readonly SourceRegistry $sources,
    ) {}

    /** Runs created from the console that are waiting for an extension to run them. */
    public function pending(): JsonResponse
    {
        $runs = MigrationRun::where('status', 'pending')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (MigrationRun $r) => $r->summary());

        return response()->json(['runs' => $runs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'sourceRef' => ['nullable', 'string', 'max:2048'],
            'options' => ['nullable', 'array'],
            'total' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! $this->sources->isReady($data['source'])) {
            return response()->json([
                'error' => 'unknown_source',
                'message' => "Source '{$data['source']}' is not available for migration.",
            ], 422);
        }

        $run = $this->ingestor->create(
            $data['source'],
            $data['name'],
            $data['sourceRef'] ?? null,
            $data['options'] ?? [],
            $this->token($request),
        );

        if (! empty($data['total'])) {
            $this->ingestor->setTotal($run, (int) $data['total']);
        }

        return response()->json(['run' => $run->fresh()->summary()], 201);
    }

    /** Claim a console-created pending run for this extension and start it. */
    public function claim(Request $request, MigrationRun $run): JsonResponse
    {
        if ($run->status !== 'pending') {
            return response()->json([
                'error' => 'not_claimable',
                'message' => "Run is '{$run->status}', not pending.",
            ], 409);
        }

        $run->forceFill([
            'status' => 'running',
            'token_id' => $this->token($request)?->id,
            'started_at' => now(),
        ])->save();

        return response()->json(['run' => $run->summary()]);
    }

    public function tree(Request $request, MigrationRun $run): JsonResponse
    {
        $data = $request->validate(['total' => ['required', 'integer', 'min:0']]);
        $this->ingestor->setTotal($run, (int) $data['total']);

        return response()->json(['run' => $run->fresh()->summary()]);
    }

    public function nodes(Request $request, MigrationRun $run): JsonResponse
    {
        if ($run->isTerminal()) {
            return response()->json([
                'error' => 'run_closed',
                'message' => "Run is already '{$run->status}'.",
            ], 409);
        }

        $max = (int) config('migration.limits.max_batch', 250);
        $data = $request->validate([
            'nodes' => ['required', 'array', "max:{$max}"],
        ]);

        $tally = $this->ingestor->ingest($run, $data['nodes']);

        return response()->json([
            'tally' => $tally,
            'run' => $run->fresh()->summary(),
        ]);
    }

    public function complete(Request $request, MigrationRun $run): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:completed,failed,canceled'],
        ]);

        $run = $this->ingestor->complete($run, $data['status'] ?? 'completed');

        return response()->json(['run' => $run->detail()]);
    }

    public function show(MigrationRun $run): JsonResponse
    {
        return response()->json(['run' => $run->detail()]);
    }

    private function token(Request $request): ?ExtensionToken
    {
        return $request->attributes->get('extension_token');
    }
}
