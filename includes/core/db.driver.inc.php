<?php

class Database extends mysqli
{
    public $_connection;
    private static $_instance;      // The single instance
    public $fetch_mode = "assoc";   // if 'object' mysql_fetch_object else mysql_fetch_assoc
    public $debug;                  // debug mode
    private $last_sql;              // the last sql request used
    public $log;                    // the log system

    /*
      Get an instance of the Database
      @return Instance
     */

    public static function getInstance()
    {
        if (!self::$_instance) { // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Constructor
    public function __construct()
    {
        // debug trace 
        if (!isset($this->debug)) $this->debug = TRUE; // not always set, depend wher call is done
        if ($this->debug) $this->log   = new Log(1, 0, LOG_FILE_SQL);

        // open MySQLi connection
        $this->_connection = parent::__construct(DB_SERVER, DB_LOGIN, DB_PASSWORD, DB_DATABASE);

        // Error handling
        if (mysqli_connect_error()) {
            trigger_error("Failed to connect to MySQL: error(".mysqli_connect_errno().') '.mysqli_connect_error(),
                E_USER_ERROR);
        }
        parent::set_charset("UTF8");
    }

    // perform a select query 
    // param $table, $where (optional) as array and fields (optional)
    // return array or object according to $fetch_mode
    // ex : $values=$db->select('lead_provider', array('login'=>$user,'password'=>$password));
    public function select($table, $where = array(), $fields = "*", $unique = false)
    {
        $values = array(); // object to return
        if (is_array($where) && count($where) > 0) { // is $where is setup
            $wherelist = array();
            foreach ($where as $key => $value) { // couple of name value are written in mysql
                $wherelist[] = "`$key`='".$this->sanitize($value)."'";
            }
            $wherereq = "WHERE ".implode(" AND ", $wherelist);
        } elseif (is_string($where)) {
            $wherereq = "WHERE $where";
        } else {
            $wherereq = "";
        }
        $this->last_sql = "SELECT $fields FROM $table $wherereq"; // build the request
        $result         = $this->query($this->last_sql);
        if ($result !== false)
                while ($row            = $this->fetch($result)) { // read values according to $fetch_mode
                $values[] = $row;
            }
        if ($unique && count($values) > 0) return $values[0];
        else return $values;
    }

    // perform a count query 
    // param $table, $where (optional) as array and fields (optional)
    // return int
    // ex : $values=$db->count('lead_provider', array('login'=>$user,'password'=>$password));
    public function count($table, $where = array(), $fields = "count(*)")
    {
        $value = 0; // object to return
        if (is_array($where) && count($where) > 0) { // is $where is setup
            $wherelist = array();
            foreach ($where as $key => $value) { // couple of name value are written in mysql
                $wherelist[] = "`$key`='".$this->sanitize($value)."'";
            }
            $wherereq = "WHERE ".implode(" AND ", $wherelist);
        } elseif (is_string($where)) {
            $wherereq = " $where ";
        } else {
            $wherereq = "";
        }
        $this->last_sql = "SELECT $fields FROM $table $wherereq"; // build the request
        try {
            $result = $this->query($this->last_sql);
            $value  = $this->fetch($result);
            return array_shift($value);
        } catch (Exception $ex) {
            error_log($this->last_sql);
        }
    }

    // perform an insert 
    // param $table, $data as assoc array (probably given by json)
    // return false or last insert id
    // ex : $values=$db->insert('lead_provider', array('login'=>$user,'password'=>$password));
    public function insert($table, $datas)
    {
        if (is_array($datas) && count($datas) > 0) { // is $where is setup
            $fields = array();
            $values = array();
            foreach ($datas as $field => $value) { // couple of name value are written in mysql
                $fields[] = $this->sanitize($field);
                $values[] = $this->sanitize($value);
            }
            // building query
            $fields         = "`".implode("`, `", $fields)."`";
            $values         = "'".implode("', '", $values)."'";
            $this->last_sql = "INSERT INTO $table($fields) VALUES ($values)"; // build the request
            if (!$this->query($this->last_sql)) {
                return FALSE;
            } else {
                return $this->lastInsertId();
            }
        } else {
            return FALSE;
        }
    }

    // delete
    public function delete($table, $where = FALSE)
    {
        if ($where === false) { // delete all
            $wherereq = "WHERE 1";
        } elseif (is_int($where) || is_string($where)) { // delete id =
            $wherereq = "WHERE ".DEFAULT_ID_FIELD."='$where'";
        } elseif (is_array($where) && count($where) > 0) { // delete where array
            $wherelist = array();
            foreach ($where as $key => $value) { // couple of name value are written in mysql
                $wherelist[] = "`$key`='".$this->sanitize($value)."'";
            }
            $wherereq = "WHERE ".implode(" AND ", $wherelist);
        } else {
            $wherereq = "";
        }

        $this->last_sql = "DELETE FROM `$table` $wherereq";
        return $this->query($this->last_sql);
    }

    // we will take a full query here, so many strange case 
    public function update($query)
    {
        $this->query($query);
        return $this->affected_rows;
    }

    // update with datas including an id or token entry for the where id= request
    public function update_by_id($table, $datas, $id_name = 'id')
    {
        if (!is_array($datas)) return FALSE;
        $id    = $datas[$id_name]; // id to identify an entry
        unset($datas[$id_name]); // no need to update the id
        $query = "UPDATE `$table` SET ";
        $set   = array();
        foreach ($datas as $key => $value) {
            $value = $this->sanitize($value);
            $set[] = " `$key` = '$value'";
        }
        $query .= implode(",", $set);
        $query .= " WHERE `$id_name`=$id";
        $this->query($query);
        return $this->affected_rows;
    }

    // fetch according to method selected
    public function fetch($result, $mode = false)
    {
        if ($mode === false) {
            $mode = $this->fetch_mode;
        }
        if (is_object($result)) {
            if ($mode === "object") {
                return $result->fetch_object();
            } else {
                return $result->fetch_assoc();
            }
        } else {
            $this->trigger_error();
        }
    }

    public function affectedRows()
    {
        return $this->affected_rows;
    }

    public function lastInsertId()
    {
        $lastid = $this->insert_id;
        if ($this->debug) $this->log->logging($lastid, 'ID');
        return $lastid;
    }

    public function query($sql)
    {
        $start  = microtime(true);
        $result = parent::query($sql);
        $end    = microtime(true);
        if ($this->debug) $this->log->logging($sql, 'SQL '.(number_format($end - $start, 6)).'s');
        if ($this->errno != 0) $this->log->logging($this->error.' ('.$this->errno.') '.$sql, 'ERROR');
        return $result;
    }

    public function sanitize($data)
    {
        if (is_array($data)) {
            array_walk($data, array($this, 'sanitize'));
            return $data;
        } else return parent::real_escape_string(trim($data));
    }

    // Magic method clone is empty to prevent duplication of connection
    public function __clone()
    {
        
    }

    // Get mysqli connection
    public function getConnection()
    {
        return $this->_connection;
    }

    public function table_exists($table)
    {
        $res = $this->Query("SHOW TABLES LIKE '$table'");

        if (isset($res->num_rows)) {
            return $res->num_rows > 0 ? true : false;
        } else {
            return false;
        }
    }

    /**
     * \brief       brief
     * \details     details
     * \param       string  param         desc
     * \return      bool  true si vrai
     */
    public function trigger_error($dst = 'all')
    {
        $dbgt    = debug_backtrace();
        $rtn_msg = "FETCH FAIL called";
        foreach ($dbgt as $caller) {
            $file    = str_replace(HOME_PATH, '', $caller['file']);
            $rtn_msg .= " from {$file} on line {$caller['line']},";
        }
        if ($this->debug) {
            $rtn_msg .= ' Error '.$this->errno.',';
            $rtn_msg .= ' '.$this->error.',';
            $rtn_msg .= ' SQL: '.$this->last_sql;
        }
        switch ($dst) {
            default:
            case 'all':
                error_log($rtn_msg, E_USER_ERROR);
            case 'internal':
                $this->log->logging($rtn_msg, 'TRIGGER_ERROR');
                break;
        }
        die();
    }
}