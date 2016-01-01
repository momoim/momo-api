<?php
/*
 * 数据库操作的类
 */

class Mysql {

    private $conn_str = array();

    private $version = '';

    private $querynum = 0;

    private $db = null;

    //private $link = null;

    private $in_transaction = FALSE;
    //连接数据库 pconnect持久连接 halt 报告连接错误 dbcharset2手动设置mysql编码
    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE, $dbcharset2 = '') {
        $t = new mysqli($dbhost, $dbuser, $dbpw);
        $this->db = $t;

        if($this->db->connect_errno > 0){
            $halt && $this->halt('Can Not Connect to MySQL');
        } else {
            $idbcharset = $dbcharset2 ? $dbcharset2 : 'utf8';
            $serverset = $idbcharset ? 'character_set_connection=' . $idbcharset . ', character_set_results=' . $idbcharset . ', character_set_client=binary' : '';
            $serverset .= $this->version() > '5.0.1' ? ((empty($serverset) ? '' : ',') . 'sql_mode=\'\'') : '';
            $serverset && $this->db->query("SET $serverset");
            $dbname && $this->db->select_db($dbname);
        }
        $this->conn_str = array($dbhost, $dbuser, $dbpw, $dbname, $pconnect, $halt, $dbcharset2);
    }

    function selectDb($dbname) {
        return $this->db->select_db($dbname);
    }
    /**
     * 事务开始
     */
    function begin() {
        if(!$this->in_transaction) {
            $this->in_transaction = TRUE;
            return $this->query('START TRANSACTION');
        }
        return FALSE;
    }
    /**
     * 提交事务
     */
    function commit() {
        if($this->in_transaction) {
            $this->in_transaction = FALSE;
            return $this->query('COMMIT');
        }
        return FALSE;
    }
    /**
     * 事务回滚
     */
    function rollback() {
        if($this->in_transaction) {
            $this->in_transaction = FALSE;
            return $this->query('ROLLBACK');
        }
        return FALSE;
    }
    /**
     * 获取数据使用MYSQL_ASSOC方式
     */
    function fetchArray($query, $result_type = MYSQL_ASSOC) {
        return $query->fetch_array($result_type);
    }

    function fetchFirst($sql) {
        return $this->fetchArray($this->query($sql));
    }

    function resultFirst($sql) {
        return $this->result($this->query($sql), 0);
    }

    function query($sql, $type = '') {
        if (!($query = $this->db->query($sql))) {
            if(in_array($this->errno(), array(2006, 2013)) && substr($type, 0, 5) != 'RETRY') {
                $this->close();
                list($dbhost, $dbuser, $dbpw, $dbname, $pconnect, $halt, $dbcharset) = $this->conn_str;
                $this->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
                return $this->query($sql, 'RETRY' . $type);
            } elseif($type != 'SILENT' && substr($type, 5) != 'SILENT') {
                $this->halt('MySQL Query Error', $sql);
            }
        }

        $this->querynum++;
        return $query;
    }

    function affectedRows() {
        return $this->db->affected_rows();
    }

    function error() {
        return $this->db->error;
    }

    function errno() {
        return intval($this->db->errno);
    }

    function result($query, $row = 0) {
        $r = null;
        $i = 0;
        while($i <= $row) {
            $r = $query->fetch_assoc();
            $i++;
            if (!$r) {
                return false;
            }
        }
        return $r;
    }

    function numRows($query) {
        $query = $query->num_rows();
        return $query;
    }

    function numFields($query) {
        return $query->num_fields();
    }

    function freeResult($query) {
        return $query->free();
    }

    function insertId() {
        $id = $this->db->insert_id;
        if ($id >= 0) {
            return $id;
        } else {
            return $this->result($this->query("SELECT last_insert_id()"), 0);
        }
    }

    function fetchRow($query) {
        return $query->fetch_row();
    }

    function fetchFields($query) {
        return $query->fetch_fields();
    }

    function quote2($str) {
        $result = $this->db->real_escape_string($str);
        if(is_string($result)) {
            return "'$result'";
        } elseif($result == '') {
            return "NULL";
        } else {
            return $result;
        }
    }

    static function quote($str) {
        $search = [ "\\", "\r", "\n", "\x1a", "\x00", "'", "\""];
        $replace = ["\\\\",  "\\r", "\\n", "\\Z", "\\0", "\\'", "\\\""];

        $result = str_replace($search, $replace, $str);

        if(is_string($result)) {
            return "'$result'";
        } elseif($result == '') {
            return "NULL";
        } else {
            return $result;
        }
    }

    function version() {
        if(empty($this->version)) {
            $this->version = $this->db->server_info;
        }
        return $this->version;
    }

    function close() {
        $this->db->close();
    }

    function halt($message = '', $sql = '') {
        $dberrno = $this->errno();
        if($dberrno == 1114) {
            $message = 'Max Onlines Reached';
        }
    }
}
?>