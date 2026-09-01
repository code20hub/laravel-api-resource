<?php

namespace Code20\ApiBoilerplate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeApiResourceCommand extends Command
{
    protected $signature = 'make:api-resource
                            {name?* : One or more resource names (space or comma separated), or omit to use --manifest}
                            {--F|subfolder= : Optional explicit subfolder (e.g., v1/Admin)}
                            {--api-version= : API version shortcut (e.g., v1) -> Api/V1 namespace + v1/ route prefix}
                            {--policy : Generate an authorization Policy and wire it into the Form Request}
                            {--tests : Generate a Pest feature test}
                            {--dto : Use a typed Data Transfer Object instead of raw arrays}
                            {--exception : Generate a dedicated conflict exception (self-rendering, no handler edits needed)}
                            {--manifest= : Path to a JSON file describing multiple resources to scaffold at once}
                            {--dry-run : Show what would be generated/changed without writing anything}
                            {--force : Overwrite files that already exist}';

    protected $description = 'High-velocity API architecture scaffolding using configurable external stubs';

    public function handle(): int
    {
        $jobs = $this->resolveJobs();

        if (empty($jobs)) {
            $this->error('No valid resources to scaffold.');
            return Command::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $interactive = count($jobs) === 1 && !$this->option('manifest');

        foreach ($jobs as $job) {
            $job['dry_run'] = $dryRun;
            $job['force'] = $job['force'] ?? (bool) $this->option('force');
            $this->scaffoldResource($job, $interactive);
            $this->newLine();
        }

        if ($dryRun) {
            $this->comment('Dry run complete — no files were written and no routes were changed.');
        }

        return Command::SUCCESS;
    }

    /**
     * Build the list of resources to scaffold, either from --manifest
     * or from the name argument(s) plus shared CLI flags.
     */
    protected function resolveJobs(): array
    {
        if ($manifestPath = $this->option('manifest')) {
            return $this->loadManifest($manifestPath);
        }

        $names = $this->argument('name') ?? [];

        if (empty($names)) {
            $answer = $this->ask('What is the singular name of your resource (e.g., product)? Comma-separate for multiple.');
            $names = $answer ? [$answer] : [];
        }

        $flat = [];
        foreach ((array) $names as $chunk) {
            foreach (explode(',', $chunk) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $flat[] = $piece;
                }
            }
        }

        $subfolder = $this->option('subfolder');
        $apiVersion = $this->option('api-version');

        if (!$subfolder && !$apiVersion && count($flat) === 1
            && $this->confirm('Deploy this inside a custom subdirectory structure (e.g., v1/Admin)?', false)) {
            $subfolder = $this->ask('Enter the target subfolder naming structure:');
        }

        $jobs = [];
        foreach ($flat as $name) {
            $job = [
                'name'        => $name,
                'subfolder'   => $subfolder,
                'api_version' => $apiVersion,
                'force'       => (bool) $this->option('force'),
            ];

            if ($this->option('policy')) {
                $job['policy'] = true;
            }
            if ($this->option('tests')) {
                $job['tests'] = true;
            }
            if ($this->option('dto')) {
                $job['dto'] = true;
            }
            if ($this->option('exception')) {
                $job['exception'] = true;
            }

            $jobs[] = $job;
        }

        return $jobs;
    }

    protected function loadManifest(string $path): array
    {
        if (!File::exists($path)) {
            $this->error("Manifest file not found: {$path}");
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $this->error('Manifest file must contain valid JSON (an array of resource objects).');
            return [];
        }

        $jobs = [];
        foreach ($decoded as $entry) {
            if (!isset($entry['name'])) {
                $this->warn('Skipping manifest entry without a "name" key.');
                continue;
            }

            $jobs[] = [
                'name'        => $entry['name'],
                'subfolder'   => $entry['subfolder'] ?? null,
                'api_version' => $entry['api_version'] ?? null,
                'policy'      => $entry['policy'] ?? null,
                'tests'       => $entry['tests'] ?? null,
                'dto'         => $entry['dto'] ?? null,
                'exception'   => $entry['exception'] ?? null,
                'model'       => $entry['model'] ?? true,
                'force'       => $entry['force'] ?? (bool) $this->option('force'),
            ];
        }

        return $jobs;
    }

