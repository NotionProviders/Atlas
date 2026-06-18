<?php

return [
    'title' => 'Notion Workspace Atlas',
    'kicker' => 'Notion Workspace Atlas',
    'url' => 'https://atlas.notionproviders.com',

    /*
    | Slug of the public map shown at "/". This is the public-facing
    | conceptual atlas — never a real, crawled workspace.
    */
    'public_default' => env('ATLAS_PUBLIC_MAP', 'concept'),
];
