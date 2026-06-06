<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AtlasController extends Controller
{
    public function index(): View
    {
        $atlas = config('atlas');

        return view('atlas.index', [
            'atlas' => $atlas,
            'atlasConfig' => [
                'palette' => $atlas['palette'],
                'tree' => $atlas['tree'],
                'legend' => $atlas['legend'],
            ],
        ]);
    }
}
