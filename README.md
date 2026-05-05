# openapi-php-simple

PHP library for easy integration with OpenAPI / REST APIs.

The repository contains a lightweight cURL client in `src/API.php`. It supports
JSON requests, JSON responses, Bearer authentication, query parameters, and
basic exception handling for transport, JSON, and HTTP errors.

## Installation

Clone the repository or copy `src/API.php` into your project:

```sh
git clone https://github.com/platon-net/openapi-php-simple.git
```

Include the client where you need it:

```php
require_once __DIR__.'/src/API.php';
```

The examples use the public OpenAPI server:

```php
$api = new API('https://setup.platon.sk/api');
```

## Basic Usage

### GET request

```php
require_once __DIR__.'/src/API.php';

$api = new API('https://setup.platon.sk/api');
$response = $api->get('/system/hello');

print_r($response);
```

### POST request

```php
require_once __DIR__.'/src/API.php';

$api = new API('https://setup.platon.sk/api');
$response = $api->post('/oauth/requests', array(
	'app_name' => 'My application',
	'app_url_homepage' => 'https://example.com',
	'app_url_return' => 'https://example.com/oauth-return',
	'scopes' => array('vehicle:read'),
));

print_r($response);
```

## Authentication

Pass an access token as the second constructor argument. The client sends it as
an `Authorization: Bearer ...` header.

```php
require_once __DIR__.'/src/API.php';

$api = new API('https://setup.platon.sk/api', 'YOUR_API_TOKEN');
$response = $api->get('/vehicle/events');

print_r($response);
```

Use tokens with the scopes required by the selected endpoint. Do not commit real
tokens to the repository.

## Examples

Example scripts are stored in `tests/` and can be run from the project root:

```sh
php tests/test-get.php
php tests/test-post.php
API_TOKEN=YOUR_API_TOKEN php tests/test-auth.php
php tests/test-error-handling.php
```

- `tests/test-get.php` calls public `GET /system/hello`.
- `tests/test-post.php` sends JSON to `POST /oauth/requests`.
- `tests/test-auth.php` shows Bearer token usage with `GET /vehicle/events`.
- `tests/test-error-handling.php` demonstrates `try` / `catch`.

## Compatibility and Error Handling

The library requires PHP with the cURL extension enabled. It is written in a
minimal style and is suitable for PHP 8+ projects.

Methods throw `Exception` when cURL fails, the response is not valid JSON, or
the API returns a non-2xx HTTP status. Wrap calls in `try` / `catch` when
handling user-facing flows.
