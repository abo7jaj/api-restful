<?php

//
require '../includes/class/api.client.inc.php';
include 'Kint/Kint.class.php';

$url = "http://" . $_SERVER['HTTP_HOST'] . "/panier/?api_key=" . MY_AUTH_KEY;
$datas = array(); // not transmitted followed on redirect

echo $url . "<br /><br />";
$api = new apiClient();

$api->send($url, $datas, 'POST');
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);
