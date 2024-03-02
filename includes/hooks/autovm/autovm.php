<?php

use WHMCS\Service\Service;
use WHMCS\User\Client;
use WHMCS\Database\Capsule;

$path = dirname(__FILE__);

require $path . '/vendor/autoload.php';

require $path . '/service.php';
require $path . '/capsule.php';

@session_start();

function autovm_has_array($name, $array)
{
    if (array_key_exists($name, $array)) {

        return true;
    }

    return false;
}

function autovm_get_array($name, $array)
{
    if (autovm_has_array($name, $array)) {

        return $array[$name];
    }

    return null;
}

function autovm_has_query($name)
{
    if (autovm_has_array($name, $_GET)) {

        return true;
    }

    return false;
}

function autovm_get_query($name)
{
    if (autovm_has_query($name)) {

        return $_GET[$name];
    }

    return null;
}

function autovm_has_post($name)
{
    if (autovm_has_array($name, $_POST)) {

        return true;
    }

    return false;
}

function autovm_get_post($name)
{
    if (autovm_has_post($name)) {

        return $_POST[$name];
    }

    return null;
}

function autovm_get_post_array($names)
{
    $params = [];

    foreach($names as $name) {

        $params[$name] = autovm_get_post($name);
    }

    return $params;
}

function autovm_has_session($name)
{
    if (array_key_exists($name, $_SESSION)) {

        return true;
    }

    return false;
}

function autovm_get_session($name)
{
    if (autovm_has_session($name)) {

        return $_SESSION[$name];
    }

    return null;
}

function autovm_generate_string($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

    $result = '';

    for ($i=0; $i<$length; $i++) {

        $result .= $chars[mt_rand(0, strlen($chars)-1)];
    }

    return $result;
}

// Find the service identity
$serviceId = autovm_get_query('avmServiceId');

// Find action
$action = autovm_get_query('avmAction');

// Find the current logged in client
$client = autovm_get_session('uid');

if ($client) {
    $client = Capsule::getClient($client);

    if ($client) {
        $service = Capsule::getClientService($client->id, $serviceId);
    }
}

// Find the current logged in admin
$admin = autovm_get_session('adminid');

if ($admin) {
    $service = Capsule::getService($serviceId);
}

// Handle AutoVM requests
if ($service) {
    $response =  autovm_get_admintoken_baseurl_client();
    
    $DefLang = $response['DefLang'];
    // get Default Language
    if(empty($DefLang)){
        $DefLang = 'English';
    }
    
    if(($DefLang != 'English' && $DefLang != 'Farsi' && $DefLang != 'Turkish' && $DefLang != 'Russian' && $DefLang != 'Deutsch' && $DefLang != 'French' && $DefLang != 'Brizilian' && $DefLang != 'Italian')){
        $DefLang = 'English';
    }

    if(!empty($DefLang)){
        if(empty($_COOKIE['temlangcookie'])) {
            setcookie('temlangcookie', $DefLang, time() + (86400 * 30 * 12), '/');
        }
    }

    
    $controller = new AVMController($serviceId);
    $controller->handle($action);
} 
