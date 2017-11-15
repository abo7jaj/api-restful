<?php
/**
 * \file        api.class.inc.php
 * \author      Norbert Penel
 * \version     1.0
 * \date        18 aout 2016
 * \brief       Définit le coeur de l'API et l'interface des fonctions utilisateurs
 *
 * \details    Cette classe surcharge les accesseurs standards du module_voiture pour
 *                  convenir aux spécificités des différents modèles possibles.
 */
require_once (ROOT_PATH.'/includes/core/db.driver.inc.php');
require_once (ROOT_PATH.'/includes/core/metier.inc.php');
require_once (ROOT_PATH.'/includes/core/control.inc.php');
require_once (ROOT_PATH.'/includes/core/library.php');
require_once (ROOT_PATH.'/includes/class/api.client.inc.php');
// get response message from definition file
require_once (ROOT_PATH.'/includes/core/messages_returned.php');

class RestUtils
{
    public $status, $program_to_lauch, $program_name;
    public $fcts, $return_obj, $__path;
    public $db;
    public $metier, $ctrl; // sub classes
    public $error; // errors comming from control class
    public $auth; // info setup during auth
    public $debug, $log; // log 
    public $format; // format specified by argument

    /**
     * \brief       main starting function
     * \details    this will load all classes
     * \param    NULL
     * \return   TRUE
     */

    public function __construct()
    {
        $this->debug    = 3; // 0 off, 1 error, 2 error + warning, 3 error + warning + info
        $this->__path   = $this->decode_url();      // clean datas
        $this->protocol = isset($_SERVER['HTTPS']) ? "https" : "http";
        $this->server   = $this->protocol."://{$_SERVER['HTTP_HOST']}";
        $this->error    = array();                  // empty error = no error
        $this->db       = new Database();           // singleton for mysql
        $this->client   = new apiClient();          // client to use API calls
        $this->metier   = new Metier($this);        // over class to access database by
        $this->ctrl     = new Control();            // class to control fields
        $this->log      = new Log(1, 0, LOG_FILE);  // class to control fields
        $this->metier->session_start();
        return true;
    }

    public function processRequest()
    {
        global $API_restriction;
        if ($this->is_banned()) { // si t es blacklisté tu dégages et on te dis que t as foiré ton auth
            $this->report_ip($_GET['api_key'], 'IP refused');
            $this->sendResponse(401);
            die();
        }
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        //header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Headers: Cache-Control, Pragma, Origin, Authorization, Content-Type, X-Requested-With');
//      header('Access-Control-Max-Age: 1000');
        // authentication required 
        // or by basic user password
        // or by api_key
        if (isset($_GET['api_key'])) {
            $keys = $this->db->select('accounts', array('api_key' => $_GET['api_key']));
            if (count($keys) == 1) {// auth ok
                unset($_GET['api_key']);
                $this->auth = $keys[0];
            } else {
                if ($this->debug >= 2) // debug warning
                        $this->log->logging("Auth fail for key '{$_GET['api_key']}'", "AUTH");
                $this->report_ip($_GET['api_key'], 'KEY unknown (OR doublon)');
                $this->sendResponse(401);
                die();
            }
        } else {
            $password = md5(@$_SERVER['PHP_AUTH_PW']);
            $user     = @$_SERVER['PHP_AUTH_USER'];
            $users    = $this->db->select('accounts', array('login' => "$user", 'password' => "$password"));
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('WWW-Authenticate: Basic realm="API login as to be requested to enchantier"');
                header('HTTP/1.0 401 Unauthorized');
                echo json_encode('you have to type or provide user / password couple');
                if ($this->debug >= 2) // debug warning
                        $this->log->logging("Basic auth fail no user provided : URL ".var_export($this->__path, true),
                        "AUTH");
                $this->report_ip('no user', 'Empty User');
                die();
            } elseif (count($users) == 1) {// auth ok
                $this->auth = $users[0];
            } else {
                if ($this->debug >= 2) // debug warning
                        $this->log->logging("Basic auth fail for user '$user' : URL {$this->__path}", "AUTH");
                $this->report_ip("$user", 'User unknown');
                $this->sendResponse(401);
                die();
            }
        }
        // is there a format specified
        if (isset($_GET['format'])) {
            $format = strtoupper($_GET['format']);
            switch ($format) {
                case 'HTML':
                    $this->format = 'text/html';
                    break;
                case 'XML':
                    $this->format = 'text/xml';
                    break;
                case 'CSV':
                    $this->format = 'text/csv';
                    break;
                case 'PHP':
                default:
                    $this->format = 'text/plain';
                    break;
                case 'JSON':
                    $this->format = 'application/vnd.api+json';
                    break;
            }
            unset($_GET['format']); // clear this output argument
        } else {
            // default format
            $this->format = DEFAULT_FORMAT;
        }

