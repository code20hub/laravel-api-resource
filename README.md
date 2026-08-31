# Laravel API Resource Scaffolder

Scaffold a full API layer - Form Request, API Resource, Service, Controller,
and optionally a Policy, DTO, conflict Exception, and Pest tests - from a
single `artisan` command.

## Before you publish this

This package ships with placeholder names. Rename before pushing to
Packagist / your own VCS:

1. In `composer.json`: change `"name"` from `code20/laravel-api-resource`
   to your actual vendor slug, and update the PSR-4 namespace + `extra.laravel.providers`
   entry if you rename the PHP namespace.
2. Find-and-replace `Code20\ApiBoilerplate` with your own namespace across
   `src/ApiBoilerplateServiceProvider.php` and
   `src/Console/Commands/MakeApiResourceCommand.php`.
3. Update the author info in `composer.json` and `LICENSE.md`.

## Installation

Local package during development (path repository), add to the **consuming
app's** `composer.json`:

```json
{
    "repositories": [
        { "type": "path", "url": "../laravel-api-resource" }
    ],
    "require": {
        "code20/laravel-api-resource": "*"
    }
}
```

```bash
composer require code20/laravel-api-resource
```

Once published to Packagist, that second step is all a consumer needs to run
- Laravel's package auto-discovery registers the service provider and the
`make:api-resource` command automatically. Nothing to add to `config/app.php`.

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
- Laravel 10, 11, or 12

## License

MIT — see [LICENSE.md](LICENSE.md).
