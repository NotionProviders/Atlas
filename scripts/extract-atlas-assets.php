<?php

$html = file_get_contents(__DIR__ . '/../legacy/notion-workspace-atlas.html');

preg_match('/<style>(.*?)<\/style>/s', $html, $cssMatch);
preg_match('/<script>(.*?)<\/script>/s', $html, $jsMatch);

$cssDir = __DIR__ . '/../public/css';
$jsDir = __DIR__ . '/../public/js';

if (! is_dir($cssDir)) {
    mkdir($cssDir, 0777, true);
}

if (! is_dir($jsDir)) {
    mkdir($jsDir, 0777, true);
}

file_put_contents($cssDir . '/atlas.css', trim($cssMatch[1]));

$js = $jsMatch[1];
$js = preg_replace('/const PAL=\{[^;]+\};\s*/', '', $js);
$js = preg_replace('/const TREE=\{[\s\S]*?\};\s*/', '', $js);
$js = preg_replace('/const LEGEND=\[[\s\S]*?\];\s*/', <<<'JS'
const PAL = window.ATLAS_CONFIG.palette;
const TREE = window.ATLAS_CONFIG.tree;
const LEGEND = window.ATLAS_CONFIG.legend;


JS, $js);

file_put_contents($jsDir . '/atlas.js', trim($js));

echo "Wrote public/css/atlas.css and public/js/atlas.js\n";