    protected function scaffoldResource(array $job, bool $interactive): void
    {
        $name = $job['name'];
        $dryRun = $job['dry_run'] ?? false;
        $force = $job['force'] ?? false;

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            $this->error("Skipping invalid resource name: [{$name}]");
            return;
        }

        $singularClass = Str::studly(Str::singular($name));
        $variableName  = Str::camel(Str::singular($name));

        [$namespaceFolder, $pathFolder, $routePrefix] = $this->resolveFolder(
            $job['subfolder'] ?? null,
            $job['api_version'] ?? null
        );

        $usePolicy    = $job['policy'] ?? false;
        $useTests     = $job['tests'] ?? false;
        $useDto       = $job['dto'] ?? false;
        $useException = $job['exception'] ?? false;

        if ($interactive) {
            if (!array_key_exists('policy', $job)) {
                $usePolicy = $this->confirm("Generate an authorization Policy for {$singularClass}?", false);
            }
            if (!array_key_exists('dto', $job)) {
                $useDto = $this->confirm('Use a typed Data Transfer Object instead of raw arrays?', false);
            }
            if (!array_key_exists('exception', $job)) {
                $useException = $this->confirm("Generate a dedicated conflict exception for {$singularClass}?", false);
            }
            if (!array_key_exists('tests', $job)) {
                $useTests = $this->confirm("Generate a Pest feature test for {$singularClass}?", false);
            }
        }

        $this->info('⚡ Scaffolding ' . $singularClass . ($dryRun ? ' [dry-run]' : '') . '...');

        $wantsModel = $interactive
            ? $this->confirm("Generate the underlying Eloquent Model & Migration for {$singularClass}?", true)
            : ($job['model'] ?? true);

        if ($wantsModel) {
            if ($dryRun) {
                $this->line("  [dry-run] would run: php artisan make:model {$singularClass} --migration");
            } else {
                $this->call('make:model', ['name' => $singularClass, '--migration' => true]);
            }
        }

        if ($usePolicy) {
            if ($dryRun) {
                $this->line("  [dry-run] would run: php artisan make:policy {$singularClass}Policy --model={$singularClass}");
            } else {
                $this->call('make:policy', ['name' => "{$singularClass}Policy", '--model' => $singularClass]);
            }
        }

        $dtoNamespace = config('api-boilerplate.namespaces.dtos') . ($namespaceFolder ? "\\{$namespaceFolder}" : '');
        $exceptionNamespace = config('api-boilerplate.namespaces.exceptions') . ($namespaceFolder ? "\\{$namespaceFolder}" : '');

        $tokens = [
            'modelClass'    => $singularClass,
            'variableName'  => $variableName,
            'folder'        => $namespaceFolder ? "\\{$namespaceFolder}" : '',
            'dtoImport'     => $useDto ? "use {$dtoNamespace}\\{$singularClass}Data;" : '',
            'dataType'      => $useDto ? "{$singularClass}Data" : 'array',
            'dataAccessor'  => $useDto ? '$data->toArray()' : '$data',
            'requestDataArg' => $useDto
                ? "{$singularClass}Data::fromArray(\$request->validated())"
                : '$request->validated()',
            'modelImport'   => $usePolicy ? "use App\\Models\\{$singularClass};" : '',
            'authorizeBody' => $usePolicy
                ? "\$ability = \$this->isMethod('post') ? 'create' : 'update';\n        return \$this->user()?->can(\$ability, {$singularClass}::class) ?? false;"
                : 'return true;',
            'routeUri' => ($routePrefix ? $routePrefix . '/' : '') . Str::kebab(Str::plural($singularClass)),
        ];

