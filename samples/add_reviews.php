<?php

require 'api/includes/class/api.client.inc.php';
include 'api/samples/Kint/Kint.class.php';

$url = "http://" . $_SERVER['HTTP_HOST'] . "/api/modules/547/reviews/?api_key=" . MY_AUTH_KEY;
$datas = array(
    'author' => str_shuffle('Norbert'),
    'evaluation' => 4,
    'review_title' => str_shuffle('Title of the review'),
    'review_message' => 'one of the best review', // yes it will ad a column everytime !
    'dol_version' => '4.0.3'
);

echo $url . "<br /><br />";
$api = new apiClient();

$api->send($url, $datas, 'POST');
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);
