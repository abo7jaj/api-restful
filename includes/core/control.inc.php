<?php

class Control {

    public $error;

    public function __construct() {
        
    }

    // !!! return false if everything ok 
    public function required(&$param, $min_length = 0) {
        if ($min_length != 0) { // controle longueur
            return !(isset($param) && $param !== '' && strlen($param) >= $min_length );
        } else {
            return !(isset($param) && $param !== '' );
        }
    }

    // !!! return false if everything ok 
    public function email_required(&$email) {
        return !( $this->check_email_address($email));
    }

    // !!! return false if everything ok
    public function url_required($url) {
        return filter_var($url, FILTER_VALIDATE_URL) === false;
    }

    private function check_email_address($email) {
        $email = trim($email);
        // First, we check that there's one @ symbol, and that the lengths are right
        if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
            // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
            return false;
        }
        // Split it into sections to make life easier
        $email_array = explode("@", $email);
        $local_array = explode(".", $email_array[0]);
        for ($i = 0; $i < sizeof($local_array); $i++) {
            if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
                return false;
            }
        }
        if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
            $domain_array = explode(".", $email_array[1]);
            if (sizeof($domain_array) < 2) {
                return false; // Not enough parts to domain
            }
            for ($i = 0; $i < sizeof($domain_array); $i++) {
                if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function get_departement_from_cp($cp) {
        if (is_int($cp)) // int received instead of str
            $cp = number_format($cp, 0, '.', '');
        if (strlen($cp) == 4) // ex 1790 => 01790
            $cp = "0" . $cp;
        $matches = null;
        if (preg_match('/^9[78]/i', $cp, $matches) == 1)
            return substr($cp, 0, 3);
        else
            return substr($cp, 0, 2);
    }

    public function my_personnal_key($length = 32) {
        $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

    // dot and slash are forbiden in dir_name
    public function sanitize_dir_name(&$dir_name) {
        $dir_name = str_replace("./\\", "", $dir_name);
        return $dir_name;
    }

}
