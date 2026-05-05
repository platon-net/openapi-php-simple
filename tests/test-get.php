<?php
/*
 * Example: public GET request.
 *
 * Run:
 * php tests/test-get.php
 */

require_once __DIR__.'/../src/API.php';

$api = new API('https://setup.platon.sk/api');

try {
	// Public endpoint from the OpenAPI specification.
	$response = $api->get('/system/hello');
	print_r($response);
} catch (Exception $e) {
	fwrite(STDERR, 'Request failed: '.$e->getMessage().PHP_EOL);
	exit(1);
}

?>
