<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notion API token
    |--------------------------------------------------------------------------
    |
    | A Notion internal integration token ("full access" recommended) used by
    | the recursive workspace crawler. The Notion REST API cannot enumerate
    | teamspaces, so teamspace roots are seeded separately (see the
    | `atlas:ingest` command and the in-app intake form).
    */
    'token' => env('NOTION_API_KEY'),

    'version' => env('NOTION_VERSION', '2022-06-28'),

    'base_url' => env('NOTION_BASE_URL', 'https://api.notion.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Crawl limits
    |--------------------------------------------------------------------------
    |
    | Guard rails so a runaway recursion can't hang the process. `max_depth`
    | counts levels below a teamspace root. `max_nodes` caps the total mapped
    | nodes. Set generously high for "everything, fully recursive" runs.
    */
    'crawl' => [
        'max_depth' => (int) env('NOTION_CRAWL_MAX_DEPTH', 12),
        'max_nodes' => (int) env('NOTION_CRAWL_MAX_NODES', 20000),
        'throttle_ms' => (int) env('NOTION_CRAWL_THROTTLE_MS', 0),
    ],
];
