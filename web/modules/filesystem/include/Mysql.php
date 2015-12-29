<?php
/*
 * 数据库操作的类
 */

class Mysql {

    private $conn_str = array();

    private $version = '';

    private $querynum = 0;

    private $link = null;

    private $in_transaction = FALSE;
    //连接数据库 pconnect持久连接 halt 报告连接错误 dbcharset2手动设置mysql编码
    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE, $dbcharset2 = '') {
        $this->conn_str = array($dbhost, $dbuser, $dbpw, $dbname, $pconnect, $halt, $dbcharset2);
        $func = empty($pconnect) ? 'mysql_connect' : 'mysql_pconnect';
        if(!$this->link = @$func($dbhost, $dbuser, $dbpw, 1)) {
            $halt && $this->halt('Can Not Connect to MySQL');
        } else {
            $idbcharset = $dbcharset2 ? $dbcharset2 : 'utf8';
            $serverset = $idbcharset ? 'character_set_connection=' . $idbcharset . ', character_set_results=' . $idbcharset . ', character_set_client=binary' : '';
            $serverset .= $this->version() > '5.0.1' ? ((empty($serverset) ? '' : ',') . 'sql_mode=\'\'') : '';
            $serverset && mysql_query("SET $serverset", $this->link);
            $dbname && @mysql_select_db($dbname, $this->link);
        }
    }

    function selectDb($dbname) {
        return mysql_select_db($dbname, $this->link);
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
        return mysql_fetch_array($query, $result_type);
    }

    function fetchFirst($sql) {
        return $this->fetchArray($this->query($sql));
    }

    function resultFirst($sql) {
        return $this->result($this->query($sql), 0);
    }

    function query($sql, $type = '') {
        $func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ? 'mysql_unbuffered_query' : 'mysql_query';
        if(! is_resource($this->link)) {
            $this->halt('MySQL Connection Lost', $sql);
            return FALSE;
        }
        if(!($query = $func($sql, $this->link))) {
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
        return mysql_affected_rows($this->link);
    }

    function error() {
        return(($this->link) ? mysql_error($this->link) : mysql_error());
    }

    function errno() {
        return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
    }

    function result($query, $row = 0) {
        $query = @mysql_result($query, $row);
        return $query;
    }

    function numRows($query) {
        $query = mysql_num_rows($query);
        return $query;
    }

    function numFields($query) {
        return mysql_num_fields($query);
    }

    function freeResult($query) {
        return mysql_free_result($query);
    }

    function insertId() {
        return($id = mysql_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
    }

    function fetchRow($query) {
        $query = mysql_fetch_row($query);
        return $query;
    }

    function fetchFields($query) {
        return mysql_fetch_field($query);
    }

    static function quote($str) {
        $result = mysql_escape_string($str);
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
            $this->version = mysql_get_server_info($this->link);
        }
        return $this->version;
    }

    function close() {
        return mysql_close($this->link);
    }

    function halt($message = '', $sql = '') {
        $dberrno = $this->errno();
        if($dberrno == 1114) {
            $message = 'Max Onlines Reached';
        }

        if(Core::$start_time) Core::debug('trace', Core::client_ip()."\t".Core::$start_time."\tmysql_halt\t" . $message . "\t" . $sql . "\t" . $this->errno() . "\t" . $this->error());
        //MongoLogger::instance()->log('mysqlerror',$message . "\t" . $sql . "\t" . $this->errno() . "\t" . $this->error() ."\t" . Core::server_addr());
    }
}
?>