        // reporting auth user to Metier class
        $this->db->fetch_mode = "object";
        $this->auth           = $this->db->select('accounts', array('id' => $this->auth['id']), "*", true);
        $this->db->fetch_mode = "assoc";
        $this->metier->auth   = &$this->auth;

        if ($this->debug >= 3) // debug info
                $this->log->logging("Auth success for user '{$this->auth->name} ({$this->auth->id})'", "AUTH");

        // get our verb
        $request_method = strtoupper($_SERVER['REQUEST_METHOD']);
        // only keep $API_restriction needed
        if (isset($API_available) && isset($API_available[$request_method]) && $API_available !== false) {
            $API_restriction = $API_available[$request_method];
        } else {
            $API_restriction = false;
        }

        $return_obj = new RestRequest();
        // we'll store our data here
        $data       = array();

        switch ($request_method) {
            // gets are easy...
            case 'GET':
                $data = $_GET;
                break;
            // so are posts
            case 'POST':
                $data = $_POST;
                break;
// 						case 'PUT':
//                 $data = $_REQUEST;
//                 break;
            // here's the tricky bit...
            default :

                // basically, we read a string from PHP's special input location,
                // and then parse it out into an array via parse_str... per the PHP docs:
                // Parses str  as if it were the query string passed via a URL and sets
                // variables in the current scope.
                parse_str(file_get_contents('php://input'), $put_vars);
                $data = $put_vars;
                break;
        }
        // store the method
        $return_obj->setMethod($request_method);

        // set the raw data, so we can access it if needed (there may be other pieces to your requests)
        $return_obj->setRequestVars($data);

        if (isset($data['data'])) {
            // translate the JSON to an Object for use however you want
            $return_obj->setData(json_decode($data['data']));
        }
        $this->return_obj = $return_obj;

        if ($this->debug >= 3) { // debug info
            $urlCalled = '/';
            foreach ($this->__path as $key => $value) {
                $urlCalled .= "$key/$value/";
            }
            $this->log->logging("Data $request_method on '$urlCalled' ".serialize($data), "CONTENT");
        }


