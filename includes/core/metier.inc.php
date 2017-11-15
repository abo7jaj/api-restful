<?php

/**
 * Description of metier
 *
 * @author Focoweb
 */
class Metier extends Database
{
    public $now, $api;

    public function __construct(&$api)
    {
        $this->now   = date('Y-m-d H:i:s');
        $this->api   = $api;
        // debug trace
        $this->debug = TRUE;
        if ($this->debug) $this->log   = new Log(1, 0, LOG_FILE);
    }

    // get the called app associated table fields
    public function get_fields_from_current_table($full = false)
    {
        $fields_result = $this->api->db->query('SHOW COLUMNS FROM '.$this->api->program_name.' WHERE Field NOT IN("'.implode('", "',
                json_decode(RESERVED_WORDS)).'")');
        $fields        = array();
        while ($row           = $this->api->db->fetch($fields_result)) {
            if ($full) {// give all info
                $fields[] = $row;
            } else {    // give minimal info
                $fields[] = $row['Field'];
            }
        }
        return $fields;
    }

    public function adjust_create_table($table_name)
    {
        if (!$this->api->db->table_exists($table_name)) { // check if there is a table
            $this->api->metier->create_table($table_name, 'Auto created');
        }
        $this->api->metier->adjust_table($table_name, $this->api->return_obj->getRequestVars());
    }

