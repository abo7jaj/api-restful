<?php
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: x-requested-with');

define('ROOT_PATH', __DIR__); // root path useful in future

require 'includes/configuration.php';
require 'includes/core/api.class.inc.php';
$api = new RestUtils();

// loading User listening files
foreach (glob(ROOT_PATH."/listen/*.php") as $filename) {
    include $filename;
}

// traitement
$retour = $api->processRequest();
