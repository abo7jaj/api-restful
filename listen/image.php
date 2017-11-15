<?php


class image_method implements Method {

    // LIST OF METHOD AS FUNCTION
    function POST(RestUtils &$api) {
        $selection = $api->__path[$api->program_name]; // upload on /image/XX/ not allowed
        if ($selection != 0) {// ?as a value == 0
            $api->sendResponse(405);
        }
        $sub_directory = "";
        $args = $api->return_obj->getRequestVars(); // collect values posted

        if (isset($args['directory'])) {
            $sub_directory = (string) $api->ctrl->sanitize_dir_name($api->db->sanitize($args['directory']));
        }
        if (!isset($_FILES['image'])) { // check if there is a table
            $api->sendResponse(400);
        }
        $filename = $_FILES['image']['name'];
        $filetype = $_FILES['image']['type'];
        $filename = strtolower($filename);
        $filetype = strtolower($filetype);

        //check if contain php and kill it 
        $pos = strpos($filename, 'php');
        if (!($pos === false)) {
            $api->sendResponse(417, array('error' => 'PHP inside'));
        }
        //get the file ext
        $file_ext = strrchr($filename, '.');

        //check if its allowed or not
        $whitelist = array(".jpg", ".jpeg", ".gif", ".png");
        if (!(in_array($file_ext, $whitelist))) {
            $api->sendResponse(417, array('error' => 'extension not allowed'));
        }

        //check upload type
        $pos = strpos($filetype, 'image');
        if ($pos === false) {
            $api->sendResponse(417, array('error' => 'Not an image'));
        }
        $imageinfo = getimagesize($_FILES['image']['tmp_name']);
        if ($imageinfo['mime'] != 'image/gif' && $imageinfo['mime'] != 'image/jpeg' && $imageinfo['mime'] != 'image/jpg' && $imageinfo['mime'] != 'image/png') {
            $api->sendResponse(417);
        }
        //check double file type (image with comment)
        if (substr_count($filetype, '/') > 1) {
            $api->sendResponse(417, array('error' => 'image already inside'));
        }

        //change the image name
        $uploadfile = md5(basename($_FILES['image']['name'])) . '_' . uniqid() . $file_ext;




        if (!move_uploaded_file($_FILES['image']['tmp_name'], IMG_PATH . DIRECTORY_SEPARATOR . $sub_directory . DIRECTORY_SEPARATOR . $uploadfile)) {
            $api->sendResponse(417, array('error' => 'Impossible to move file to '.IMG_PATH . DIRECTORY_SEPARATOR . $sub_directory . DIRECTORY_SEPARATOR . $uploadfile.', no directory ?'));
        }

        $datas = array();
        $datas['owner_type'] = 'accounts'; // set who is the owner of this data
        $datas['id_owner'] = $api->auth->id; // the auth key corresponding id

        $datas['file_name'] = $uploadfile;
        $datas['mime_type'] = $imageinfo['mime'];
        $datas['directory'] = $sub_directory;

        $id = $api->db->insert($api->program_name, $datas);

        if ($id !== FALSE) {
            $api->sendResponse(200, array('id' => $id, 'url' => $api->metier->buildURL("image/{$id}")));
        } else {
            $api->sendResponse(500);
        }
    }

