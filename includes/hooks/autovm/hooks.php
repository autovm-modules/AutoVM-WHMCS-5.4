<?php

use WHMCS\Database\Capsule;
use PG\Request\Request;
use WHMCS\User\Client;


function invoice_paid_traffic($vars) {

    $invoiceId = autovm_get_array('invoiceid', $vars);

    if (empty($invoiceId)) {
        return false;
    }

    $command = 'GetInvoice';

    $postData = array(
        'invoiceid' => $invoiceId
    );

    $invoice = localAPI($command, $postData);

    if (empty($invoice)) {
        return false;
    }

    $items = autovm_get_array('items', $invoice);

    if (empty($items)) {
        return false;
    }

    $items = autovm_get_array('item', $items);

    if (empty($items)) {
        return false;
    }

    foreach ($items as $item) {

        $description = autovm_get_array('description', $item);

        if (empty($description)) {
            continue;
        }

        $traffic = strripos($description, 'traffic');

        if (empty($traffic)) {
            continue;
        }

        $params = array(
            'invoiceid' => $invoiceId
        );

        $order = Capsule::selectOne('SELECT * FROM tblorders WHERE invoiceid = :invoiceid', $params);

        if (empty($order)) {
            continue;
        }

        $params = array(
            'orderid' => $order->id
        );

        $hosting = Capsule::selectOne('SELECT * FROM tblhostingaddons WHERE orderid = :orderid', $params);

        if (empty($hosting)) {
            continue;
        }

        // Service identity
        $serviceId = $hosting->hostingid;

        $params = array(
            'id' => $hosting->addonid
        );

        $addon = Capsule::selectOne('SELECT * FROM tbladdons WHERE id = :id', $params);

        if (empty($addon)) {
            continue;
        }

        $traffic = $addon->description;

        if (!is_numeric($traffic)) {
            continue;
        }

        $controller = new AVMController($serviceId);

        $machineId = $controller->getMachineIdFromService();

        if (empty($machineId)) {
            continue;
        }

        $duration = $controller->getServiceDuration();

        if (empty($duration)) {
            continue;
        }

        $remaining = $controller->getServiceRemaining();

        if (empty($remaining)) {
            $remaining = $duration;
        }

        $response = $controller->sendTrafficRequest($machineId, $traffic, $remaining, $duration, 'plus');

        if (empty($response)) {
            continue;
        }

        // var_dump($response);
    }
}

add_hook("InvoicePaid", 1, "invoice_paid_traffic");

function autovm_create_user($client, $BackendUrl)
{
    $params = ['name' => $client->fullName, 'email' => $client->email ];
    $address = [ $BackendUrl, 'candy', 'frontend', 'auth', 'token', 'register' ];
    return Request::instance()->setAddress($address)->setParams($params)->getResponse()->asObject();
}



function autovm_get_user_token($userId)
{
    $params = ['userId' => $userId];
    $user = Capsule::selectOne('SELECT token FROM autovm_user WHERE user_id = :userId', $params);
    return current($user);
}



// Get Token From AutoVm module
function autovm_get_admintoken_baseurl_client(){
    $response = [];

    // find Module aparams
    try {
        $moduleparams = Capsule::table('tbladdonmodules')->get();
        foreach ($moduleparams as $item) {
            if($item->module == 'autovm'){
                if($item->setting == 'BackendUrl'){
                    $BackendUrl = $item->value;
                }
                
                if($item->setting == 'AdminToken'){
                    $AdminToken = $item->value;
                }
                
                if($item->setting == 'DefLang'){
                    $DefLang = $item->value;
                }

                if($item->setting == 'CloudActivationStatus'){
                    $CloudActivationStatus = $item->value;
                }
                
                if($item->setting == 'ConsoleRoute'){
                    $ConsoleRoute = $item->value;
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Database ERR ===> Can not find module params table in database';
        $response['error'] = $error;
        return $response;
    }
    
    // if cloud is active
    if(isset($CloudActivationStatus)){
        $response['CloudActivationStatus'] = $CloudActivationStatus;
    }

    if(empty($BackendUrl)){
        $message = 'Backend URL ERR ===> Go to addons module and insert your backend adrress';
        $response['message'] = $message;
        return $response;
    }
    
    if(empty($AdminToken)){
        $message = 'Admin Token ERR ===> Go to addons module and insert your Token';
        $response['message'] = $message;
        return $response;
    }
   
    if(empty($DefLang)){
        $message = 'Defaul Language ERR ===> Go to addons module and select a language';
        $response['message'] = $message;
        return $response;
    }
    
    if(empty($ConsoleRoute)){
        $message = 'ConsoleRoute ERR ===> Go to addons module and insert ConsoleRoute';
        $response['message'] = $message;
        return $response;
    }

    if(isset($AdminToken) && isset($BackendUrl) && isset($DefLang) && isset($ConsoleRoute)){
        $response['AdminToken'] = $AdminToken;
        $response['BackendUrl'] = $BackendUrl;
        $response['DefLang'] = $DefLang;
        $response['ConsoleRoute'] = $ConsoleRoute;
        return $response;
    } 
}



// Hook to generate user and token in data base for cloud in client side
add_hook('ClientAreaPage', 100, function($params) {
    $response =  autovm_get_admintoken_baseurl_client();
    if(!empty($response['error'])){
        return false;
    }

    if(isset($response['CloudActivationStatus'])){
        $CloudActivationStatus = $response['CloudActivationStatus'];
    }

    if(!empty($response['message'])){
        return false;
    }
    
    if(isset($response['AdminToken']) && isset($response['BackendUrl'])){
        $AdminToken = $response['AdminToken'];
        $BackendUrl = $response['BackendUrl'];
    }
    
    // create token if cloud is active
    if(!empty($CloudActivationStatus) && !empty($AdminToken) && !empty($BackendUrl)){
        
        $clientId = autovm_get_session('uid');
        if (empty($clientId)) {
            // echo('can not find client ID');
            return false;
        }
    

        $client = Client::find($clientId);
        if(empty($client)) {
            echo('can not find the client');
            return false;
        }


        $token = autovm_get_user_token($clientId);
        if($token) {
            return false;
        }


        // create new user if can not find Token
        $CreateResponse = autovm_create_user($client, $BackendUrl);
        if(empty($CreateResponse)) {
            return false;
        }


        $message = property_exists($CreateResponse, 'message');
        if($message) {
            return false;
        }


        $user = $CreateResponse->data;

        // Save token in WHMCS
        $params = ['user_id' => $client->id, 'token' => $user->token];

        Capsule::table('autovm_user')
            ->insert($params);
            
    } else {
        return false;
    }
});



