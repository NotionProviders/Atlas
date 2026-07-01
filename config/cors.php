<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Only the extension-facing API is cross-origin (the browser extension calls
    | it from a chrome-extension:// origin). Those endpoints are bearer-token
    | authenticated and never rely on cookies, so a wildcard origin with
    | credentials disabled is safe — no session can ride along.
    |
    | The session-backed /console lives on `web` routes and is untouched by this.
    */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
