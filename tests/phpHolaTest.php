<?php
class phpHolaTest extends PHPUnit_Framework_TestCase {

	public function testWrongSession() {
		$hola = new Hola('fail', 'fail');
		$this->assertNull($hola->uuid);
	}

	public function testInitSessionAndReuseSession() {
		$hola = new Hola();
		$this->assertInternalType('string', $hola->sessionKey, 'Init new session failed.');

		if(is_string($hola->sessionKey)) {
			$hola = new Hola($hola->uuid, $hola->sessionKey);
			$this->assertInternalType('string', $hola->sessionKey, 'Reusing sessions failed.');
		}
	}

	public function testGetTunnel() {
		$hola = new Hola();
		$result = $hola->getTunnels();
		$this->assertInternalType('array', $result, 'Getting tunnel failed');
	}

}