        $this->generateLayer(
            'request',
            $this->buildRelativePath(config('api-boilerplate.paths.requests'), $pathFolder, "{$singularClass}Request.php"),
            config('api-boilerplate.namespaces.requests') . ($namespaceFolder ? "\\{$namespaceFolder}" : ''),
            $singularClass, $tokens, $force, $dryRun
        );

        $this->generateLayer(
            'resource',
            $this->buildRelativePath(config('api-boilerplate.paths.resources'), $pathFolder, "{$singularClass}Resource.php"),
            config('api-boilerplate.namespaces.resources') . ($namespaceFolder ? "\\{$namespaceFolder}" : ''),
            $singularClass, $tokens, $force, $dryRun
        );

        $this->generateLayer(
            'service',
            $this->buildRelativePath(config('api-boilerplate.paths.services'), $pathFolder, "{$singularClass}Service.php"),
            config('api-boilerplate.namespaces.services') . ($namespaceFolder ? "\\{$namespaceFolder}" : ''),
            $singularClass, $tokens, $force, $dryRun
        );

        $this->generateLayer(
            'controller',
            $this->buildRelativePath(config('api-boilerplate.paths.controllers'), $pathFolder, "{$singularClass}Controller.php"),
            config('api-boilerplate.namespaces.controllers') . ($namespaceFolder ? "\\{$namespaceFolder}" : ''),
            $singularClass, $tokens, $force, $dryRun
        );

        if ($useDto) {
            $this->generateLayer(
                'dto',
                $this->buildRelativePath(config('api-boilerplate.paths.dtos'), $pathFolder, "{$singularClass}Data.php"),
                $dtoNamespace, $singularClass, $tokens, $force, $dryRun
            );
        }

        if ($useException) {
            $this->generateLayer(
                'exception',
                $this->buildRelativePath(config('api-boilerplate.paths.exceptions'), $pathFolder, "{$singularClass}ConflictException.php"),
                $exceptionNamespace, $singularClass, $tokens, $force, $dryRun
            );
        }

        if ($useTests) {
            $this->generateTest($singularClass, $variableName, $tokens['routeUri'], $force, $dryRun);
        }

