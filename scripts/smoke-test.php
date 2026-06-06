<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach (['/', '/up', '/atlas-config.js'] as $path) {
    try {
        $response = $kernel->handle(Illuminate\Http\Request::create($path, 'GET'));
        $body = $response->getContent();
        echo $path.' '.$response->getStatusCode();
        if ($path === '/' && $response->getStatusCode() === 200) {
            echo ' inline_config='.(str_contains($body, 'window.ATLAS_CONFIG=') ? 'ok' : 'bad');
        }
        if ($path === '/atlas-config.js' && $response->getStatusCode() === 200) {
            echo ' starts='.(str_starts_with($body, 'window.ATLAS_CONFIG=') ? 'ok' : 'bad');
        }
        echo PHP_EOL;
        if ($response->getStatusCode() >= 400) {
            echo substr($body, 0, 500).PHP_EOL;
        }
    } catch (Throwable $e) {
        echo $path.' EXCEPTION '.$e->getMessage().PHP_EOL;
    }
}
