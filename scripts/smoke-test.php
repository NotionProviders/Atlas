<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$adminExists = User::query()->where('email', 'admin@notionproviders.com')->exists();
echo 'admin_user='.($adminExists ? 'ok' : 'missing').PHP_EOL;

$checks = [
    ['GET', '/'],
    ['GET', '/up'],
    ['GET', '/atlas-config.js'],
    ['GET', '/console/projects'],
    ['GET', '/console/login'],
];

foreach ($checks as [$method, $path]) {
    try {
        $response = $kernel->handle(Illuminate\Http\Request::create($path, $method));
        $body = $response->getContent();
        echo $path.' '.$response->getStatusCode();

        if ($path === '/' && $response->getStatusCode() === 200) {
            echo ' inline_config='.(str_contains($body, 'window.ATLAS_CONFIG=') ? 'ok' : 'bad');
        }
        if ($path === '/atlas-config.js' && $response->getStatusCode() === 200) {
            echo ' starts='.(str_starts_with($body, 'window.ATLAS_CONFIG=') ? 'ok' : 'bad');
        }
        if ($path === '/console/projects' && $response->isRedirect()) {
            echo ' redirect='.(str_contains($response->headers->get('Location') ?? '', '/console/login') ? 'ok' : 'bad');
        }
        if ($path === '/console/login' && $response->getStatusCode() === 200) {
            echo ' form='.(str_contains($body, 'Atlas Console') ? 'ok' : 'bad');
        }

        echo PHP_EOL;

        if ($response->getStatusCode() >= 400) {
            echo substr($body, 0, 500).PHP_EOL;
        }
    } catch (Throwable $e) {
        echo $path.' EXCEPTION '.$e->getMessage().PHP_EOL;
    }
}
