<?php

/**
 * A bootstrap for the Dropbox SDK usage examples
 * @link https://github.com/BenTheDesigner/Dropbox/tree/master/examples
 */
// Prevent access via command line interface
if (PHP_SAPI === 'cli') {
	exit('bootstrap.php must not be run via the command line interface');
}

// Don't allow direct access to the boostrap
if(basename($_SERVER['REQUEST_URI']) == 'bootstrap.php'){
	exit('bootstrap.php does nothing on its own. Please see the examples provided');
}

// Set error reporting
error_reporting(-1);
ini_set('display_errors', 'On');
ini_set('html_errors', 'On');

// Register a simple autoload function
spl_autoload_register(function($class){
	$class = str_replace('\\', '/', $class);
	require_once('/'. $class . '.php');
});

// Set your consumer key, secret and callback URL
$key      = 'xbrjg41fzzqqm3c';
$secret   = 'wduvrd7p4ya6zin';

// Check whether to use HTTPS and set the callback URL
$protocol = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
$callback = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$tokenFile = unserialize(file_get_contents('oauth.token'));

// Instantiate the required Dropbox objects
$encrypter = new \Dropbox\OAuth\Storage\Encrypter('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
$storage = new \Dropbox\OAuth\Storage\Session($encrypter);
$storage->set($tokenFile['token'], 'access_token');
$OAuth = new \Dropbox\OAuth\Consumer\Curl($key, $secret, $storage, $callback);
$dropbox = new \Dropbox\API($OAuth);
