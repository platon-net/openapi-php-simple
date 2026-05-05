# Repository Guidelines

## Project Structure & Module Organization

This repository is a small PHP library for REST/OpenAPI APIs.

- `src/API.php` contains the `API` cURL client class and all current library behavior.
- `tests/` contains standalone CLI usage examples, not PHPUnit tests.
- `README.md` documents installation, GET/POST usage, authentication, and examples.
- `LICENSE` contains the project license.

Keep library code under `src/`. Place executable examples under `tests/` using descriptive `test-*.php` names.

## Build, Test, and Development Commands

There is no Composer, npm, or Make configuration. Use direct PHP checks:

```sh
php -l src/API.php
php -l tests/test-get.php
php -l tests/test-post.php
php -l tests/test-auth.php
php -l tests/test-error-handling.php
```

Runs PHP syntax validation for the library and example scripts.

```sh
php tests/test-get.php
php tests/test-post.php
API_TOKEN=YOUR_API_TOKEN php tests/test-auth.php
php tests/test-error-handling.php
```

Runs examples against `https://setup.platon.sk/api`. Use placeholder or environment tokens only.

When a test runner is added, document its root-level command here.

## Coding Style & Naming Conventions

Match `src/API.php`:

- Use tabs for indentation.
- Use classic PHP array syntax, for example `array('Accept: application/json')`.
- Use camelCase for methods and private helpers, such as `filterEmpty()` and `errorMessage()`.
- Keep the public API small and explicit: `get()`, `post()`, `patch()`, `delete()`.
- Throw `Exception` for transport, JSON, and HTTP failures.
- In examples, include the client with `require_once __DIR__.'/../src/API.php';`.

Avoid unrelated refactors when changing behavior; this project is intentionally compact.

## Testing Guidelines

No test framework is configured yet. Files in `tests/` are runnable examples:

- `test-get.php` calls public `GET /system/hello`.
- `test-post.php` sends JSON to `POST /oauth/requests`.
- `test-auth.php` demonstrates Bearer auth with `GET /vehicle/events`.
- `test-error-handling.php` catches an expected unauthorized response.

Until automated tests exist, run `php -l` on changed PHP files and one relevant example script.

## Commit & Pull Request Guidelines

Local Git history only shows the clone record, so use clear imperative commit messages, for example `Add API request timeout handling`.

Pull requests should include a short description, reason for the change, test results, and compatibility notes. Link related issues when available. For behavior changes, include a minimal usage example or affected method.

## Security & Configuration Tips

Do not commit access tokens, API keys, or endpoint-specific secrets. Pass credentials through constructor arguments or environment-specific configuration outside the repository.
