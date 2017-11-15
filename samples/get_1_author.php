<?php



require '../includes/class/api.client.inc.php';
include 'Kint/Kint.class.php';

$url = "https://".$_SERVER['HTTP_HOST']."/authors/1/?api_key=" . MY_AUTH_KEY;

echo $url ."<br /><br />";
$api = new apiClient();

$api->send($url);
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);
