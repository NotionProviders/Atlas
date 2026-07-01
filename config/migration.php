<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Source connectors
    |--------------------------------------------------------------------------
    |
    | The tools Atlas can migrate *out of* via the browser extension. Each
    | connector is implemented as an adapter in the extension (it runs inside
    | your already-authenticated tab) and streams normalized pages back here.
    |
    | This registry is the single source of truth the console renders from and
    | the API validates against. Add a new tool by adding an entry here plus a
    | matching adapter in extension/src/connectors — nothing else changes.
    |
    | status: 'ready' (live), 'beta', or 'planned' (shown, not yet selectable).
    */
    'sources' => [
        'loop' => [
            'key' => 'loop',
            'label' => 'Microsoft Loop',
            'blurb' => 'Scrape Loop workspaces from your logged-in tab — Loop has no API, so the extension reads the page tree directly.',
            'host' => 'loop.cloud.microsoft',
            'status' => 'ready',
        ],
        'onenote' => [
            'key' => 'onenote',
            'label' => 'OneNote (web)',
            'blurb' => 'Notebooks → sections → pages from the OneNote web app.',
            'host' => 'onedrive.live.com',
            'status' => 'planned',
        ],
        'confluence' => [
            'key' => 'confluence',
            'label' => 'Confluence',
            'blurb' => 'Spaces and pages from an Atlassian Confluence site.',
            'host' => 'atlassian.net',
            'status' => 'planned',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extension pairing
    |--------------------------------------------------------------------------
    |
    | A console session mints a short-lived pairing code; the extension trades
    | it for a long-lived API token. code_ttl is how long the code stays valid.
    */
    'pairing' => [
        'code_ttl' => (int) env('MIGRATION_PAIRING_TTL', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage + guard rails
    |--------------------------------------------------------------------------
    |
    | Exported markdown trees are written under storage/app/{root}/{slug}. Limits
    | cap a single run so a runaway crawl can't exhaust disk or memory.
    */
    'storage_root' => env('MIGRATION_STORAGE_ROOT', 'migrations'),

    'limits' => [
        'max_nodes' => (int) env('MIGRATION_MAX_NODES', 20000),
        'max_batch' => (int) env('MIGRATION_MAX_BATCH', 250),
        'max_html_bytes' => (int) env('MIGRATION_MAX_HTML_BYTES', 4_000_000),
    ],
];
