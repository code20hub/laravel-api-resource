# Agent Notes

This repo is a **Laravel package** (`code20/laravel-api-resource`), not a standalone application. It registers one Artisan command, `make:api-resource`, that scaffolds API layers in the *host* Laravel app.

## Local development

```bash
# Install dependencies (mostly for IDE/autoloading; the package has no runtime tests)
composer install

# Quick sanity check on PHP files
php -l src/Console/Commands/MakeApiResourceCommand.php
php -l src/ApiBoilerplateServiceProvider.php
php -l config/api-boilerplate.php
```

There is **no `phpunit.xml`, no CI config, and no test suite** in this package. The `--tests` flag generates Pest feature tests in the *consuming* app.

## How to actually run the command

You cannot run `php artisan make:api-resource` directly from this repo. To exercise or demo it:

1. Create or use a Laravel app.
2. Require this package locally:
   ```bash
   cd /path/to/host-laravel-app
   composer require code20/laravel-api-resource
   # or symlink a local checkout:
   composer config repositories.local '{"type": "path", "url": "/var/www/laravel-api-resource"}'
   composer require code20/laravel-api-resource:@dev
   ```
3. Run `php artisan make:api-resource post --tests --dry-run` inside the host app.

## Generated code assumes host-app conventions

The bundled stubs produce code that expects the host app to provide:

- `App\Http\Controllers\Controller` as the base controller.
- `App\Traits\HttpResponses` trait (used by the generated controller).
- `App\Models\{Model}` model class (generated via `make:model`).
- `routes/api.php` exists (route is appended here).
- Pest is installed when `--tests` is used.

If the host app diverges from these paths/namespaces, update `config/api-boilerplate.php` **or** publish and edit the stubs.

## Config and stub customization

```bash
php artisan vendor:publish --tag=api-boilerplate-config
php artisan vendor:publish --tag=api-boilerplate-stubs
php artisan vendor:publish --tag=api-boilerplate   # both at once
```

- Published stubs live in `stubs/api-boilerplate/` of the host app by default.
- If a published stub is missing, the command transparently falls back to the bundled package stub.
- The config defines both namespaces and filesystem paths for each generated layer; keep them in sync.

## Important implementation details

- **Omitting `--subfolder` places generated files at the configured layer root** (e.g. `App\Http\Controllers\PostController`), not inside a resource-named subfolder.
- **Namespace vs. filesystem path are resolved separately** (`resolveFolder()`). Reusing one string for both would break subfolders like `v1/Admin` on Unix systems.
- **`--api-version=v1`** adds an `Api\V1` namespace segment and prefixes routes with `v1/`.
- **`--subfolder=v1/Admin`** adds arbitrary nested folder/namespace segments; accepts `/` or `\` separators.
- **`--manifest=resources.json`** accepts an array of resource objects and skips interactive prompts.
- **`--exception`** generates a self-rendering exception with its own `render()` method; no global handler edit is required.
- Routes are appended only if neither the controller namespace nor the URI already appears in `routes/api.php`.

## Version constraints

- PHP `^8.1`
- Laravel `^10.0 | ^11.0 | ^12.0 | ^13.0`
