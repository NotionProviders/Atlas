<?php

$dataPath = resource_path('data/atlas.json');

if (! file_exists($dataPath)) {
    throw new RuntimeException('Missing atlas data. Run: php scripts/extract-atlas-data.php');
}

return [
    'title' => 'Notion Workspace · Orbital Atlas',
    'kicker' => 'Workspace Atlas · orbital zoom',
    'heading' => 'Notion Workspace',
    'data_path' => $dataPath,
];
