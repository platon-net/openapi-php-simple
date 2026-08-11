# LLM Spam Detection using the Control Panel API

The Control Panel API provides an LLM-based endpoint that classifies text submitted through a web form. It can distinguish unsolicited or abusive content (**spam**) from legitimate messages (**ham**) before the message enters your normal processing workflow.

The integration uses an ordinary JSON request and is suitable for PHP applications, WordPress plugins, custom content management systems, and existing websites. This tutorial starts with the API client and ends with a complete form-processing example.

## 1. Download the API client

The simplest way to integrate the endpoint is to use the lightweight [openapi-php-simple client](https://github.com/platon-net/openapi-php-simple). It has no Composer dependencies and uses PHP's cURL extension to send JSON requests and decode JSON responses.

Download [`src/API.php`](https://github.com/platon-net/openapi-php-simple/blob/main/src/API.php) and save it in your project, for example as:

```text
src/API.php
```

The examples below assume that your PHP script and the `src` directory have the same parent directory. Adjust the path in `require_once` if your project has a different layout.

## 2. Create an API key

Requests to the spam-detection endpoint must use a Control Panel API key.

1. Open the [API list](https://setup.platon.sk/?module=API&action=list) in the Control Panel.
2. Select [Create API key](https://setup.platon.sk/?module=API&action=new).
3. Enable both of the following scopes:
   - **LLM read**
   - **LLM complete**
4. Create the key and store its token securely.

The spam-detection operation requires LLM permissions and will not work if these scopes are missing. Treat the token as a secret: do not commit it to source control or expose it in browser-side code. The PHP request should run on your server.

## 3. Initialize the client

Load the downloaded class and create an `API` instance:

```php
require_once __DIR__.'/src/API.php';

$api = new API(
    'https://setup.platon.sk/api',
    'YOUR_API_TOKEN'
);
```

The first constructor argument is the Control Panel API base URL. The second is your API token; the client sends it in the `Authorization: Bearer YOUR_API_TOKEN` header. Replace `YOUR_API_TOKEN` with a securely loaded token in production, preferably from an environment variable or server-side configuration.

## 4. Detect spam

The `POST /llm/spam-detection` endpoint accepts a JSON object with one required field, `message`. According to the OpenAPI specification, `message` must be a string and may contain at most 20,000 characters.

The following example submits text stored in `$message`:

```php
$message = 'Your contact-form message goes here.';

$response = $api->post('/llm/spam-detection', [
    'message' => $message,
]);
```

The client JSON-encodes the array and sends it with `Content-Type: application/json`. The normalized result is available at:

```php
$response['data']['classification']
```

For a successfully classified message, its value is:

- `spam` — the message is classified as unwanted content;
- `ham` — the message is classified as legitimate content.

A simple decision can therefore be written as:

```php
if ($response['data']['classification'] === 'spam') {
    // reject message
} else {
    // accept message
}
```

The OpenAPI schema also permits `unknown`. This value is used when the service cannot produce a valid classification, so production integrations should not automatically accept it as ham. Check `data.error` and route an unknown result to manual review or another safe fallback.

## 5. Confidence score

The response includes a normalized confidence value at:

```php
$response['data']['confidence']
```

It is a number from `0` to `100` representing the model's confidence in the returned classification. A higher value means the model is more certain that its `spam` or `ham` label is correct. For example:

- a confidence near `95` indicates a high-confidence classification;
- a confidence near `70` indicates a useful result with more uncertainty;
- a confidence near `50` is borderline and may deserve manual review.

These ranges are application-policy examples, not thresholds imposed by the API. Advanced applications can combine `classification` and `confidence` to choose an action. For instance, they might reject high-confidence spam, place lower-confidence spam in moderation, and accept only sufficiently confident ham. Monitor real traffic before selecting thresholds because confidence scores are useful decision signals but are not guaranteed to be perfectly calibrated probabilities.

## 6. Response format

The OpenAPI specification defines a successful HTTP response as a JSON object with an API envelope and a nested classification result. It does not provide fixed example values, so the following is an illustrative `200` response that follows the defined schema exactly:

```json
{
  "status": "OK",
  "retval": 0,
  "msg": "Spam detection completed",
  "data": {
    "classification": "ham",
    "confidence": 96,
    "reason": "Správa obsahuje konkrétnu otázku bez znakov nevyžiadanej reklamy.",
    "signals": [
      "konkrétna otázka",
      "bez reklamných odkazov"
    ],
    "error": false,
    "error_message": ""
  },
  "requestId": 123456
}
```

The fields have the following meanings:

- `status` is the API-level status string.
- `retval` is the API-level integer return value.
- `msg` is the API-level response message.
- `data` contains the spam-detection result and may be `null` according to the schema.
- `data.classification` is `spam`, `ham`, or `unknown`.
- `data.confidence` is the normalized confidence score from `0` to `100`.
- `data.reason` is a short explanation in Slovak.
- `data.signals` is an array of the most important signals used for classification.
- `data.error` is `true` if the LLM call or its response validation failed.
- `data.error_message` contains a short technical reason when `data.error` is `true`. The raw LLM response is never included.
- `requestId` is the integer request identifier and may be `null`.

All six fields inside a non-null `data` object are required by the OpenAPI schema. Do not base automated decisions solely on `status`; validate `data`, `data.error`, and `data.classification` as well.

## 7. Error handling

The PHP client throws an `Exception` in these situations:

- a cURL transport or network failure occurs;
- the server returns invalid JSON;
- the server returns an HTTP status outside the 2xx range.

An invalid or expired API token and missing scopes normally result in an HTTP authentication or authorization error. An invalid request, such as a missing `message` field or a message exceeding 20,000 characters, can result in an HTTP validation error. The client uses the API response's `msg`, `message`, or `error` field as the exception message when available; otherwise, it reports `Control Panel API HTTP <status>`.

Wrap the request in `try`/`catch`, and separately check application-level errors returned in a successful HTTP response:

```php
try {
    $response = $api->post('/llm/spam-detection', [
        'message' => $message,
    ]);

    if (!isset($response['data']) || !is_array($response['data'])) {
        throw new Exception('Spam detection returned no result data');
    }

    if ($response['data']['error']) {
        throw new Exception(
            'Spam detection failed: '.$response['data']['error_message']
        );
    }

    $classification = $response['data']['classification'];
} catch (Exception $e) {
    error_log('Control Panel API error: '.$e->getMessage());
    // Show a generic message to the visitor or send the submission for review.
}
```

Avoid displaying raw exception messages to public form users because they may expose operational details. Log them on the server, then fail safely according to your application policy. For example, keep the submission for manual review instead of silently discarding it when the API is unavailable.

## 8. Complete example

The following server-side example reads `message` from a submitted form, validates it, initializes the client, calls the endpoint, checks the classification, and prints a result. Set the `CONTROL_PANEL_API_TOKEN` environment variable before running it.

```php
<?php

require_once __DIR__.'/src/API.php';

$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($message === '') {
    http_response_code(400);
    echo 'Please enter a message.';
    exit;
}

$messageLength = function_exists('mb_strlen')
    ? mb_strlen($message, 'UTF-8')
    : strlen($message);

if ($messageLength > 20000) {
    http_response_code(400);
    echo 'The message is too long.';
    exit;
}

$apiToken = getenv('CONTROL_PANEL_API_TOKEN');

if ($apiToken === false || $apiToken === '') {
    http_response_code(500);
    echo 'Spam detection is not configured.';
    exit;
}

$api = new API(
    'https://setup.platon.sk/api',
    $apiToken
);

try {
    $response = $api->post('/llm/spam-detection', [
        'message' => $message,
    ]);

    if (!isset($response['data']) || !is_array($response['data'])) {
        throw new Exception('Spam detection returned no result data');
    }

    $result = $response['data'];

    if ($result['error']) {
        throw new Exception('Spam detection failed: '.$result['error_message']);
    }

    if ($result['classification'] === 'spam') {
        echo 'The message was rejected as spam.';
    } elseif ($result['classification'] === 'ham') {
        echo 'The message was accepted.';
    } else {
        // The documented third value is "unknown". Do not accept it as ham.
        http_response_code(202);
        echo 'The message requires manual review.';
    }
} catch (Exception $e) {
    error_log('Control Panel API error: '.$e->getMessage());
    http_response_code(503);
    echo 'The message could not be checked automatically. Please try again later.';
}
```

Only the server-side PHP code should have access to the token. Once the integration is working, replace the example output with your application's normal workflow—for example, storing accepted messages, placing uncertain messages in a moderation queue, and rejecting spam without sending it to staff.
