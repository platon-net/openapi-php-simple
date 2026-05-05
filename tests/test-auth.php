<?php
/*
 * Example: authenticated request with Bearer token.
 *
 * Run:
 * API_TOKEN=YOUR_API_TOKEN php tests/test-auth.php
 */

require_once __DIR__.'/../src/API.php';

$token = getenv('API_TOKEN');
if ($token === false || strlen($token) === 0) {
	$token = 'YOUR_API_TOKEN';
}

$api = new API('https://setup.platon.sk/api', $token);

try {
	// Protected endpoint; replace the token with one that has vehicle:read scope.
	$response = $api->get('/vehicle/events', array(
		'since' => '2026-01-01 00:00:00',
	));
	print_r($response);
} catch (Exception $e) {
	fwrite(STDERR, 'Request failed: '.$e->getMessage().PHP_EOL);
	if ($token === 'YOUR_API_TOKEN') {
		fwrite(STDERR, 'Set API_TOKEN or edit the placeholder token before running this example.'.PHP_EOL);
	}
	exit(1);
}

?>
