<?php

$boundary = "---------------------" . md5(mt_rand() . microtime());
//
require '../includes/class/api.client.inc.php';
include 'Kint/Kint.class.php';

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            . ($postname ?: basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}

$file = "./image.gif";

$args['file']  = curl_file_create($file, 'image/gif');

$url = "http://" . $_SERVER['HTTP_HOST'] . "/api/image/?api_key=" . MY_AUTH_KEY;
$datas = array(
    'test_file' => $args 
);

echo $url . "<br /><br />";
$api = new apiClient();

$api->send($url, $args, 'POST',"Content-Type: multipart/form-data;");
if (isset($api->decoded->response->status) && $api->decoded->response->status == 'ERROR') {
    die('error occured: ' . $api->decoded->response->errormessage);
}
echo 'response reached : <br />';
!Kint::dump($api->curl_response);
