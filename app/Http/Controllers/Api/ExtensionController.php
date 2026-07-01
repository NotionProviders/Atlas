<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtensionToken;
use App\Services\Migration\SourceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Extension identity + pairing. The pairing code is minted from an authed
 * console session (see MigrationsController@pairCode); trading it here is what
 * bootstraps the extension's long-lived token.
 */
class ExtensionController extends Controller
{
    public function __construct(private readonly SourceRegistry $sources) {}

    /**
     * Trade a pairing code for a bearer token. Public, but the code is the
     * secret and expires quickly.
     */
    public function pair(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $token = ExtensionToken::redeemPairingCode(
            strtoupper(trim($data['code'])),
            $data['name'] ?? null,
        );

        if ($token === null) {
            return response()->json([
                'error' => 'invalid_code',
                'message' => 'That pairing code is invalid or has expired. Generate a fresh one in the Atlas console.',
            ], 422);
        }

        return response()->json([
            'token' => $token,
            'app' => config('atlas.title'),
            'sources' => $this->sourcePayload(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var ExtensionToken $token */
        $token = $request->attributes->get('extension_token');

        return response()->json([
            'connected' => true,
            'name' => $token->name,
            'app' => config('atlas.title'),
            'lastUsedAt' => $token->last_used_at?->toIso8601String(),
            'sources' => $this->sourcePayload(),
        ]);
    }

    public function sources(): JsonResponse
    {
        return response()->json(['sources' => $this->sourcePayload()]);
    }

    /**
     * @return array<int, array{key: string, label: string, blurb: string, host: string, status: string}>
     */
    private function sourcePayload(): array
    {
        return array_values($this->sources->all());
    }
}