        $this->appendRoute($singularClass, $namespaceFolder, $routePrefix, $dryRun);
    }

    /**
     * Resolve namespace folder (backslashes), filesystem path folder (forward
     * slashes), and route prefix from an optional subfolder + api-version.
     *
     * Kept as two distinct strings deliberately: reusing one string for both
     * namespace and filesystem path would cause subfolders like "v1/Admin" to
     * produce a broken literal directory named "v1\Admin" on Linux/macOS.
     */
    protected function resolveFolder(?string $subfolder, ?string $apiVersion): array
    {
        $parts = [];

        if ($apiVersion) {
            $parts[] = 'Api';
            $parts[] = Str::studly($apiVersion);
        }

        if ($subfolder) {
            $normalized = trim(str_replace('\\', '/', $subfolder), '/');
            foreach (explode('/', $normalized) as $segment) {
                if ($segment !== '') {
                    $parts[] = $segment;
                }
            }
        }

        $pathFolder = implode('/', $parts);
        $namespaceFolder = implode('\\', $parts);
        $routePrefix = $apiVersion ? Str::lower($apiVersion) : '';

        return [$namespaceFolder, $pathFolder, $routePrefix];
    }

    /**
     * Build a relative filesystem path, avoiding double slashes when no folder
     * is provided.
     */
    protected function buildRelativePath(string $basePath, string $folder, string $fileName): string
    {
        return $folder !== ''
            ? "{$basePath}/{$folder}/{$fileName}"
            : "{$basePath}/{$fileName}";
    }

    /**
     * Resolve a stub file, preferring a published/customized copy in the
     * host app over the one bundled inside this package.
     */
    protected function resolveStub(string $stubName): ?string
    {
        $published = rtrim(config('api-boilerplate.stub_path'), '/') . "/{$stubName}.stub";
        if (File::exists($published)) {
            return $published;
        }

        $bundled = __DIR__ . "/../../../stubs/api-boilerplate/{$stubName}.stub";
        return File::exists($bundled) ? $bundled : null;
    }

    protected function generateLayer(string $stubName, string $relativeAppPath, string $namespace, string $className, array $tokens, bool $force, bool $dryRun): void
    {
        $fullPath = app_path($relativeAppPath);

        $stubFile = $this->resolveStub($stubName);
        if (!$stubFile) {
            $this->error("Critical: stub template [{$stubName}.stub] could not be found (checked published path and package defaults).");
            return;
        }

        $contents = File::get($stubFile);

        $replacements = array_merge($tokens, [
            'namespace' => $namespace,
            'class'     => $className,
        ]);

        foreach ($replacements as $key => $val) {
            $contents = str_replace("{{{$key}}}", $val, $contents);
        }

        $this->writeFile($fullPath, $contents, $force, $dryRun, Str::headline($stubName) . ' layer');
    }

    protected function generateTest(string $class, string $variable, string $routeUri, bool $force, bool $dryRun): void
    {
        $testsPath = config('api-boilerplate.tests_path');
        $fullPath = "{$testsPath}/{$class}Test.php";

        $stubFile = $this->resolveStub('test');
        if (!$stubFile) {
            $this->error('Critical: stub template [test.stub] could not be found (checked published path and package defaults).');
            return;
        }

        $contents = File::get($stubFile);
        $contents = str_replace(
            ['{{modelClass}}', '{{routeUri}}', '{{variableName}}'],
            [$class, $routeUri, $variable],
            $contents
        );

        $this->writeFile($fullPath, $contents, $force, $dryRun, 'Pest feature test');
    }

    protected function writeFile(string $fullPath, string $contents, bool $force, bool $dryRun, string $label): void
    {
        if ($dryRun) {
            $note = File::exists($fullPath) ? ' (would overwrite)' : '';
            $this->line("  [dry-run] would write {$label}: {$fullPath}{$note}");
            return;
        }

        $directory = dirname($fullPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        if (File::exists($fullPath) && !$force) {
            $this->warn('[-] File skipped (already exists, use --force to overwrite): ' . basename($fullPath));
            return;
        }

        File::put($fullPath, $contents);
        $this->components->info("[+] {$label} built successfully: " . basename($fullPath));
    }

    protected function appendRoute(string $class, string $namespaceFolder, string $routePrefix, bool $dryRun): void
    {
        $routeFile = config('api-boilerplate.routes_file');
        if (!File::exists($routeFile)) {
            return;
        }

        $uri = ($routePrefix ? "{$routePrefix}/" : '') . Str::kebab(Str::plural($class));
        $controllerNamespace = config('api-boilerplate.namespaces.controllers')
            . ($namespaceFolder ? "\\{$namespaceFolder}" : '')
            . "\\{$class}Controller";

        $contents = File::get($routeFile);
        $uriPattern = "/apiResource\\(\\s*['\"]" . preg_quote($uri, '/') . "['\"]/";

        if (Str::contains($contents, $controllerNamespace) || preg_match($uriPattern, $contents)) {
            $this->warn("[-] A route for '{$uri}' already appears to exist; skipping append.");
            return;
        }

        if ($dryRun) {
            $this->line("  [dry-run] would append to routes/api.php: Route::apiResource('{$uri}', \\{$controllerNamespace}::class);");
            return;
        }

        File::append($routeFile, "\nRoute::apiResource('{$uri}', \\{$controllerNamespace}::class);");
        $this->components->info('[+] Route automatically registered in routes/api.php');
    }
}
