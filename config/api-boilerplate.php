<?php

return [
    // Where published/customized .stub files are looked for first.
    // Publish with: php artisan vendor:publish --tag=api-boilerplate-stubs
    // If a stub isn't found here, the command falls back to the one bundled
    // inside the package itself, so nothing breaks if you never publish.
    'stub_path' => base_path('stubs/api-boilerplate'),

    // Where generated routes get appended.
    'routes_file' => base_path('routes/api.php'),

    // Where Pest feature tests get written.
    'tests_path' => base_path('tests/Feature'),

    // Root namespaces for each generated layer (folder/version subpaths get appended).
    'namespaces' => [
        'requests'    => 'App\\Http\\Requests',
        'resources'   => 'App\\Http\\Resources',
        'services'    => 'App\\Services',
        'controllers' => 'App\\Http\\Controllers',
        'dtos'        => 'App\\DataTransferObjects',
        'exceptions'  => 'App\\Exceptions\\Api',
    ],

    // Root filesystem paths (relative to app_path()) mirroring the namespaces above.
    'paths' => [
        'requests'    => 'Http/Requests',
        'resources'   => 'Http/Resources',
        'services'    => 'Services',
        'controllers' => 'Http/Controllers',
        'dtos'        => 'DataTransferObjects',
        'exceptions'  => 'Exceptions/Api',
    ],
];