    public function create_table($table_name, $comment = "")
    {
        $sql         = "CREATE TABLE IF NOT EXISTS `$table_name` (
              ".DEFAULT_ID_FIELD." int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `id_owner` int(11) unsigned DEFAULT NULL,
              `owner_type` varchar(256) COLLATE utf8_bin NOT NULL,
              `created_time` timestamp NULL DEFAULT NULL,
              `modified_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='$comment'";
        $sql_trigger = "CREATE TRIGGER `{$table_name}_creation_timestamp` BEFORE INSERT ON `$table_name`
         FOR EACH ROW SET NEW.`created_time` = NOW()";
        $this->api->db->query($sql);
        $this->api->db->query($sql_trigger);
    }

    public function adjust_table($table_name, $fields)
    {
        $known_fields = $this->api->metier->get_fields_from_current_table();
        foreach ($fields as $column => $value) {
            if (!in_array($column, $known_fields)) {
                $sql = "ALTER TABLE `$table_name` ADD `$column` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL";
                $this->api->db->query($sql);
            }
        }
    }

    public function add_activity()
    {
        $datas = array(
            'uri' => strtok($_SERVER["REQUEST_URI"], '?'),
            'id_auth' => $this->api->auth->id,
            'method' => $this->api->return_obj->getMethod(),
            'ip' => $this->api->log->get_ip(),
            'class' => $this->api->program_to_launch
        );
        if (COLLECT_IP_INFO) {
            try {
                $client          = new apiClient();
                $client->timeout = 5;
                $client->send("http://ip-api.com/json/".$datas['ip']);
                if ($client->decoded->status == "success") {
                    $ip_infos          = json_decode($client->curl_response, true);
                    $ip_infos['as_ip'] = $ip_infos['as']; // reserved word in mysql
                    unset($ip_infos['as']); // reserved word in mysql
                    unset($ip_infos['query']); // not usefull
                    unset($ip_infos['status']); // not usefull
                    $datas             += $ip_infos;
                }
            } catch (Exception $exc) {

            }
        }
        switch (ACTIVITY_DESTINATION) {
            default:
            case 'db':
                if ($_SERVER['SERVER_ADDR'] != $datas['ip']) { // do not trace own activity for faster access
                    $this->api->db->insert('activity', $datas);
                }
                break;
            case 'log':
                $this->api->log->logging($datas, 'ACTIVITY');
                break;
        }
    }

    /**
     * Get the remaining amount of working (business) days in the current month
     * from the current datew
     * @author Mike Zriel
     * @date   7 March 2011
     * @website www.zriel.com
     */
    public function getRemainingBusinessDays()
    {
        $d        = date("j");
        $m        = date("n");
        $days     = 0;
        $skipDays = array("Sat", "Sun");
        while (date("n", mktime(0, 0, 0, $m, $d, date("Y"))) == $m) {
            if (!in_array(date("D", mktime(0, 0, 0, $m, $d, $y)), $skipDays)) {
                $days++;
            }
            $d++;
        }
        return $days;
    }

    public function info_utf8($text)
    {
        $text = html_entity_decode(html_entity_decode($text));
        $text = $this->convertEncoding($text);
        $text = $this->rewriteImgUrl($text);
        $text = str_replace("&rsquo;", "&#8217;", $text);
        $text = str_replace("&hellip;", "...", $text);
        return $text;
    }

    function convertEncoding($val)
    {

        if (empty($val) || strlen($val) < 1) {
            return '';
        }

        //$v = mb_detect_encoding($val);
        $v = !(bool) preg_match('//u', $val);

        if ($v) {
            //$val = mb_convert_encoding($val, 'UTF-8', $v);
            $val = utf8_encode($val);
        }

        return $val;
    }

    public function rewriteImgUrl($text)
    {
        // useful if you provide html content
        //$text = str_replace('src="../img/', 'src="'.$this->api->protocol.'://www.domain.com/img/', $text);
        return $text;
    }

    public function session_start()
    {
        if (isset($_GET['api_key']) && !isset($_GET['token'])) {
            $this->api->token = $this->api->ctrl->my_personnal_key();
            $this->redirect($this->buildURL($_SERVER['REQUEST_URI']));
        } elseif (isset($_GET['api_key']) && isset($_GET['token'])) {
            $this->api->token = $this->api->db->sanitize(urlencode($_GET['token']));
        } else {
            $this->api->token = $this->api->ctrl->my_personnal_key();
        }
        $this->api->session_file_name = TMP_PATH.basename($this->api->token.'.session');
        if (file_exists($this->api->session_file_name)) {
            $this->api->session = json_decode(file_get_contents($this->api->session_file_name), true);
        } else {
            $this->api->session = array();
            file_put_contents($this->api->session_file_name, json_encode($this->api->session));
        }
    }

    public function buildURL($uri)
    {
        if ($uri[0] != '/') $uri    = '/'.$uri;
        $qtpos  = strpos($uri, '?');
        $qt     = ($qtpos === FALSE) ? '?' : ''; // uri?
        $args   = array();
        if (strpos($uri, 'api_key=', $qtpos) === FALSE) $args[] = 'api_key='.$this->api->auth->api_key;
        if (strpos($uri, 'token=', $qtpos) === FALSE) $args[] = 'token='.$this->api->token;
        if ($qt == '' & count($args) > 0) $qt     = '&';
        return $this->api->server.$uri.$qt.implode('&', $args);
    }

    public function redirect($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            header('Access-Control-Allow-Origin: *');
            header("Location: ".$url, true, 302);
            die();
        } else {
            $this->api->sendResponse(500);
        }
    }

    public function __destruct()
    {
        file_put_contents($this->api->session_file_name, json_encode($this->api->session));
    }

    public function cache($max_cache_age = 0)
    {
        // params
        parse_str($_SERVER['QUERY_STRING'], $query_string);
        unset($query_string['token']);
        unset($query_string['api_key']);
        $query = http_build_query($query_string);

        // url
        $dis_url = $_SERVER['REQUEST_URI'];
        $uri     = trim(trim(strtok($dis_url, '?')), '/');
        $uri     = str_replace('/', '-', $uri);

        // cache filename
        $cache_file = $uri.((count($query_string) > 0) ? "+$query" : '').".cache.";
        switch ($this->api->format) {
            case 'text/xml':
                $cache_file .= "xml";
                break;
            case 'text/html':
                $cache_file .= "html";
                break;

            case 'text/csv':
                $cache_file .= "csv";
                break;

            case 'text/plain':
                $cache_file .= "txt";
                break;

            case 'application/json':
            case 'application/vnd.api+json':
            case 'text/json':
            default:
                $cache_file .= "json";
                break;
        }



        if (file_exists(CACHE_PATH.$cache_file)) {
            if ($max_cache_age == 0) {
                $not_expired = true;
            } else {
                $file_age    = time() - filemtime(CACHE_PATH.$cache_file);
                $not_expired = $file_age <= $max_cache_age;
            }
            if ($not_expired) {
                $this->api->status = 200;
                $status_header     = 'HTTP/1.1 '.$this->api->status.' '.$this->api->getStatusCodeMessage($this->api->status);
                // set the status
                header($status_header);

                // set the content type
                header('Content-type: '.$this->api->format);
                $content         = file_get_contents(CACHE_PATH.$cache_file);
                $count           = null;
                $contentReplaced = preg_replace('/token=([1-9a-zA-Z]{1,256})(["\'&])/', 'token='.$this->api->token.'$2',
                    $content, -1, $count);
                $this->api->sendResponse($this->api->status, $contentReplaced);
            } else {
                $this->api->make_cache = CACHE_PATH.$cache_file;
            }
        } else {
            $this->api->make_cache = CACHE_PATH.$cache_file;
        }
    }
}