    function GET(RestUtils &$api) {
        // get arguments passed in url, filter, limit, order, etc
        $filters = $api->return_obj->getRequestVars();
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }
        $selection = $api->__path[$api->program_name]; // ?as a value != 0
        $fields = $api->metier->get_fields_from_current_table();
        $where = $order = " ";
        if ($selection != 0) {
            $where .= "WHERE id = '" . $api->db->sanitize($selection) . "' ";
        } elseif (isset($filters['filter'])) {
            $search = array(); // list of search
            foreach ($filters['filter'] as $key => $value) {
                if (in_array($key, $fields)) {
                    $search[] = "LOWER(`" . $api->db->sanitize($key) . "`) LIKE LOWER('%" . $api->db->sanitize($value) . "%')";
                }
            }
            // if we received a non associative array, we will search in all fields
            if (count($search) == 0) {
                foreach ($fields as $column) {
                    $search[] = "LOWER(`$column`) LIKE LOWER('%" . $api->db->sanitize($filters['filter']) . "%')";
                }
            }
            $where .= "WHERE " . implode(' OR ', $search);
        } else {
            $where .= "WHERE 1 ";
        }
        if (isset($filters['order_by'])) {
            $order .= " ORDER BY `" . $api->db->sanitize($filters['order_by']) . "` ";
        } else {
            $order = " ORDER BY `id` ";
        }
        if (isset($filters['order'])) {
            $order .= $api->db->sanitize($filters['order']) . " ";
        } else {
            $order .="ASC ";
        }
        //    $api->sendResponse(200, $filters);
        $row_result = $api->db->query('SELECT `id`, `' . implode('`, `', $fields) . '` FROM ' . $api->program_name . $where . $order);
        $output = array();
        while ($row = $api->db->fetch($row_result)) {
            $output[] = array("type" => $api->program_name) + $row + array('url' => "https://" . $_SERVER['HTTP_HOST'] . "/api/images/{$row['id']}/?api_key=" . $api->auth->api_key);
        }

        if (count($output) == 0) {// no image
            $api->sendResponse(444);
        } elseif ($selection != 0) {
            $file_db_info = $output[0];
            //header('Content-Disposition: Attachment;filename=image.png'); // img to download 
            header("Content-type: {$file_db_info['mime_type']}");
            header('Content-disposition: filename="' . $file_db_info['file_name'] . '"');
            try {
                $image_relative = (($file_db_info['directory'] != "") ? $file_db_info['directory'] . DIRECTORY_SEPARATOR : "") . $file_db_info['file_name'];
                $image_path = IMG_PATH . DIRECTORY_SEPARATOR . $image_relative;

                // load with corresponding function
                switch ($file_db_info['mime_type']) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($image_path);
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($image_path);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($image_path);
                        // preserve alpha
                        imagealphablending($image, false);
                        imageSaveAlpha($image, true);
                        break;
                }
                //echo function image
                $function_echo = array(
                    'image/jpeg' => 'imagejpeg',
                    'image/gif' => 'imagegif',
                    'image/png' => 'imagepng'
                );

                if (isset($filters['width']) || isset($filters['height'])) {

                    // image size
                    $srcWidth = imagesx($image);
                    $srcHeight = imagesy($image);

                    $newWidth = (isset($filters['width'])) ? intval($filters['width']) : $srcWidth;
                    $newHeight = (isset($filters['height'])) ? intval($filters['height']) : $srcHeight;

                    // avoid width= 0 
                    $newWidth = max($newWidth, 1);
                    $newHeight = max($newHeight, 1);

                    $newImg = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($newImg, false);
                    imagesavealpha($newImg, true);
                    $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                    imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
                    imagecopyresampled($newImg, $image, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

                    $function_echo[$file_db_info['mime_type']]($newImg);
                    // clean up RAM
                    imagedestroy($newImg);
                } else {
                    //print with corresponding function 
                    $function_echo[$file_db_info['mime_type']]($image);
                    // clean up RAM
                    imagedestroy($image);
                }
                die();
            } catch (Exception $exc) {
                // report possible attack on malformed image
                $api->report_ip($api->auth->key, 'Malformed image');
                $api->sendResponse(304, 'image malformed');
            }
        } else {
            $api->sendResponse(200, array("data" => $output));
        }
    }

    function PUT(RestUtils &$api) { // not possible to modify a picture
        $api->sendResponse(304);
    }

    function DELETE(RestUtils &$api) {
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }
        $selection = $api->__path[$api->program_name]; // ?as a value != 0
        if ($selection != 0) {
            $info = $api->db->delete($api->program_name, $selection, '*', true);
            $response = $api->db->delete($api->program_name, $selection);
            if ($response->affected_rows) {
                unlink(IMG_PATH . DIRECTORY_SEPARATOR . $info['directory'] . DIRECTORY_SEPARATOR . $info['file_name']);
            }
            $api->sendResponse(204);
        } else {
            $api->sendResponse(304);
        }
    }

}
