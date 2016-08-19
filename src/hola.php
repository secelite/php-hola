<?php
require_once dirname(__FILE__) . '/hola_agent.php';

/**
 * @file
 *   Provides a simple class to validate and create sessions and get proxies (tunnels) by country from the Hola API.
 *   Requires HolaAgent.
 */
class Hola extends HolaAgent {

	/**
	 * The current version of the extension
	 */
	CONST VERSION = '1.15.713';

	/**
	 * The product
	 */
	CONST PRODUCT = 'cws';

	/**
	 * The browser
	 */
	CONST BROWSER = 'chrome';

	/**
	 * @var string $uuid The uuid used in requests
	 */
	public $uuid;

	/**
	 * @var string $uuid The session key used in requests
	 */
	public $sessionKey;


	/**
	 * @var array $endpoints Currently used endpoints
	 */
	private $_endpoints = array(
		'validateSession' => '/rules_get_vpn.json', // using this for validating our uuid & session key
		'initSession' => '/background_init',
		'getTunnels' => '/zgettunnels',
	);


	/**
	 * Hola constructor.
	 * Accepts uuid and session key as argument to reuse a session.
	 *
	 * @param null|string $uuid An uuid
	 * @param null|string $sessionKey A session key
	 */
	public function __construct($uuid = null, $sessionKey = null) {
		return $this->initSession($uuid, $sessionKey);
	}


	/**
	 * Will build a proper query string for the specified endpoint.
	 * Also attaches some parameters which are always sent with every request.
	 *
	 * @param string $endpoint A valid endpoint as specified in $_endpoints
	 * @param array $query An array of query parameters to attach
	 * @return bool|string The endpoint with proper query or false if the endpoint is unknown
	 */
	private function _buildEndpoint($endpoint, $query = array()) {
		if (!isset($this->_endpoints[$endpoint])) return false;

		$query = array(
				'rmt_ver' => self::VERSION,
				'ext_ver' => self::VERSION,
				'browser' => self::BROWSER,
				'product' => self::PRODUCT,
				'lccgi' => 1,
			) + $query;

		$queryString = isset($query) ? '?' . http_build_query($query) : '';

		return $this->_endpoints[$endpoint] . $queryString;
	}

	/**
	 * Queries an endpoint and returns the result.
	 *
	 * @param string $endpoint A valid endpoint as specified in $_endpoints
	 * @param array $query Additional query parameters
	 * @param null|array $data Array of post data
	 * @return bool|object
	 */
	private function _queryEndpoint($endpoint, $query = array(), $data = null) {

		$result = false;

		if ($endpoint = $this->_buildEndpoint($endpoint, $query)) {
			if (is_array($data)) {
				// POST
				$result = parent::post($endpoint, $data);
			} else {
				// GET
				$result = parent::get($endpoint);
			}
		}
		return $result;
	}


	/**
	 * Simple request to validate a session by its uuid and session key.
	 * If no uuid or session key is provided, the class internals wil be taken.
	 *
	 * @param null|string $uuid The uuid
	 * @param null|string $sessionKey The session key
	 * @return bool True if the session is valid or false if invalid
	 */
	private function _validateSession($uuid = null, $sessionKey = null) {
		$uuid = isset($uuid) ? $uuid : $this->uuid;
		$sessionKey = isset($sessionKey) ? $sessionKey : $this->sessionKey;
		$result = $this->_queryEndpoint('validateSession', array('uuid' => $uuid, 'session_key' => $sessionKey));
		return $result !== false;
	}

	/**
	 * Sets up a new session or tries to reuse an existing, if the credentials are still valid.
	 * Returns false if provided uuid and session key are not valid.
	 *
	 * @param null|string $uuid
	 * @param null|string $sessionKey
	 * @return bool True if everything works, false if an error occurred or the session key was not provided in response
	 */
	public function initSession($uuid = null, $sessionKey = null) {
		if (!empty($uuid) && !empty($sessionKey)) {
			if ($this->_validateSession($uuid, $sessionKey)) {
				$this->uuid = (string) $uuid;
				$this->sessionKey = (string) $sessionKey;
				return true;
			}
		} else {
			// init to get session key

			// generate uuid
			$this->uuid = parent::generateUuid();

			// post
			$result = $this->_queryEndpoint('initSession', array('uuid' => $this->uuid), array(
				'login' => '1',
				'flags' => '0',
				'ver' => self::VERSION,
			));

			// check for response
			if (isset($result->key) && $result->key !== 0) {
				$this->sessionKey = (string)$result->key;
				return true;
			} else {
				$this->uuid = null;
			}
		}
		return false;
	}


	/**
	 * Returns the current session credentials if set, otherwise false.
	 *
	 * @return array|bool Array of uuid and session key or false if not set
	 */
	public function getSession() {
		if (!empty($this->uuid) && !empty($this->sessionKey)) {
			return array(
				'uuid' => $this->uuid,
				'sessionKey' => $this->sessionKey,
			);
		}
		return false;
	}

	/**
	 * Gets a proxy(tunnel) from the provided country.
	 * The return includes host, port, IP, agent key and auth credentials for the proxy to connect.
	 *
	 * @param string $countryCode A valid country code. Defaults to US
	 * @return array|bool The array with all proxy info or false
	 */
	public function getTunnels($countryCode = 'us') {
		$result = $this->_queryEndpoint('getTunnels', array('uuid' => $this->uuid, 'session_key' => $this->sessionKey, 'country' => strtolower($countryCode)));
		if ($result) {
			if (isset($result->ztun->{$countryCode}[0]) && isset($result->agent_key)) {
				// extract host & port
				preg_match('/([a-zA-Z0-9_\.]{1,}\:\d{1,5})/', $result->ztun->{$countryCode}[0], $match);
				if (isset($match, $match[0])) {
					list($host, $port) = explode(':', $match[0]);

					// bonus: get the displayed ip
					$ip = isset($result->ip_list->{$host}) ? $result->ip_list->{$host} : false;

					return array(
						'host' => $host,
						'port' => $port,
						'ip' => $ip,
						'agentKey' => $result->agent_key,
						'user' => sprintf('user-uuid-%s', $this->uuid),
						'password' => $result->agent_key,
					);
				}
			}
		}

		return false;
	}
}