        // look if there is something to run
        $run = false;
        if ($this->is_allowed()) { // checking if it's open time and allowed provider
            $keys                    = array_keys($this->__path);
            $this->program_to_launch = array_pop($keys);
            $this->program_name      = $this->db->sanitize($this->program_to_launch);
            //run the proper API zone
            if (file_exists('listen/'.$this->program_to_launch.'.php')) {
                if ($API_restriction === false || in_array($this->program_to_launch, $API_restriction)) { // is allowed by IP
                    require_once ('listen/'.$this->program_to_launch.'.php');
                    $program_to_lauch = $this->program_to_launch.'_method'; //php syntax requirement
                    $this->log->logging("Running $request_method $program_to_lauch", "LAUNCH");
                    $this->metier->add_activity(); // trace for stats
                    $program_to_lauch::$request_method($this);
                } else { // not authorized
                    $this->log->logging("NOT running $request_method {$this->__path}", "LAUNCH");
                    $this->report_ip($this->auth->key, 'Not authorized');
                    $this->sendResponse(403);
                }
            } else {
                if ($API_restriction === false || in_array($this->program_to_launch, $API_restriction)) { // is allowed by IP
                    if ($this->program_to_launch != 'root') {
                        $this->program_to_launch = 'default';
                    }
                    require ('includes/URL/'.$this->program_to_launch.'.php');
                    $program_to_lauch = $this->program_to_launch.'_method'; //php syntax requirement
                    $this->log->logging("Running $request_method $program_to_lauch", "LAUNCH");
                    $this->metier->add_activity(); // trace for stats
                    $program_to_lauch::$request_method($this);
                } else { // not authorized
                    $this->log->logging("NOT running $request_method {$this->__path}", "LAUNCH");
                    $this->report_ip($this->auth->key, 'Not authorized');
                    $this->sendResponse(403);
                }
            }
            $this->sendResponse(404, 'The API failed and return nothing');
        } else { // not authorized
            $this->report_ip($this->auth->key, 'Not authorized');
            $this->sendResponse(403);
        }
        // if nothing to run, send 404 template
        if ($run === false) {
            $this->log->logging("NOT running $request_method {$this->__path}", "LAUNCH");
            $this->report_ip($this->auth->key, 'Nothing to lauch');
            $this->sendResponse(404);
        }
    }

    public function sendResponse($status = 200, $body = '')
    {
        if (isset($this->make_cache)) {
            ob_start();
        }
        $this->status = $status;
        // pages with body are easy
        if ($body != '') {
            // send the body
            if (is_array($body)) {
                $this->format_response($body);
            } else {
                echo $body;
            }
        }
        // we need to create the body if none is passed
        else {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch ($status) {
                case 400:
                    $message = 'data malformed';
                    break;
                case 401:
                    $message = 'You must be authorized to view this fucking page.';
                    break;
                case 404:
                    $app_uri = $_SERVER['REQUEST_URI'];
                    if ($pos_get = strpos($app_uri, '?')) $app_uri = substr($app_uri, 0, $pos_get);
                    $message = 'The requested URL '.$app_uri.' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // error returned
            $body = array('error' => $this->status." ".$this->getStatusCodeMessage($this->status), 'message' => "$message");
            $this->format_response($body);
        }
        if (isset($this->make_cache)) {
            $content = ob_get_contents();
            file_put_contents($this->make_cache, $content);
            ob_end_flush();
        }
        die();
    }

    private function format_response($body)
    {
        // add signature 
        if ($_SERVER['SERVER_SIGNATURE'] != '') {
            $body['signature'] = $_SERVER['SERVER_SIGNATURE'];
        }
        switch ($this->format) {
            case 'text/xml':

                $xml = new SimpleXMLElement('<xml/>');
                foreach ($body as $zone => $datas) {
                    if ($zone == "data") {
                        $row = $xml->addChild($zone);
                        foreach ($datas as $key => $value) {
                            if (is_int($key)) {
                                $key  = $value['type'];
                                $row2 = $row->addChild($key);
                                foreach ($value as $key2 => $value2) {
                                    $row2->addChild($key2, $value2);
                                }
                            } else {
                                $row->addChild($key, $value);
                            }
                        }
                    } else {
                        $xml->addChild($zone, print_r($datas, true));
                    }
                }

                $output = $xml->asXML();
                break;

            case 'text/html':
                $output = "<html><head><meta charset='UTF-8'></head><body>";
                foreach ($body as $zone => $datas) {
                    $output .= "<h1>$zone </h1><table id='$zone' border='1'>";
                    if ($zone == "data") {
                        foreach ($datas as $key => $value) {
                            if (is_int($key)) {
                                if ($key == 0) {
                                    $output .= "<tr><th>".implode('</th><th>', array_keys($value))."</th></tr>";
                                }
                                $output .= "<tr><td>".implode('</td><td>', $value)."</td></tr>";
                            } else {
                                if (!filter_var($value, FILTER_VALIDATE_URL) === false) {
                                    $value = "<a href='$value'>$value</a>";
                                }
                                $output .= "<tr><th>$key</th><td>$value</td></tr>";
                            }
                        }
                    } else {
                        $output .= "<tr><th>$zone</th><td>".print_r($datas, true)."</td></tr>";
                    }
                    $output .= '</table>';
                }
                $output .= "</body></html>";
                break;

            case 'text/csv':
                // create a file pointer connected to the output stream
                ob_start();
                $pointer = fopen('php://output', 'w');
                // output the column headings
                fputcsv($pointer, array('Key', 'Value'));
                foreach ($body['data'] as $key => $value) {
                    // output the column headings

                    if (is_int($key)) {
                        fputcsv($pointer, $value);
                    } else {
                        fputcsv($pointer, array($key, $value));
                    }
                }
                fclose($pointer);
                $output = ob_get_clean();
                break;

            case 'text/plain':
                $output = serialize($body);
                break;

            case 'application/json':
            case 'application/vnd.api+json':
            case 'text/json':
            default:
                $output = json_encode($body);
                break;
        }
        $status_header = 'HTTP/1.1 '.$this->status.' '.$this->getStatusCodeMessage($this->status);
        // set the status
        header($status_header);

        // set the content type
        header('Content-type: '.$this->format);
        echo $output;
    }

    public function getStatusCodeMessage($status)
    {
        global $return_code;
        return (isset($return_code[$status])) ? $return_code[$status] : $return_code;
    }

    public function attach($url, $method, $funct)
    {
        //echo "$url $method\n";
        $this->fcts["$url"]["$method"] = $funct;
        return true;
    }

    private function is_allowed()
    {
        return $this->auth->state == "1";
    }

    private function report_ip($auth = '', $abuse = '')
    {
        $auth  = substr("$auth", 0, 254); // db max size to be sure to log it
        $abuse = substr("$abuse", 0, 254); // db max size to be sure to log it
        $datas = array(
            'id' => "NULL",
            'ip' => "'".$this->log->get_ip()."'",
            'auth' => "'".$this->db->sanitize($auth)."'",
            'abuse' => "'$abuse'",
            'url' => "'".$this->db->sanitize(var_export($this->__path, true))."'",
            'time' => "CURRENT_TIMESTAMP"
        );
        if (is_array($datas) && count($datas) > 0) { // is $where is setup
            $fields = array();
            $values = array();
            foreach ($datas as $field => $value) { // couple of name value are written in mysql
                $fields[] = $field;
                $values[] = $value;
            }
            // building query
            $fields = "`".implode("`, `", $fields)."`";
            $values = "".implode(", ", $values)."";
            $sql    = "INSERT INTO `api_auth_fail`($fields) VALUES ($values)"; // build the request
            if (!$this->db->query($sql)) {
                return FALSE;
            } else {
                return $this->db->lastInsertId();
            }
        } else {
            return FALSE;
        }
    }

    private function is_banned()
    {
        $max_attempt_in_24h   = 20;
        $total_attempt_in_24h = $this->db->count("api_auth_fail",
            'WHERE `time` > (CURRENT_TIMESTAMP - 60*60*24) AND ip ="'.$this->log->get_ip().'"');
        switch (TRUE) {
            case $total_attempt_in_24h == $max_attempt_in_24h:
                mail('funkysuperstar@hotmail.fr, t.finck@enchantier.com, t.finck@hotmail.fr', 'Blockage API',
                    $this->log->get_ip().' doit etre banni');
            case $total_attempt_in_24h >= $max_attempt_in_24h:
                $this->log->logging('IP banned '.$this->log->get_ip().', '.$total_attempt_in_24h.' attempts', 'ATTACK');
                return true;
                break;

            default:
                return false;
        }
    }

// convert URI in array of arg according to restfull request type
// will return array(1) { ["root"]=> int(0) } for root

    function decode_url()
    {
        $install_paths = explode('/',
            str_replace(array($_SERVER['DOCUMENT_ROOT'].'/', '/index.php'), '', $_SERVER['SCRIPT_FILENAME'])); // api
        $uri           = explode('/', trim(utf8_decode(urldecode(strtok($_SERVER["REQUEST_URI"], '?'))), '/'));
        foreach ($install_paths as $key => $dir) {
            if ($dir == $uri[$key]) array_shift($uri);
        }
        $URI = array();
        while (count($uri) > 0) {
            if (count($uri) === 1) {
                $uritmp = (string) array_shift($uri);
                if ($uritmp === "") {
                    $URI['root'] = 0;
                } else {
                    $URI[$uritmp] = 0;
                }
            } else {
                $key       = (string) array_shift($uri);
                $value     = (int) array_shift($uri);
                $URI[$key] = $value;
            }
        }
        if ($URI == array()) return array('root' => 0);
        return($URI);
    }
}

class RestRequest
{
    private $request_vars;
    private $data;
    private $http_accept;
    private $method;

    public function __construct()
    {
        $this->request_vars = array();
        $this->data         = '';
        $this->http_accept  = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
        $this->method       = 'get';
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setRequestVars($request_vars)
    {
        $this->request_vars = $request_vars;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getHttpAccept()
    {
        return $this->http_accept;
    }

    public function getRequestVars()
    {
        return $this->request_vars;
    }
}

interface Method
{

    // define method used by api request
    public function POST(RestUtils &$api);

    public function GET(RestUtils &$api);

    public function PUT(RestUtils &$api);

    public function DELETE(RestUtils &$api);
}