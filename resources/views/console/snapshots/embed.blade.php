<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — {{ $snapshotType->label() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/atlas.css') }}?v={{ @filemtime(public_path('css/atlas.css')) ?: 1 }}">
    <style>
        html, body { margin: 0; padding: 0; background: #060a14; overflow: hidden; height: 100%; }
        #app { min-height: 100vh; height: 100vh; }
    </style>
</head>
<body>
@include('atlas.partials.map', ['kicker' => $project->name.' · '.$snapshotType->label()])
<script>{!! $atlasConfigScript !!}</script>
<script src="{{ asset('js/atlas.js') }}?v={{ @filemtime(public_path('js/atlas.js')) ?: 1 }}"></script>
</body>
</html>
