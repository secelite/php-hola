# Hola PHP
This is a simple library built to communicate with the Hola API of the Chrome browser extension.

It can retrieve proxy information by country code including host, port, ip and auth information.

## Usage
Include src/hola.php to your script.
```php
<?php
// Create a new session
$agent = new Hola();
// Get current session information (uuid and session key). You may store and reuse them
$session = $agent->getSession();
// Get a proxy by country code
$proxy = $agent->getTunnels('cz');
// Test the proxy against ipify
if($proxy) {
	$url = 'http://api.ipify.org/';
	$auth = $proxy['user'] . ':' . $proxy['password'];
	$proxy = $proxy['host'] . ':' . $proxy['port'];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_PROXY, $proxy);
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$content = curl_exec($ch);
	curl_close($ch);
	echo $content;
}
?>
```

## Documentation
Currently there is no documentation. But the source code is well documentated and should be self explaining.
Also note that error handling is very weak. You may customize it.


## License
MIT


## Legal
This code is in no way affiliated with, authorized, maintained, sponsored or endorsed by Hola or any of its affiliates or subsidiaries. This is an independent and unofficial API. I, the project owner and creator, am not responsible for any legalities that may arise in the use of this project. Use at your own risk.