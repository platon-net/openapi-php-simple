<?php
/*
 * Example: public POST request with JSON body.
 *
 * Run:
 * php tests/test-post.php
 */

require_once __DIR__.'/../src/API.php';

$api = new API('https://setup.platon.sk/api');

$payload = array(
	'app_name' => 'openapi-php-simple example',
	'app_url_homepage' => 'https://example.com',
	'app_url_return' => 'https://example.com/oauth-return',
	'scopes' => array('vehicle:read'),
);

try {
	// Creates an OAuth request and sends $payload as JSON.
	$response = $api->post('/oauth/requests', $payload);
	print_r($response);
} catch (Exception $e) {
	fwrite(STDERR, 'Request failed: '.$e->getMessage().PHP_EOL);
	exit(1);
}

?>
