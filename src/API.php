<?php
/*
 * API.php
 *
 * Lightweight cURL client for REST API.
 * https://github.com/platon-net/openapi-php-simple
 */

class API
{
	private $baseUrl;
	private $accessToken;
	private $timeout;
	private $sslVerifyPeer;
	private $sslVerifyHost;
	private $caFile;

	public function __construct($baseUrl, $accessToken = null, $timeout = 20, $options = array())
	{
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->accessToken = $accessToken;
		$this->timeout = $timeout;
		$this->sslVerifyPeer = !isset($options['ssl_verify_peer']) || (bool)$options['ssl_verify_peer'];
		$this->sslVerifyHost = !isset($options['ssl_verify_host']) || (bool)$options['ssl_verify_host'];
		$this->caFile = isset($options['ca_file']) ? $options['ca_file'] : null;
	}

	public function get($path, $query = array())
	{
		return $this->request('GET', $path, null, $query);
	}

	public function post($path, $data = array(), $query = array())
	{
		return $this->request('POST', $path, $data, $query);
	}

	public function patch($path, $data = array(), $query = array())
	{
		return $this->request('PATCH', $path, $data, $query);
	}

	public function delete($path, $query = array())
	{
		return $this->request('DELETE', $path, null, $query);
	}

	private function request($method, $path, $data = null, $query = array())
	{
		if (!function_exists('curl_init')) {
			throw new Exception('CURL not installed in PHP');
		}

		$url = $this->baseUrl.'/'.ltrim($path, '/');
		$query = $this->filterEmpty($query);
		if (!empty($query)) {
			$url .= '?'.http_build_query($query);
		}

		$headers = array(
			'Accept: application/json',
			'X-Forwarded-For: '.$this->getClientIP(),
		);
		if ($data !== null) {
			$headers[] = 'Content-Type: application/json';
		}
		if ($this->accessToken !== null && strlen($this->accessToken) > 0) {
			$headers[] = 'Authorization: Bearer '.$this->accessToken;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost ? 2 : 0);
		if ($this->sslVerifyPeer && $this->caFile !== null && strlen($this->caFile) > 0) {
			curl_setopt($ch, CURLOPT_CAINFO, $this->caFile);
		}
		if ($data !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}

		$body = curl_exec($ch);
		$curlError = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false) {
			throw new Exception($curlError);
		}

		$response = null;
		if (strlen($body) > 0) {
			$response = json_decode($body, true);
			if ($response === null) {
				throw new Exception('Invalid JSON response from Control Panel API');
			}
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			throw new Exception($this->errorMessage($response, $httpCode));
		}

		return $response;
	}

	private function filterEmpty($data)
	{
		if (!is_array($data)) {
			return array();
		}
		foreach ($data as $key => $value) {
			if ($value === null || $value === '') {
				unset($data[$key]);
			}
		}
		return $data;
	}

	private function errorMessage($response, $httpCode)
	{
		if (is_array($response)) {
			if (isset($response['msg']) && strlen($response['msg']) > 0) {
				return $response['msg'];
			}
			if (isset($response['message']) && strlen($response['message']) > 0) {
				return $response['message'];
			}
			if (isset($response['error']) && strlen($response['error']) > 0) {
				return $response['error'];
			}
		}
		return 'Control Panel API HTTP '.$httpCode;
	}

	private function getClientIP()
	{
		foreach (array('REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP') as $key) {
			if (isset($_SERVER[$key]) && strlen($_SERVER[$key]) > 0) {
				return trim(stripslashes($_SERVER[$key]));
			}
		}
		return '';
	}
}

?>
