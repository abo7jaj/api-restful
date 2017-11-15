<?php

class root_method implements Method
{

    // LIST OF METHOD AS FUNCTION
    function POST(RestUtils &$api)
    {
        $api->sendResponse(200, 'root POST reached');
    }

    function GET(RestUtils &$api)
    {
        global $API_available;
        $api->metier->cache(3600 * 24); // return cache if available, 1 day
        $datas = array('links' => array());
        if (EDIT_MODE) {
            if ($API_available !== false) { // on envoie la liste de ce qui est authorisé
                foreach ($API_available as $method => $method_array) {
                    foreach ($method_array as $uri) {
                        $uri              = ($uri === 'root') ? '' : $uri;
                        $datas['links'][] = array(
                            'url' => $api->metier->buildURL('/'.$uri),
                            'method' => $method
                        );
                    }
                }
            } else { //il y a quoi de dispo dans la db ?
                $methods    = array('GET', 'POST', 'PUT', 'DELETE');
                $row_result = $api->db->query('SHOW TABLES WHERE Tables_in_'.DB_DATABASE.' NOT IN (\'api_auth_fail\', \'accounts\', \'activity\')');
                while ($row        = $api->db->fetch($row_result)) {
                    foreach ($methods as $method) {
                        $datas['links'][] = array(
                            'url' => $api->metier->buildURL($row['Tables_in_'.DB_DATABASE]),
                            'method' => $method
                        );
                    }
                }
            }
            $api->sendResponse(200, $datas);
        } else {
            $api->sendResponse(200, 'root GET reached');
        }
    }

    function PUT(RestUtils &$api)
    {
        $api->sendResponse(200, 'root PUT reached');
    }

    function DELETE(RestUtils &$api)
    {
        $api->sendResponse(200, 'root DELETE reached');
    }
}