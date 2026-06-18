<?php

/*
|--------------------------------------------------------------------------
| Intake source catalog
|--------------------------------------------------------------------------
|
| The full set of ways a Notion workspace's footprint can enter Atlas. This
| drives the console intake checklist and the usage guide. Ordered by their
| role in reconciliation: the canonical page seed first, corroborators and
| behavioral sources next, then live connections.
|
| Each source:
|   key      unique id (also the stored filename stem)
|   label    display name
|   kind     'upload' | 'api' | 'oauth'
|   role     short tag shown as a badge
|   seed     true if it is a primary page-enumeration seed
|   accept   accepted file extensions (upload kinds)
|   expected rough expectation to set the operator's bias
|   summary  one line on what it is / why it matters
|   how_to   ordered steps to obtain it
*/

return [
    'sources' => [
        [
            'key' => 'admin_content_search_active',
            'label' => 'AdminContentSearch — Active',
            'kind' => 'upload',
            'role' => 'Primary seed',
            'seed' => true,
            'accept' => '.csv',
            'expected' => 'one row per active page (the largest page count you have)',
            'summary' => 'The canonical enumeration of every active page ID in the workspace. This is the seed — every other source corroborates it.',
            'how_to' => [
                'Open Notion → Settings → Admin / Security (Enterprise admin).',
                'Go to Content Search and run an empty/all search across the workspace.',
                'Filter to state = Active.',
                'Export the results as CSV and upload it here.',
            ],
        ],
        [
            'key' => 'admin_content_search_retained',
            'label' => 'AdminContentSearch — Retained',
            'kind' => 'upload',
            'role' => 'Retention seed',
            'seed' => true,
            'accept' => '.csv',
            'expected' => 'admin-deleted pages held under legal hold',
            'summary' => 'The only source for pages that were admin-deleted but preserved under legal hold. Feeds each page a retention_state.',
            'how_to' => [
                'Same Content Search panel as the Active export.',
                'Filter to state = Retained.',
                'Export as CSV and upload it here.',
            ],
        ],
        [
            'key' => 'audit_log',
            'label' => 'Audit Log',
            'kind' => 'upload',
            'role' => 'Behavioral',
            'seed' => false,
            'accept' => '.csv',
            'expected' => 'tens of thousands of events over the retained window',
            'summary' => 'Time-stamped, attributed events (created/edited/viewed/exported/shared, logins with IP, MCP/integration connections). The richest source for "is this still alive?" signals.',
            'how_to' => [
                'Notion → Settings → Security & identity → Audit log (Enterprise).',
                'Choose the longest available date range.',
                'Export as CSV and upload it here.',
            ],
        ],
        [
            'key' => 'content_analytics',
            'label' => 'Content Analytics',
            'kind' => 'upload',
            'role' => 'Corroborator',
            'seed' => false,
            'accept' => '.csv',
            'expected' => 'slightly fewer pages than AdminContentSearch (it undercounts ~4%)',
            'summary' => 'Per-page view/edit analytics. A corroborator for recency signals — never the seed (it misses pages AdminContentSearch catches).',
            'how_to' => [
                'Notion → Settings → Analytics / Workspace analytics.',
                'Open the Content tab.',
                'Export the content table as CSV and upload it here.',
            ],
        ],
        [
            'key' => 'members',
            'label' => 'Members',
            'kind' => 'upload',
            'role' => 'People & access',
            'seed' => false,
            'accept' => '.csv',
            'expected' => 'one row per member, with a comma-delimited Permission groups column',
            'summary' => 'Members and guests with their permission groups (parsed from the comma-delimited column — no separate permission-group export needed).',
            'how_to' => [
                'Notion → Settings → Members.',
                'Use the ··· menu to export the member list as CSV.',
                'Upload it here.',
            ],
        ],
        [
            'key' => 'workspace_export',
            'label' => 'Workspace export (ZIP)',
            'kind' => 'upload',
            'role' => 'Content & structure',
            'seed' => false,
            'accept' => '.zip',
            'expected' => 'a large ZIP of Markdown/CSV (or HTML) mirroring the page tree',
            'summary' => 'A full content export. Gives the page tree, titles, and archive state directly — useful structure that the API crawl reconstructs more slowly.',
            'how_to' => [
                'Notion → Settings → Workspace → Export all workspace content.',
                'Choose "Markdown & CSV" (or HTML), include subpages.',
                'Download the ZIP and upload it here (do not unzip).',
            ],
        ],
        [
            'key' => 'api_scan',
            'label' => 'Live API scan',
            'kind' => 'api',
            'role' => 'Live structure',
            'seed' => false,
            'accept' => null,
            'expected' => 'teamspaces and the current page/database tree the token can reach',
            'summary' => 'A live recursive crawl via the Notion REST API using a full-access token you paste once. The token is used for the scan only and is never written to disk.',
            'how_to' => [
                'Create a full-access internal integration token in Notion.',
                'Paste it below and run the scan — it is held in the request only and discarded after.',
                'Seed teamspace roots (the API cannot list teamspaces) or use auto-discover.',
            ],
        ],
        [
            'key' => 'oauth_connect',
            'label' => 'Connect Notion (OAuth)',
            'kind' => 'oauth',
            'role' => 'Live structure',
            'seed' => false,
            'accept' => null,
            'expected' => 'everything the authorizing account can access — no token handling',
            'summary' => 'One-click "Connect Notion" that scans everything you can access. Cleaner than pasting a key, but requires a registered Notion OAuth integration first.',
            'how_to' => [
                'Register a public OAuth integration in Notion and set its redirect URL.',
                'Add NOTION_OAUTH_CLIENT_ID / SECRET to your .env.',
                'Then this button becomes live. (Setup required — not yet configured.)',
            ],
        ],
    ],
];
