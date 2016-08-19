<?php
/**
 * @file
 *   Provides a class to communicate with the Hola extension API.
 */
abstract class HolaAgent {
	/**
	 * The current API URL of Hola.
	 */
	CONST URL = 'https://client.hola.org/client_cgi';

	/**
	 * Default options for cURL requests.
	 * @var array
	 */
	public static $CURL_OPTIONS = array(
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2228.0 Safari/537.36',
		CURLOPT_HTTPHEADER => array(
			'Accept-Language: de',
			'origin: chrome-extension://gkojfkhlekighikafcpjkiklfbnlmeio'
		),
	);


	/**
	 * Generates an unique ID to communicate with the Hola API.
	 *
	 * @return string The UUID
	 */
	public function generateUuid() {
		$uuid = '';
		for ($i = 0; $i < 16; $i++) {
			$int = mt_rand(0, 255);
			$uuid .= (($int < 15) ? 0 : '') . dechex($int);
		}
		return $uuid;
	}

	/**
	 * Wrapper to perform a cURL request.
	 *
	 * @param string $endpoint The endpoint to use
	 * @param null|array $data If this is not null cURL will perform a post request with the provided data
	 * @return bool|object The JSON response or false if something went wrong
	 */
	private function _curl($endpoint, $data = NULL) {
		$ch = curl_init();


		$options = self::$CURL_OPTIONS + array(
				CURLOPT_URL => self::URL . $endpoint,
			);

		// If data is set, it is a post request
		if (isset($data) && !is_null($data)) {
			$options = $options + array(
					CURLOPT_POST => TRUE,
					CURLOPT_POSTFIELDS => $data,
				);
		}

		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);

		// If no certificate found, use the provided one
		// see http://curl.haxx.se/docs/sslcerts.html
		if (curl_errno($ch) == 60) {
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/ca_bundle.crt');
			$result = curl_exec($ch);
		}

		// Check for a good cURL request
		if ($result === false || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			curl_close($ch);
			return false;
		}

		curl_close($ch);

		$result = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($result));
		$result = json_decode($result);
		return json_last_error() == JSON_ERROR_NONE ? $result : false;
	}


	/**
	 * Simple wrapper for post request
	 * @param string $endpoint The endpoint to use
	 * @param array $data The post data to send
	 * @return bool|object The JSON response or false if something went wrong
	 */
	public function post($endpoint, $data) {
		return self::_curl($endpoint, $data);
	}

	/**
	 * Simple wrapper for get request
	 * @param string $endpoint The endpoint to use
	 * @return bool|object The JSON response or false if something went wrong
	 */
	public function get($endpoint) {
		return self::_curl($endpoint);
	}

}