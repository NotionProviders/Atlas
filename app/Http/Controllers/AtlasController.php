<?php

namespace App\Http\Controllers;

use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use JsonException;
use RuntimeException;

class AtlasController extends Controller
{
    public function __construct(private readonly WorkspaceMapRepository $maps) {}

    public function index(Request $request): View
    {
        $meta = config('atlas');
        $slug = $this->resolveSlug($request);
        $map = $this->maps->load($slug);

        return view('atlas.index', [
            'pageTitle' => $meta['title'],
            'kicker' => $map['meta']['name'] ?? $meta['kicker'],
            'atlasConfigScript' => $this->buildConfigScript($map),
            'workspaces' => $this->maps->list(),
            'activeSlug' => $slug,
        ]);
    }

    public function configScript(Request $request): Response
    {
        $map = $this->maps->load($this->resolveSlug($request));

        return response(
            $this->buildConfigScript($map),
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, max-age=300',
            ]
        );
    }

    private function resolveSlug(Request $request): string
    {
        $slug = (string) $request->query('w', '');

        if ($slug !== '' && $this->maps->exists($slug)) {
            return $slug;
        }

        return $this->maps->defaultSlug();
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
