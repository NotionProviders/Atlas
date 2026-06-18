<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Console password
    |--------------------------------------------------------------------------
    |
    | Shared password for the private /console backend (the workspace mapping
    | tool). Set CONSOLE_PASSWORD in your .env. If left blank, the console is
    | locked and no one can log in.
    */
    'password' => env('CONSOLE_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Default console map
    |--------------------------------------------------------------------------
    |
    | The real workspace map shown first inside the console.
    */
    'default_map' => env('CONSOLE_DEFAULT_MAP', 'formosa-ev-hq'),
];
