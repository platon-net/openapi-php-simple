<?php
/*
 * Example: catching API errors.
 *
 * Run:
 * php tests/test-error-handling.php
 */

require_once __DIR__.'/../src/API.php';

$api = new API('https://setup.platon.sk/api');

try {
	// This endpoint requires Bearer auth, so an unauthenticated call should fail.
	$response = $api->get('/vehicle/events');
	print_r($response);
} catch (Exception $e) {
	echo 'Caught expected API error: '.$e->getMessage().PHP_EOL;
}

?>
