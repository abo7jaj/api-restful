<?php

//
require '../includes/class/api.client.inc.php';
include 'Kint/Kint.class.php';

$url = "https://" . $_SERVER['HTTP_HOST'] . "/authors/?api_key=" . MY_AUTH_KEY;
$datas = array(
    'name' => str_shuffle('Redfield'),
    'first_name' => str_shuffle('James'),
    'refused_value' => 'will ignore it'
);

echo "POST ". $url . "<br /><br />";
$api = new apiClient();

$api->send($url, $datas, 'POST');
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);

$id_to_delete = $api->decoded->data->id; 

echo 'Now we will delete author id '.$id_to_delete .' <br />A DELETE RETURNS NOTHING !!! (only a 204 code)<br /><br />';

$url = "https://" . $_SERVER['HTTP_HOST'] . "/authors/$id_to_delete/?api_key=" . MY_AUTH_KEY;

$api->send($url, array(), 'DELETE');


if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';