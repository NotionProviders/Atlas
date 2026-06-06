<?php

$dataPath = resource_path('data/atlas.json');

if (! file_exists($dataPath)) {
    throw new RuntimeException('Missing atlas data. Run: php scripts/extract-atlas-data.php');
}

$data = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);

return [
    'title' => 'Notion Workspace · Orbital Atlas',
    'kicker' => 'Workspace Atlas · orbital zoom',
    'heading' => 'Notion Workspace',
    'palette' => $data['palette'],
    'tree' => $data['tree'],
    'legend' => $data['legend'],
];
