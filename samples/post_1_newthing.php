<?php

//
require '../includes/class/api.client.inc.php';
include 'Kint/Kint.class.php';

$url = "https://" . $_SERVER['HTTP_HOST'] . "/newthings/?api_key=" . MY_AUTH_KEY;
$datas = array(
    'name' => str_shuffle('unknown'),
    'first_name' => str_shuffle('James'),
    'accepted_value_'.rand(1,3) => 'will NOT be ignore in EDIT_MODE' // yes it will ad a column everytime !
);

echo $url . "<br /><br />";
$api = new apiClient();

$api->send($url, $datas, 'POST');
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);
