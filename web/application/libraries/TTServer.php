<?php
//TTServer 操作类 by avenger
//TTServer 
define('CONNECTED'    , 0xF0);
define('DISCONNECTED' , 0x0000);
define('IS_STRING'    , 0x02);
define('IS_ARRAY'     , 0x04);
define('IS_COMPRESSED', 0x08);
class TTServer_Core {
    var $host;
    var $port;
    var $status;
    var $socket;

    public function __construct($host = array(), $port=11222) { 
        
        if(empty($host)) {
            $host = Kohana::config('core.ttserver');
	}
	
        $key = array_rand($host,1);
        $this->host = $host[$key];
        $this->port = $port;
        $this->connect();
    } 

    public function connect() {
        if ( $this->isConnected() ) return false;
        $this->status = DISCONNECTED;
        if ( $this->host == "" || $this->port == 0) return false;
        $this->socket = fsockopen($this->host,$this->port);
        if ($this->socket !== false)
            $this->status = CONNECTED;
        return ($this->socket !== false);
    }

    public function isConnected() {
        if($this->status == CONNECTED)  return true;
        return false;
    }

    public function get($key) {

        if (! $this->isConnected() ) return false;
        $buf = "";

        fwrite($this->socket, "GET -fc $key\r\n");

        $buf = fgets($this->socket,8192);


        if ($buf == 'notexists') return false;

        $parts = explode(",",$buf);
        $result = explode("___",$parts[0]);

        $value = base64_decode($result[1]); 
        if (trim($result[0]) == IS_COMPRESSED)
           $value = gzuncompress($value);

        if (trim($result[0]) == IS_ARRAY)
            $value = unserialize($value);

        return $value;
    }

    public function set($key, $value, $compress = false) {

        if (! $this->isConnected() ) return false;

        $magic = $this->getVarType($value);

        if ( $magic == IS_ARRAY) {
            $value = serialize($value);
        }

        if ($compress) {
            $magic |= IS_COMPRESSED;
            $value = gzcompress($value);
        }

        $value = $magic.'___'.base64_encode($value);

        $len = strlen($value);
        $buf = '';

        fwrite($this->socket, "SET -fc ${key} $len $value\r\n");

        $buf = fgets($this->socket,8192);

        if ( $buf == "error") return false;
    }

    public function del($key) {

        if (! $this->isConnected() ) return false;

        $buf = "";

        fwrite($this->socket, "DEL -c $key\r\n");

        $buf = fgets($this->socket,8192);

        if ($buf == 'ok') {
            return true;
        } else {
            return false;
        }

    }

    public function del_force($key) {

        if (! $this->isConnected() ) return false;

        $buf = "";

        fwrite($this->socket, "DEL -f $key\r\n");

        $buf = fgets($this->socket,8192);

        if ($buf == 'ok') {
            return true;
        } else {
            return false;
        }

    }

    public function set_count($key) {

        if (! $this->isConnected() ) return false;

        $buf = "";

        fwrite($this->socket, "SET -c $key\r\n");

        $buf = fgets($this->socket,8192);

        if ($buf == 'error') return false;

        return $buf;

    }

    public function get_count($key) {

        if (! $this->isConnected() ) return false;

        $buf = "";

        fwrite($this->socket, "GET -c $key\r\n");

        $buf = fgets($this->socket,8192);

        if ($buf == 0) return false;

        return true;

    }

    public function disconnect() {

        if (! $this->isConnected() ) return false;

        fclose($this->socket);
    }

    //判断要存取的类型
    public function getVarType(&$var) {

        $r = IS_STRING;

        switch ( gettype($var) ) {
            case "array":
            case "object":
                $r = IS_ARRAY;
                break;
            case "default":
                $r = IS_STRING;
        }
        return $r;
    }
}

