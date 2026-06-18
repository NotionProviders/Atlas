<?php

namespace App\Http\Controllers;

use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use JsonException;
use RuntimeException;

/**
 * Public-facing atlas at "/". Serves only public maps (the conceptual
 * Workspace Atlas). Real, crawled workspaces are private and live behind the
 * console; they are never reachable from here.
 */
class AtlasController extends Controller
{
    public function __construct(private readonly WorkspaceMapRepository $maps) {}

    public function index(Request $request): View
    {
        $meta = config('atlas');
        $slug = $this->resolvePublicSlug($request);
        $map = $this->maps->load($slug);

        return view('atlas.index', [
            'pageTitle' => $meta['title'],
            'kicker' => $meta['kicker'],
            'atlasConfigScript' => $this->buildConfigScript($map),
            'context' => 'public',
            'workspaces' => $this->maps->publicList(),
            'activeSlug' => $slug,
            'switchBase' => '/?w=',
        ]);
    }

    public function configScript(Request $request): Response
    {
        $map = $this->maps->load($this->resolvePublicSlug($request));

        return response(
            $this->buildConfigScript($map),
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, max-age=300',
            ]
        );
    }

    /**
     * Resolve the requested slug, but never serve a private map publicly.
     */
    private function resolvePublicSlug(Request $request): string
    {
        $slug = (string) $request->query('w', '');

        if ($slug !== '' && $this->maps->exists($slug) && $this->maps->isPublic($slug)) {
            return $slug;
        }

        return $this->maps->publicDefaultSlug();
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
