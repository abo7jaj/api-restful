<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                                 USER SETUP                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
// edit mode 
// WARNING : must be turned to FALSE when API is configured 
define('EDIT_MODE', true);

// MySQLi access
define('DB_SERVER', '127.0.0.1');
define('DB_DATABASE', 'my_database');
define('DB_LOGIN', 'my_login');
define('DB_PASSWORD', 'my_password');

// 0 no log
// 1 error level
// 2 trace all
define('LOG_LEVEL', 0); // not implement yet
// relative log path
define('LOG_PATH', '../logs/');
// log file name 
define('LOG_FILE', 'api.log');
// SQL log file name 
define('LOG_FILE_SQL', 'api_sql.log');

// tmp directory, used for tmp files like session
define('TMP_PATH', '../tmp/');
if (!file_exists(TMP_PATH)) {
    mkdir(TMP_PATH);
}

// cache directory, used for cache files
define('CACHE_PATH', '../cache/');
if (!file_exists(CACHE_PATH)) {
    mkdir(CACHE_PATH);
}

// collect ip info from ip-api.com (150 max / minutes)
define('COLLECT_IP_INFO', false);

// where the activity goes:
// db or log
define('ACTIVITY_DESTINATION', 'db');

// offline image path directory
define('IMG_PATH', '../images');
if (!file_exists(IMG_PATH)) {
    mkdir(IMG_PATH);
}

//API restriction on URL
global $API_available;
// to disable restriction
//$API_available= false;
// to enable URL
$API_available = array('GET' => array(
        'root',
        'image',
        'img_dir',
        'activity',
        'authors'
    ), 'POST' => array(
        'image',
        'img_dir',
        'activity',
        'authors'
    ), 'PUT' => array(
        'image',
        'img_dir',
        'activity',
        'authors'
    ), 'DELETE' => array(
        'image',
        'img_dir',
        'activity',
        'authors'
    ), 'OPTIONS' => array(
    )
);


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                     API RESERVED SETUP, DO NOT TOUCH BELOW                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
// column names not allowed
define('RESERVED_WORDS',
    json_encode(array(
    'id',
    'id_',
    'created_time',
    'modified_time',
    'id_owner',
    'owner_type',
    'format'
)));

// output format
define('DEFAULT_FORMAT', 'application/json');

// default fields needed in table for generic use
define('DEFAULT_ID_FIELD', '`id`');
define('DEFAULT_FIELDS', DEFAULT_ID_FIELD.',`created_time`,`modified_time`');
