# Laravel API Resource Scaffolder

Scaffold a full API layer - Form Request, API Resource, Service, Controller,
and optionally a Policy, DTO, conflict Exception, and Pest tests - from a
single `artisan` command.

## Installation

```bash
composer require code20/laravel-api-resource
```

Once published to Packagist, that second step is all a consumer needs to run
- Laravel's package auto-discovery registers the service provider and the
`make:api-resource` command automatically. Nothing to add to `config/app.php`.

## Updating

After installation, pull in the latest version with:

```bash
composer update code20/laravel-api-resource
```

Or update all packages at once:

```bash
composer update
```

## Publishing config/stubs (optional)

The package works out of the box with its bundled stubs and defaults. To
customize either:

```bash
# config/api-boilerplate.php
php artisan vendor:publish --tag=api-boilerplate-config

# stubs/api-boilerplate/*.stub
php artisan vendor:publish --tag=api-boilerplate-stubs

# both
php artisan vendor:publish --tag=api-boilerplate
```

If a stub isn't published (or you delete a published one), the command
transparently falls back to the version bundled inside the package — nothing
breaks either way.

## Usage

```bash
# Single resource
php artisan make:api-resource post

# With extras
php artisan make:api-resource post --policy --dto --exception --tests

# Custom subfolder / API version
php artisan make:api-resource post --subfolder=v1/Admin
php artisan make:api-resource post --api-version=v1

# Multiple resources in one run
php artisan make:api-resource post comment tag --tests
php artisan make:api-resource post,comment,tag

# Bulk via manifest (skips interactive prompts)
php artisan make:api-resource --manifest=resources.json

# Preview only, writes nothing
php artisan make:api-resource post --policy --dto --dry-run
```

### Where files are generated

Without `--subfolder`, files are placed directly in each layer's default location:

```text
app/Http/Controllers/PostController.php
app/Http/Requests/PostRequest.php
app/Http/Resources/PostResource.php
app/Services/PostService.php
```

Pass `--subfolder` to nest resources under a custom directory instead:

```bash
php artisan make:api-resource post --subfolder=v1/Admin
```

This would generate:

```text
app/Http/Controllers/v1/Admin/PostController.php
app/Http/Requests/v1/Admin/PostRequest.php
app/Http/Resources/v1/Admin/PostResource.php
app/Services/v1/Admin/PostService.php
```

`--api-version=v1` still adds an `Api\V1` namespace segment and prefixes routes with `v1/`.

`resources.json` example:
```json
[
  { "name": "post", "policy": true, "dto": true, "tests": true },
  { "name": "comment", "subfolder": "v1/Admin" },
  { "name": "tag", "api_version": "v1", "exception": true }
]
```

### Notes

- `--exception` generates a `{Model}ConflictException` with its own
  `render()` method - Laravel calls that automatically, so no edits to your
  app's global exception handler are needed.
- `--dto` generates a generic typed wrapper (`{Model}Data`) around the
  validated array, with a TODO comment showing how to add explicit typed
  properties once your fields are finalized.
- `--policy` wires a basic `create`/`update` ability check into the Form
  Request based on HTTP verb; refine it for ownership-style checks that need
  the specific model instance.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12 or 13

## License

MIT — see [LICENSE.md](LICENSE.md).
