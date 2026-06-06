<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AtlasController extends Controller
{
    public function index(): View
    {
        $meta = config('atlas');
        $data = json_decode(File::get($meta['data_path']), true, 512, JSON_THROW_ON_ERROR);

        return view('atlas.index', [
            'pageTitle' => $meta['title'],
            'kicker' => $meta['kicker'],
            'heading' => $meta['heading'],
            'atlasConfig' => [
                'palette' => $data['palette'],
                'tree' => $data['tree'],
                'legend' => $data['legend'],
            ],
        ]);
    }
}
