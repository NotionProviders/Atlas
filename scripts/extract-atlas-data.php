<?php

$html = file_get_contents(__DIR__ . '/../legacy/notion-workspace-atlas.html');

if (! preg_match('/const TREE=(\{[\s\S]*?\});[\s\n]*const LEGEND=/', $html, $treeMatch)) {
    fwrite(STDERR, "Failed to extract TREE.\n");
    exit(1);
}

if (! preg_match("/const LEGEND=(\\[[\\s\\S]*?\\]);/", $html, $legendMatch)) {
    fwrite(STDERR, "Failed to extract LEGEND.\n");
    exit(1);
}

$palette = [
    'core' => '#ffd66b',
    'blue' => '#4ea1ff',
    'amber' => '#f5a623',
    'violet' => '#b07cff',
    'green' => '#3ee0a0',
    'pink' => '#fb6f8e',
    'cyan' => '#2bd3e8',
    'red' => '#ff5d6c',
    'orange' => '#ff9f45',
];

$jsToJson = static function (string $js): string {
    return preg_replace('/([{\[,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $js);
};

$treeJs = $treeMatch[1];
foreach ($palette as $key => $value) {
    $treeJs = str_replace('PAL.'.$key, '"'.$value.'"', $treeJs);
}

$legendJs = preg_replace("/'([^']*)'/", '"$1"', $legendMatch[1]);

$payload = [
    'palette' => $palette,
    'tree' => json_decode($jsToJson($treeJs), true, 512, JSON_THROW_ON_ERROR),
    'legend' => json_decode($jsToJson($legendJs), true, 512, JSON_THROW_ON_ERROR),
];

$outDir = __DIR__ . '/../resources/data';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

file_put_contents(
    $outDir . '/atlas.json',
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Wrote resources/data/atlas.json\n";
