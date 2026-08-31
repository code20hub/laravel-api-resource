<?php

namespace Code20\ApiBoilerplate;

use Illuminate\Support\ServiceProvider;
use Code20\ApiBoilerplate\Console\Commands\MakeApiResourceCommand;

class ApiBoilerplateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-boilerplate.php',
            'api-boilerplate'
        );
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MakeApiResourceCommand::class,
        ]);

        // php artisan vendor:publish --tag=api-boilerplate-config
        $this->publishes([
            __DIR__ . '/../config/api-boilerplate.php' => config_path('api-boilerplate.php'),
        ], 'api-boilerplate-config');

        // php artisan vendor:publish --tag=api-boilerplate-stubs
        $this->publishes([
            __DIR__ . '/../stubs/api-boilerplate' => base_path('stubs/api-boilerplate'),
        ], 'api-boilerplate-stubs');

        // php artisan vendor:publish --tag=api-boilerplate  (publishes both at once)
        $this->publishes([
            __DIR__ . '/../config/api-boilerplate.php' => config_path('api-boilerplate.php'),
            __DIR__ . '/../stubs/api-boilerplate' => base_path('stubs/api-boilerplate'),
        ], 'api-boilerplate');
    }
}
