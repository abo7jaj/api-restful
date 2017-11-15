<?php

class Log {

    private $uid;

    public function __construct($TRACE = 1, $DISPLAY = 0, $log_file = "api.log") {
        $this->uid = strrev(uniqid());
//        $this->uid = $_SESSION["ladmin"] . "^" . strrev(uniqid());
        $this->TRACE = $TRACE;
        $this->DISPLAY = $DISPLAY;
        $this->log_file = $log_file;
        // open file
        $this->fd = fopen(LOG_PATH . $this->log_file, "a+");
    }

    function get_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    // argument : Message , [Group]
    // write string 18/01/2011 11:45:16 -> MYSQL: SELECT * FROM table
    function logging($msg, $group = "DEFAULT") {
//        ob_start();
//        //debug_print_backtrace();
//        $trace = ob_get_contents();
//        ob_end_clean();
        // prepare message 
        if (is_array($msg))
            $msg = serialize($msg);
        $msg = str_replace(array("\r", "\n"), ' ', $msg);

        //  $msg .= " TESTRS:".$this->get_ip();
        //$trace = str_replace(array("\r", "\n"), ' ', $trace);
        // write string 18/01/2011 11:45:16 -> MYSQL SELECT * FROM table
        if ($this->DISPLAY == true)
            echo "<hr />" . date("d/m/Y H:i:s") . " " . $this->uid . " -> " . $group . " -> " . $trace . ": " . $msg . "<br /><hr />";
        if ($this->TRACE == true)
//            fwrite($this->fd, "\n" . date("d/m/Y H:i:s") . " " . $this->uid . " -> " . $group . ": " . $msg." => ". $trace);
            fwrite($this->fd, "\n" . date("d/m/Y H:i:s") . " " . $this->get_ip() . " " . $this->uid . " -> " . $group . ": " . $msg);
    }

    public function __destruct() {
        // close file
        fclose($this->fd);
    }

    function l($msg, $group = "DEFAULT") {
        $this->logging($msg, $group);
    }

}
