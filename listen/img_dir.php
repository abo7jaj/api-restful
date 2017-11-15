<?php

// this class will manipulate a $dir argument
class img_dir_method implements Method {

    // LIST OF METHOD AS FUNCTION
    function POST(RestUtils &$api) { //mkdir
        $args = $api->return_obj->getRequestVars(); // collect params
        $dir = $args['dir'];
        $api->metier->sanitize_dir_name($dir); // dot and slash are forbiden in dir_name
        if (!is_dir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $dir)) {
            mkdir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $dir);
            self::GET($api);
        } else {
            $api->sendResponse(405);
        }
    }

    function GET(RestUtils &$api) { // list dir
        $dirs = array();
        //$api->sendResponse(200, ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH);
        if (is_dir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH)) {
            if ($dh = opendir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_dir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $file) && $file != "." && $file != "..") {
                        $dirs[] = array("type" => "directory",
                            'name' => $file);
                    }
                }
                closedir($dh);
            } else {
                $api->sendResponse(405);
            }
        } else {
            $api->sendResponse(500);
        }
        $api->sendResponse(200, $dirs);
    }

    function PUT(RestUtils &$api) { // rename dir
        $args = $api->return_obj->getRequestVars(); // collect params
        $dir = $args['dir'];
        $rename = $args['rename'];
        $api->metier->sanitize_dir_name($dir); // dot and slash are forbiden in dir_name        
        $api->metier->sanitize_dir_name($rename); // dot and slash are forbiden in dir_name

        if (rename(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $dir, ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $rename)) {
            self::GET($api);
        } else {
            $api->sendResponse(304);
        }
    }

    function DELETE(RestUtils &$api) { // delete dir
        $args = $api->return_obj->getRequestVars(); // collect params
        $dir = $args['dir'];
        $api->metier->sanitize_dir_name($dir); // dot and slash are forbiden in dir_name
        if (rmdir(ROOT_PATH . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . $dir)) {
            self::GET($api);
        } else {
            $api->sendResponse(304);
        }
    }

}
