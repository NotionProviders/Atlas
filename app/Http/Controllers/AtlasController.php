<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use JsonException;
use RuntimeException;

class AtlasController extends Controller
{
    public function index(): View
    {
        $meta = config('atlas');

        return view('atlas.index', [
            'pageTitle' => $meta['title'],
            'kicker' => $meta['kicker'],
            'heading' => $meta['heading'],
        ]);
    }

    public function configScript(): Response
    {
        $data = $this->loadAtlasData();

        try {
            $json = json_encode([
                'palette' => $data['palette'],
                'tree' => $data['tree'],
                'legend' => $data['legend'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode atlas config.', 0, $e);
        }

        return response(
            'window.ATLAS_CONFIG='.$json.';',
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, max-age=300',
            ]
        );
    }

    private function loadAtlasData(): array
    {
        $path = resource_path('data/atlas.json');

        if (! File::exists($path)) {
            throw new RuntimeException('Missing atlas data at '.$path);
        }

        return json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
