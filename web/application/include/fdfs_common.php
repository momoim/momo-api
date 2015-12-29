<?php
/**
* Copyright (C) 2008 Happy Fish / YuQing
*
* This FastDFS php client may be copied only under the terms of the BSD License.
* Please visit the FastDFS Home Page http://www.csource.org/ for more detail.
**/
class Common {


    public static $fdfs_network_timeout = 30;  //seconds
    public static $fdfs_tracker_servers =  array('ip_addr' => TRACKER_SERVER,'port' => 22122,'sock' => -1);
    public static $fdfs_tracker_server_index = 0;


/**
* change me to correct tracker server list, assoc array element:
*    ip_addr: the ip address or hostname of the tracker server
*    port:    the port of the tracker server
*    sock:    the socket handle to the tracker server, should init to -1 or null
*/

    public static $fdfs_tracker_server_count = 1;

        public function  __construct()
        {

          //  self::fdfs_tracker_servers = array('ip_addr' => '192.168.9.128','port' => 22122,'sock' => -1); 
        }

/**
* recv package header
* @param $server connected tracker or storage server (assoc array)
* @param $in_bytes return the package length
* @return 0 for success, none zero (errno) for fail
*/

public static function  fdfs_recv_header($server, &$in_bytes)
{
	$pkg_len = fread($server['sock'], FDFS_PROTO_PKG_LEN_SIZE);
	$cmd = fread($server['sock'], 1);
	$status = fread($server['sock'], 1);
	if (ord($status) != 0)
	{
		$in_bytes = 0;
		return ord($status);
	}

	$in_bytes = self::fdfs_buff2long($pkg_len);
	if ($in_bytes < 0)
	{
		error_log("server: ${server['ip_addr']}:${server['port']}, "
			. "recv package size $in_bytes is not correct");
		$in_bytes = 0;
		return FDFS_EINVAL;
	}

	return 0;
}

/**
* recv response package from server
* @param $server connected tracker or storage server (assoc array)
* @param $expect_pkg_len expect body length, < 0 for uncertain
* @param $buff return the package buff
* @param $in_bytes return the package length
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_recv_response($server, $expect_pkg_len, &$buff, &$in_bytes)
{
	$result = self::fdfs_recv_header($server, $in_bytes);
	if ($result != 0)
	{
		return $result;
	}

	if ($expect_pkg_len >= 0 && $expect_pkg_len != $in_bytes)
	{
		error_log("server: ${server['ip_addr']}:${server['port']}, "
			. "pkg length: $in_bytes is not correct, " 
			. "expect pkg length: $expect_pkg_len");
		$in_bytes = 0;
		return FDFS_EINVAL;
	}

	if ($in_bytes == 0)
	{
		return 0;
	}

	$buff = '';
	$remain_bytes = $in_bytes;
	while ($remain_bytes > 0)
	{
		$s = fread($server['sock'], $remain_bytes);
		if (!$s)
		{
			error_log("server: ${server['ip_addr']}:${server['port']}, "
				. "fread fail");
			return FDFS_EIO;
		}

		$buff .= $s;
		$remain_bytes -= strlen($s);
	}

	return 0;
}

/**
* recv response package from server
* @param $server connected storage server (assoc array)
* @param $file_size  file size (bytes)
* @param $local_filename  local filename to write
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_recv_file($server, $file_size, $local_filename)
{
	$fp = fopen($local_filename, 'wb');
	if ($fp === false)
	{
		error_log("open file \"$local_filename\" to write fail");
		return FDFS_EIO;
	}

	$sock = $server['sock'];
	$result = 0;
	$remain_bytes = $file_size;
	while ($remain_bytes > 0)
	{
		if ($remain_bytes > 16 * 1024)
		{
			$read_bytes = 16 * 1024;
		}
		else
		{
			$read_bytes = $remain_bytes;
		}

		$buff = fread($sock, $read_bytes);
		if (!$buff)
		{
			error_log("server: ${server['ip_addr']}:${server['port']}, "
				. "fread fail");
			$result = FDFS_EIO;
			break;
		}

		$bytes = strlen($buff);
		if (fwrite($fp, $buff, $bytes) != $bytes)
		{
			error_log("fwrite to \"$local_filename\" fail");
			$result = FDFS_EIO;
			break;
		}

		$remain_bytes -= $bytes;
	}

	fclose($fp);
	return $result;
}

/**
* send file to server
* @param $server connected storage server (assoc array)
* @param $local_filename  local filename to write
* @param $file_size  file size (bytes)
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_send_file($server, $local_filename, $file_size)
{
	$fp = fopen($local_filename, 'rb');
	if ($fp === false)
	{
		error_log("open $local_filename to read fail");
		return FDFS_EIO;
	}

	$sock = $server['sock'];
	$result = 0;
	$remain_bytes = $file_size;
	while ($remain_bytes > 0)
	{
		if ($remain_bytes > 64 * 1024)
		{
			$read_bytes = 64 * 1024;
		}
		else
		{
			$read_bytes = $remain_bytes;
		}

		$buff = fread($fp, $read_bytes);
		if ($buff === false)
		{
			error_log("fread fail, file: $local_filename");
			$result = FDFS_EIO;
			break;
		}

		if (fwrite($sock, $buff, $read_bytes) != $read_bytes)
		{
			error_log("server: ${server['ip_addr']}:${server['port']}, "
				. "fwrite fail");
			$result = FDFS_EIO;
			break;
		}

		$remain_bytes -= $read_bytes;
	}

	fclose($fp);
	return $result;
}

/**
* long to big-endian buff, because PHP does not support 64 bits integer, we use 32 bits
* @param $n the integer number
* @return 8 bytes big-endian buff (string)
*/
public static function  fdfs_long2buff($n)
{
	/*
	return sprintf('%c%c%c%c%c%c%c%c'
			, ($n >> 56) & 0xFF
			, ($n >> 48) & 0xFF
			, ($n >> 40) & 0xFF
			, ($n >> 32) & 0xFF
			, ($n >> 24) & 0xFF
			, ($n >> 16) & 0xFF
			, ($n >> 8) & 0xFF
			, $n & 0xFF);
	*/

	return "\000\000\000\000" . pack('N', $n);
}

/**
* big-endian buff to long, because PHP does not support 64 bits integer, we use 32 bits
* @param $buff 8 bytes big-endian buff
* @return the 32 bits integer number
*/
public static function  fdfs_buff2long($buff)
{
	$arr = unpack('N', substr($buff, 4, 4));
	return $arr['1'];
}

/**
* pack package header
* @param $pkg_len the package length
* @param $cmd the command
* @param $status the status
* @return package header string
*/
public static function  fdfs_pack_header($pkg_len, $cmd, $status)
{
	return self::fdfs_long2buff($pkg_len) . sprintf('%c%c', $cmd, $status);
}

/**
* send package header
* @param $server the connected server (assoc array)
* @param $pkg_len the package length
* @param $cmd the command
* @param $status the status
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_send_header($server, $pkg_len, $cmd, $status)
{
    $header = self::fdfs_pack_header($pkg_len, $cmd, $status);
	if (fwrite($server['sock'], $header, FDFS_PROTO_HEADER_LENGTH) != FDFS_PROTO_HEADER_LENGTH)
	{
		error_log("server: ${server['ip_addr']}:${server['port']}, "
			. "send data fail");
		return FDFS_EIO;
	}

	return 0;
}

/**
* send QUIT command to server
* @param $server the connected server (assoc array)
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_quit($server)
{
	return self::fdfs_send_header($server, 0, FDFS_PROTO_CMD_QUIT, 0);
}

/**
* pack group_name and filename
* @param $group_name the group name of the storage server
* @param $filename the filename on the storage server
* @param $len return the packed length
* @return packed string
*/
public static function  fdsf_pack_group_and_filename($group_name, $filename, &$len)
{
	$filename_len = strlen($filename);
	$groupname_len = strlen($group_name);
	$len = FDFS_GROUP_NAME_MAX_LEN + $filename_len;

	if ($groupname_len > FDFS_GROUP_NAME_MAX_LEN)
	{
		$body = substr($group_name, 0, FDFS_GROUP_NAME_MAX_LEN);
	}
	else
	{
		$body = $group_name;
		$body .= str_repeat("\000", FDFS_GROUP_NAME_MAX_LEN - $groupname_len);
	}

	return $body . $filename;
}

/**
* pack metadata array to string
* @param $meta_list metadata assoc array
* @return packed metadata string
*/
public static function  fdfs_pack_metadata($meta_list)
{
	if (!is_array($meta_list) || count($meta_list) == 0)
	{
		return '';
	}

	$s = '';
	$i = 0;
	foreach($meta_list as $key => $value)
	{
		if ($i > 0)
		{
			$s .= FDFS_RECORD_SEPERATOR;
		}
		$s .= $key . FDFS_FIELD_SEPERATOR . $value;
		$i++;
	}

	return $s;
}

/**
* pack split metadata string to assoc array
* @param $metadata metadata string
* @return metadata array
*/
public static function  fdfs_split_metadata($metadata)
{
	$meta_list = array();

	$rows = explode(FDFS_RECORD_SEPERATOR, $metadata);
	foreach ($rows as $r)
	{
		$cols = explode(FDFS_FIELD_SEPERATOR, $r, 2);
		if (count($cols) == 2)
		{
			$meta_list[$cols[0]] = $cols[1];
		}
	}

	return $meta_list;
}

/**
* get storage readable connection, if the $storage_server is connected, 
* do not connect again
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $filename the filename on the storage server
* @param $new_connection make a new connection flag, 
         true means create a new connection
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_get_read_connection($tracker_server, &$storage_server, 
		$group_name, $filename, &$new_connection)
{
	return self::storage_get_connection($tracker_server, $storage_server, 
		TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ONE, $group_name, $filename, 
		$new_connection);
}

/**
* get storage updatable connection, if the $storage_server is connected, 
* do not connect again
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $filename the filename on the storage server
* @param $new_connection make a new connection flag, 
         true means create a new connection
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_get_update_connection($tracker_server, &$storage_server, 
		$group_name, $filename, &$new_connection)
{
	return self::storage_get_connection($tracker_server, $storage_server, 
		TRACKER_PROTO_CMD_SERVICE_QUERY_UPDATE, $group_name, $filename, 
		$new_connection);
}

/**
* get storage readable connection, if the $storage_server is connected, 
* do not connect again
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the storage server (assoc array), can be null
* @param $cmd the query command
* @param $group_name the group name of storage server
* @param $filename the filename on the storage server
* @param $new_connection make a new connection flag, 
         true means create a new connection
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_get_connection($tracker_server, &$storage_server, 
		$cmd, $group_name, $filename, &$new_connection)
{
	if (!$storage_server)
	{
		if ($cmd == TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ONE)
		{
			if (($result=self::tracker_query_storage_fetch($tracker_server, 
		       	         $storage_server, $group_name, $filename)) != 0)
			{
				return $result;
			}
		}
		else
		{
			if (($result=self::tracker_query_storage_update($tracker_server, 
		       	         $storage_server, $group_name, $filename)) != 0)
			{
				return $result;
			}
		}

		if (($result=self::fdfs_connect_server($storage_server)) != 0)
		{
			return $result;
		}

		$new_connection = true;
	}
	else
	{
		if (isset($storage_server['sock']) && $storage_server['sock'] >= 0)
		{
			$new_connection = false;
		}
		else
		{
			if (($result=self::fdfs_connect_server($storage_server)) != 0)
			{
				return $result;
			}

			$new_connection = true;
		}
	}

	return 0;
}

/**
* get storage writable connection, if the $storage_server is connected, 
* do not connect again
* @param $tracker_server the connected tracker server (assoc array)
* @param $group_name the group to upload file to, can be empty
* @param $storage_server the storage server (assoc array), can be null
* @param $new_connection make a new connection flag, 
         true means create a new connection
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_get_write_connection($tracker_server, $group_name, &$storage_server, 
		&$new_connection, $custom_param)
{
	if (!$storage_server)
	{
		if (($result=self::tracker_query_storage_store($tracker_server, 
		                $storage_server, $group_name, $custom_param)) != 0)
		{
			return $result;
		}

		if (($result=self::fdfs_connect_server($storage_server)) != 0)
		{
			return $result;
		}

		$new_connection = true;
	}
	else
	{
		if (isset($storage_server['sock']) && $storage_server['sock'] >= 0)
		{
			$new_connection = false;
		}
		else
		{
			if (($result=self::fdfs_connect_server($storage_server)) != 0)
			{
				return $result;
			}

			$new_connection = true;
		}
	}

	return 0;
}

/**
* get metadata from the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name: the group name of storage server
* @param $filename: filename on storage server
* @param $meta_list return metadata assoc array
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_get_metadata($tracker_server, $storage_server, 
			$group_name, $filename, &$meta_list)
{
	if (($result=self::storage_get_update_connection($tracker_server, 
		$storage_server, $group_name, $filename, $new_connection)) != 0)
	{
		return $result;
	}

	while (1)
	{
	/**
	send pkg format:
	FDFS_GROUP_NAME_MAX_LEN bytes: group_name
	remain bytes: filename
	**/

	$body = self::fdsf_pack_group_and_filename($group_name, $filename, $pkg_len);
	if (($result=self::fdfs_send_header($storage_server, $pkg_len, 
			STORAGE_PROTO_CMD_GET_METADATA, 0)) != 0)
	{
		break;
	}

	if (fwrite($storage_server['sock'], $body, $pkg_len) != $pkg_len)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	if (($result=self::fdfs_recv_response($storage_server, -1, $file_buff, $file_size)) != 0)
	{
		break;
	}

	if ($file_size == 0)
	{
		$meta_list = array();
		break;
	}

	$meta_list = self::fdfs_split_metadata($file_buff);
	break;
	}

	if ($new_connection)
	{
		self::fdfs_quit($storage_server);
		self::fdfs_disconnect_server($storage_server);
	}

	return $result;
}

/**
* delete file from the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name: the group name of storage server
* @param $filename: filename on storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_delete_file($tracker_server, $storage_server, 
			$group_name, $filename)
{
	if (($result=self::storage_get_update_connection($tracker_server, 
		$storage_server, $group_name, $filename, 
		$new_connection)) != 0)
	{
		return $result;
	}

	while (1)
	{
	/**
	send pkg format:
	FDFS_GROUP_NAME_MAX_LEN bytes: group_name
	remain bytes: filename
	**/

	$body = self::fdsf_pack_group_and_filename($group_name, $filename, $pkg_len);
	if (($result=self::fdfs_send_header($storage_server, $pkg_len, 
			STORAGE_PROTO_CMD_DELETE_FILE, 0)) != 0)
	{
		break;
	}

	if (fwrite($storage_server['sock'], $body, $pkg_len) != $pkg_len)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	$result = self::fdfs_recv_response($storage_server, 0, $in_buff, $in_bytes);
	break;
	}

	if ($new_connection)
	{
		self::fdfs_quit($storage_server);
		self::fdfs_disconnect_server($storage_server);
	}

	return $result;
}

/**
* download file from the storage server, internal public static function , do not use directly
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $remote_filename filename on storage server
* @param $file_offset the start offset of the file
* @param $download_bytes download bytes, 0 for remain bytes from offset
* @param $download_type FDFS_DOWNLOAD_TO_FILE for filename (write to file), 
*        FDFS_DOWNLOAD_TO_BUFF for file buff (write to buff)
*        FDFS_DOWNLOAD_TO_CALLBACK for callback
* @param $file_buff filename when $download_type is FDFS_DOWNLOAD_TO_FILE, 
*                   file buff when $download_type is FDFS_DOWNLOAD_TO_BUFF
*                   callback public static function  name when $download_type is FDFS_DOWNLOAD_TO_CALLBACK
* @param $arg callback public static function  extra argument
* @param $file_size return the file size (bytes)
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_do_download_file($tracker_server, $storage_server, $download_type,
		$group_name, $remote_filename, $file_offset, $download_bytes, 
		&$file_buff, $arg, &$file_size)
{
	$file_size = 0;
	if (($result=self::storage_get_read_connection($tracker_server, 
		$storage_server, $group_name, $remote_filename, 
		$new_connection)) != 0)
	{
		return $result;
	}

	while (1)
	{
	/**
	send pkg format:
	8 bytes: file start offset
	8 bytes: download bytes 
	FDFS_GROUP_NAME_MAX_LEN bytes: group_name
	remain bytes: filename
	**/
	$body = self::fdfs_long2buff($file_offset);
	$body .= self::fdfs_long2buff($download_bytes);
	$body .= self::fdsf_pack_group_and_filename($group_name, $remote_filename, $pkg_len);
	$pkg_len += 16;
	if (($result=self::fdfs_send_header($storage_server, $pkg_len, 
			STORAGE_PROTO_CMD_DOWNLOAD_FILE, 0)) != 0)
	{
		break;
	}

	if (fwrite($storage_server['sock'], $body, $pkg_len) != $pkg_len)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	if ($download_type == FDFS_DOWNLOAD_TO_FILE)
	{
		$result = self::fdfs_recv_header($storage_server, $in_bytes);
		if ($result != 0)
		{
			break;
		}

		$result = self::fdfs_recv_file($storage_server, $in_bytes, $file_buff);
		if ($result != 0)
		{
			break;
		}
	}
	else if ($download_type == FDFS_DOWNLOAD_TO_BUFF)
	{
		if (($result=self::fdfs_recv_response($storage_server, -1, $file_buff, $in_bytes)) != 0)
		{
			break;
		}
	}
	else
	{
		$result = self::fdfs_recv_header($storage_server, $in_bytes);
		if ($result != 0)
		{
			break;
		}

		$callback = $file_buff;
		$remain_bytes = $in_bytes;
		while ($remain_bytes > 0)
		{
			$s = fread($storage_server['sock'], $remain_bytes > 2048 ? 2048 : $remain_bytes);
			if (!$s)
			{
				error_log("server: ${storage_server['ip_addr']}:${storage_server['port']}, "
					. "fread fail");
				$result = FDFS_EIO;
				break;
			}

			$len = strlen($s);
			if (($result=call_user_func($callback, $arg, $in_bytes, $s, $len)) !== 0)
			{
				break;
			}

			$remain_bytes -= $len;
		}

		if ($remain_bytes != 0)
		{
			break;
		}
	}

	$file_size = $in_bytes;
	break;
	}

	if ($new_connection)
	{
		self::fdfs_quit($storage_server);
		self::fdfs_disconnect_server($storage_server);
	}

	return $result;
}

/**
* download file to file from the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $remote_filename filename on storage server
* @param $local_filename  local filename to write
*        note: the path of the file must in the php open_basedir
* @param $file_size return the file size (bytes)
* @param $file_offset the start offset of the file
* @param $download_bytes download bytes, 0 for remain bytes from offset
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_download_file_to_file($tracker_server, $storage_server, 
		$group_name, $remote_filename, $local_filename, &$file_size, 
		$file_offset = 0, $download_bytes = 0)
{
	return self::storage_do_download_file($tracker_server, $storage_server, 
		FDFS_DOWNLOAD_TO_FILE, $group_name, $remote_filename, 
		$file_offset, $download_bytes, $local_filename, null, $file_size);
}

/**
* download file to buff from the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $remote_filename filename on storage server
* @param $file_buff return the file buff (string)
* @param $file_size return the file size (bytes)
* @param $file_offset the start offset of the file
* @param $download_bytes download bytes, 0 for remain bytes from offset
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_download_file_to_buff($tracker_server, $storage_server, 
		$group_name, $remote_filename, &$file_buff, &$file_size, 
		$file_offset = 0, $download_bytes = 0)
{
	return self::storage_do_download_file($tracker_server, $storage_server,
			FDFS_DOWNLOAD_TO_BUFF, $group_name, $remote_filename, 
			$file_offset, $download_bytes, $file_buff, null, $file_size);
}

/**
* download file to buff from the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $remote_filename filename on storage server
* @param $callback callback public static function  name
* @param $arg callback public static function  extra argument
* @param $file_size return the file size (bytes)
* @param $file_offset the start offset of the file
* @param $download_bytes download bytes, 0 for remain bytes from offset
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_download_file_ex($tracker_server, $storage_server, 
		$group_name, $remote_filename, $callback, $arg, &$file_size, 
		$file_offset = 0, $download_bytes = 0)
{
	return self::storage_do_download_file($tracker_server, $storage_server, 
		FDFS_DOWNLOAD_TO_CALLBACK, $group_name, $remote_filename, 
		$file_offset, $download_bytes, $callback, $arg, $file_size);
}

/**
* upload file to the storage server, internal public static function , do not use directly
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $bFilename true for filename (read from file), 
* @param $file_buff filename when $bFilename is true, else file buff 
* @param $file_size the file size (bytes)
* @param $file_ext_name the file ext name (not including dot)
* @param $meta_list metadata assoc array (key value pair array)
* @param $group_name specify the group to upload file to, 
                     return the group name of the storage server
* @param $remote_filename return the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_do_upload_file($tracker_server, $storage_server, 
			$bFilename, $file_buff, $file_size, $file_ext_name,
			$meta_list, &$group_name, &$remote_filename)
{
	$remote_filename = '';
    $custom_param = ''; 
    if($meta_list && isset($meta_list['remote_filename'])) { 
		    $custom_param = $meta_list['remote_filename']; 
    }

    if (($result=self::storage_get_write_connection($tracker_server, $group_name,
		$storage_server, $new_connection, $custom_param)) != 0)
	{
		return $result;
	}

	$group_name = '';

	while (1)
	{
	/**
	1 byte: store path index
	8 bytes: meta data bytes
	8 bytes: file size
	FDFS_FILE_EXT_NAME_MAX_LEN bytes: file ext name
	meta data bytes: each meta data seperated by \x01,
			 name and value seperated by \x02
	file size bytes: file content
	**/
	if ($meta_list)
	{
		$meta_buff = self::fdfs_pack_metadata($meta_list);
		$meta_bytes = strlen($meta_buff);
	}
	else
	{
		$meta_buff = '';
		$meta_bytes = 0;
	}

	$pkg_len = 1 + 2 * FDFS_PROTO_PKG_LEN_SIZE + FDFS_FILE_EXT_NAME_MAX_LEN
		     + $meta_bytes + $file_size;
	if (($result=self::fdfs_send_header($storage_server, $pkg_len, 
			STORAGE_PROTO_CMD_UPLOAD_FILE, 0)) != 0)
	{
		break;
	}

	$body = chr(isset($storage_server['store_path_index']) ? $storage_server['store_path_index'] : 0)
		 . self::fdfs_long2buff($meta_bytes);
	$body .= self::fdfs_long2buff($file_size);
	if ($file_ext_name !== null)
	{
		$ext_name_len = strlen($file_ext_name);
		if ($ext_name_len > FDFS_FILE_EXT_NAME_MAX_LEN)
		{
			$ext_name_len = FDFS_FILE_EXT_NAME_MAX_LEN;
			$file_ext_name = substr($file_ext_name, 0, $ext_name_len);
		}
	}
	else
	{
		$file_ext_name = '';
	}
	$body .= str_pad($file_ext_name, FDFS_FILE_EXT_NAME_MAX_LEN, chr(0), STR_PAD_RIGHT);

	if (fwrite($storage_server['sock'], $body, 1 + 2 * FDFS_PROTO_PKG_LEN_SIZE + FDFS_FILE_EXT_NAME_MAX_LEN) 
		!= 1 + 2 * FDFS_PROTO_PKG_LEN_SIZE + FDFS_FILE_EXT_NAME_MAX_LEN)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	if ($meta_bytes > 0 && 
		fwrite($storage_server['sock'], $meta_buff, $meta_bytes) != $meta_bytes)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	if ($bFilename)
	{
		if (($result=self::fdfs_send_file($storage_server, $file_buff,
			$file_size)) != 0)
		{
			break;
		}
	}
	else
	{
		if ($file_size> 0 && fwrite($storage_server['sock'], 
			$file_buff, $file_size) != $file_size)
		{
			error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
				. "send data fail");
			$result = FDFS_EIO;
			break;
		}
	}

	if (($result=self::fdfs_recv_response($storage_server, 
		-1, $in_buff, $in_bytes)) != 0)
	{
		break;
	}

	if ($in_bytes <= FDFS_GROUP_NAME_MAX_LEN)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "length: $in_bytes is invalid, should > " . FDFS_GROUP_NAME_MAX_LEN);
		$result = FDFS_EINVAL;
		break;
	}

	$group_name = trim(substr($in_buff, 0, FDFS_GROUP_NAME_MAX_LEN));
	$remote_filename = substr($in_buff, FDFS_GROUP_NAME_MAX_LEN);

	break;
	}

	if ($new_connection)
	{
		self::fdfs_quit($storage_server);
		self::fdfs_disconnect_server($storage_server);
	}

	return $result;
}

/**
* upload file by filename to the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $local_filename local file name to upload 
*        note: the path of the file must in the php open_basedir
* @param $meta_list metadata assoc array (key value pair array)
* @param $group_name return the group name of the storage server
* @param $remote_filename return the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_upload_by_filename($tracker_server, $storage_server, 
			$local_filename, $meta_list, 
			&$group_name, &$remote_filename)

{
 	if (($attr=stat($local_filename)) === false)
	{
		$group_name = '';
		$remote_filename = '';
		return FDFS_EIO;
	}

	if (!is_file($local_filename))
	{
		$group_name = '';
		$remote_filename = '';
		return FDFS_EINVAL;
	}

	$pos = strrpos($local_filename, '.');
	if ($pos === false)
	{
		$file_ext_name = '';
	}
	else
	{
		$file_ext_name = substr($local_filename, $pos + 1);
	}

	return self::storage_do_upload_file($tracker_server, $storage_server, 
			true, $local_filename, $attr['size'], $file_ext_name, 
			$meta_list, $group_name, $remote_filename);
}

/**
* upload file by buff to the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $file_buff the file content to upload
* @param $file_size the file content length
* @param $file_ext_name the file ext name (not including dot)
* @param $meta_list metadata assoc array (key value pair array)
* @param $group_name return the group name of the storage server
* @param $remote_filename return the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_upload_by_filebuff($tracker_server, $storage_server, 
			$file_buff, $file_size, $file_ext_name, $meta_list, 
			&$group_name, &$remote_filename)
{
	return self::storage_do_upload_file($tracker_server, $storage_server, 
			false, $file_buff, $file_size, $file_ext_name,
			$meta_list, $group_name, $remote_filename);
}

/**
* change the metadata of the file on the storage server
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server the connected storage server (assoc array), can be null
* @param $group_name the group name of storage server
* @param $filename the filename on the storage server
* @param $meta_list metadata assoc array (key value pair array)
* @param $op_flag flag
*        STORAGE_SET_METADATA_FLAG_OVERWRITE('O') for overwrite all old metadata
*        STORAGE_SET_METADATA_FLAG_MERGE('M') for merge, insert when the meta 
*                                            item not exist, otherwise update it
* @return 0 for success, none zero (errno) for fail
*/
public static function  storage_set_metadata($tracker_server, $storage_server, 
			$group_name, $filename, 
			$meta_list, $op_flag)
{
	if (($result=self::storage_get_update_connection($tracker_server, 
		$storage_server, $group_name, $filename, 
		$new_connection)) != 0)
	{
		return $result;
	}

	/**
	the request pkg body format:
	8 bytes: filename length
	8 bytes: meta data size
	1 bytes: operation flag,
              'O' for overwrite all old metadata
	      'M' for merge, insert when the meta item not exist, 
                  otherwise update it
	FDFS_GROUP_NAME_MAX_LEN bytes: group_name
	filename
	meta data bytes: each meta data seperated by \x01,
                 name and value seperated by \x02
	**/
	while (1)
	{
	$filename_len = strlen($filename);

	if ($meta_list)
	{
		$meta_buff = self::fdfs_pack_metadata($meta_list);
		$meta_bytes = strlen($meta_buff);
	}
	else
	{
		$meta_buff = '';
		$meta_bytes = 0;
	}

	$body = self::fdfs_long2buff($filename_len);
	$body .= self::fdfs_long2buff($meta_bytes);
	$body .= $op_flag;

	$body .= self::fdsf_pack_group_and_filename($group_name, $filename, $pkg_len);
	$pkg_len += 2 * FDFS_PROTO_PKG_LEN_SIZE + 1;

	if (($result=self::fdfs_send_header($storage_server, $pkg_len + $meta_bytes, 
			STORAGE_PROTO_CMD_SET_METADATA, 0)) != 0)
	{
		break;
	}

	if (fwrite($storage_server['sock'], $body, $pkg_len) != $pkg_len)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	if ($meta_bytes > 0 
		&& fwrite($storage_server['sock'], $meta_buff, $meta_bytes) != $meta_bytes)
	{
		error_log("storage server: ${storage_server['ip_addr']}:${storage_server['port']}, "
			. "send data fail");
		$result = FDFS_EIO;
		break;
	}

	$result = self::fdfs_recv_response($storage_server, 0, $in_buff, $in_bytes);
	break;
	}

	if ($new_connection)
	{
		self::fdfs_quit($storage_server);
		self::fdfs_disconnect_server($storage_server);
	}

	return $result;
} 

/**
* disconnect server
* @param $server assoc array
* @return none
*/
public static function  fdfs_disconnect_server(&$server)
{
	if (is_resource($server['sock']))
	{
		fclose($server['sock']);
		unset($server['sock']);
	}
}

/**
* connect server
* @param $server assoc array
* @return 0 for success, none zero (errno) for fail
*/
public static function  fdfs_connect_server(&$server)
{
	$fdfs_network_timeout = self::$fdfs_network_timeout;

	if (isset($server['sock']) && is_resource($server['sock']))
	{
		return 0;
	}

	$sock = fsockopen($server['ip_addr'], $server['port'], $errno, $errstr, 
			$fdfs_network_timeout);
	if ($sock === false)
	{
		error_log("connect to ${server['ip_addr']}:${server['port']} " 
			. "fail, errno: $errno, error info: $errstr");
		return $errno;
	}

	stream_set_timeout($sock, $fdfs_network_timeout);
	$server['sock'] = $sock;

	return 0;
}

/**
* disconnect all tracker servers
* @param 
* @return none
*/
public static function  tracker_close_all_connections()
{
	$fdfs_tracker_servers = self::$fdfs_tracker_servers;
	foreach ($fdfs_tracker_servers as $server)
	{
		self::fdfs_disconnect_server($server);
	}
}

/**
* get connection to a tracker server
* @param 
* @return a connected tracker server(assoc array) for success, false for fail
*/

public static function  tracker_get_connection()
{
	$fdfs_tracker_servers = self::$fdfs_tracker_servers;
	$fdfs_tracker_server_index= self::$fdfs_tracker_server_index;
	$fdfs_tracker_server_count= self::$fdfs_tracker_server_count;
 

	if (count($fdfs_tracker_servers) == 0)
	{
		error_log("no tracker server!");
		return false;
	}

 
	$server = $fdfs_tracker_servers;
     
	if (is_resource($server['sock']) ||
		self::fdfs_connect_server($server) == 0)
	{
		$fdfs_tracker_server_index++;
		if ($fdfs_tracker_server_index >= $fdfs_tracker_server_count)
		{
			$fdfs_tracker_server_index = 0;
		}
		return $server;
	}

	for ($i=$fdfs_tracker_server_index+1; $i<$fdfs_tracker_server_count; $i++)
	{
		if (self::fdfs_connect_server($fdfs_tracker_servers[$i]) == 0)
		{
			return $fdfs_tracker_servers[$i];
		}
	}

	for ($i=0; $i<$fdfs_tracker_server_index; $i++)
	{
		if (self::fdfs_connect_server($fdfs_tracker_servers[$i]) == 0)
		{
			return $fdfs_tracker_servers[$i];
		}
	}

	return false;
}

/**
* query storage server to download file
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server return the storage server (assoc array, not connected)
* @param $group_name the group name of the storage server
* @param $filename the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  tracker_query_storage_update($tracker_server, &$storage_server, 
		$group_name, $filename)
{
	return self::tracker_do_query_storage($tracker_server, $storage_server, 
		TRACKER_PROTO_CMD_SERVICE_QUERY_UPDATE, $group_name, $filename);
}

/**
* query storage server to download file
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server return the storage server (assoc array, not connected)
* @param $group_name the group name of the storage server
* @param $filename the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  tracker_query_storage_fetch($tracker_server, &$storage_server, 
		$group_name, $filename)
{
	return self::tracker_do_query_storage($tracker_server, $storage_server, 
		TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ONE, $group_name, $filename);
}

/**
* query storage servers to download file
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server return the storage server (assoc array, not connected)
* @param $group_name the group name of the storage server
* @param $filename the filename on the storage server
* @return 0 for success, none zero (errno) for fail
*/
public static function  tracker_query_storage_list($tracker_server, &$storage_server, 
		$group_name, $filename)
{
	return self::tracker_do_query_storage($tracker_server, $storage_server, 
		TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ALL, $group_name, $filename, true);
}

/**
* query storage server to download file
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server return the storage server (assoc array, not connected)
* @param $cmd the query command
* @param $group_name the group name of the storage server
* @param $filename the filename on the storage server
* @param $multi_server if return multi-server
* @return 0 for success, none zero (errno) for fail
*/
public static function  tracker_do_query_storage($tracker_server, &$storage_server, $cmd, 
		$group_name, $filename, $multi_server=false)
{
	$body = self::fdsf_pack_group_and_filename($group_name, $filename, $pkg_len);
	if (($result=self::fdfs_send_header($tracker_server, $pkg_len, 
			$cmd, 0)) != 0)
	{
		return $result;
	}

	if (fwrite($tracker_server['sock'], $body, $pkg_len) != $pkg_len)
	{
		error_log("tracker server: ${tracker_server['ip_addr']}:${tracker_server['port']}, "
			. "send data fail");
		return FDFS_EIO;
	}

	if (($result=self::fdfs_recv_response($tracker_server, -1, $in_buff, $in_bytes)) != 0)
	{
		return $result;
	}

	if (($in_bytes - TRACKER_QUERY_STORAGE_FETCH_BODY_LEN) % (FDFS_IPADDR_SIZE - 1) != 0)
	{
		error_log("server: ${server['ip_addr']}:${server['port']}, "
			. "recv data fail");
		return FDFS_EINVAL;
	}

	$port = self::fdfs_buff2long(substr($in_buff, FDFS_GROUP_NAME_MAX_LEN+FDFS_IPADDR_SIZE-1));
	$storage_server0 = array('ip_addr' => trim(substr($in_buff, FDFS_GROUP_NAME_MAX_LEN, FDFS_IPADDR_SIZE-1)), 
				'port' => $port,
				'store_path_index' => 0,
				'sock' => -1);

	if (!$multi_server)
	{
		$storage_server = $storage_server0;
		return 0;
	}

	$storage_server = array();
	$storage_server[0] = $storage_server0;

	$server_count = ($in_bytes - TRACKER_QUERY_STORAGE_FETCH_BODY_LEN) / (FDFS_IPADDR_SIZE - 1);
	$offset = TRACKER_QUERY_STORAGE_FETCH_BODY_LEN;
	for ($i=1; $i<=$server_count; $i++)
	{
		$storage_server[$i] = array('ip_addr' => trim(substr($in_buff, $offset, FDFS_IPADDR_SIZE-1)), 
				'port' => $port,
				'store_path_index' => 0,
				'sock' => -1);
		$offset += FDFS_IPADDR_SIZE - 1;
	}

	return 0;
}

/**
* query storage server to upload file
* @param $tracker_server the connected tracker server (assoc array)
* @param $storage_server return the storage server (assoc array, not connected)
* @param $group_name the group to upload file to, can be empty
* @return 0 for success, none zero (errno) for fail
*/
public static function  tracker_query_storage_store($tracker_server, &$storage_server, $group_name, $custom_param)
{
	if (empty($group_name))
	{
		$cmd = TRACKER_PROTO_CMD_SERVICE_QUERY_STORE_WITHOUT_GROUP;
		$out_len = 0;
	}
	else
	{
		$cmd = TRACKER_PROTO_CMD_SERVICE_QUERY_STORE_WITH_GROUP;
		$out_len = FDFS_GROUP_NAME_MAX_LEN;

        if($custom_param) {
                   
            $cmd = CMD_QUERY_STORE_WITH_GROUP_REMOTE_FILENAME;
            $out_len = $out_len + FDFS_REMOTE_FILENAME_MAX_LEN;
        }         
	}


	if (($result=self::fdfs_send_header($tracker_server, $out_len, $cmd, 0)) != 0)
	{
		return $result;
	}

	if (!empty($group_name))
	{
		$groupname_len = strlen($group_name);
		if ($groupname_len >= FDFS_GROUP_NAME_MAX_LEN)
		{
			$body = substr($group_name, 0, FDFS_GROUP_NAME_MAX_LEN);
		}
		else
		{
			$body = $group_name;
			$body .= str_repeat("\000", FDFS_GROUP_NAME_MAX_LEN - $groupname_len);
		}
        $body_len = FDFS_GROUP_NAME_MAX_LEN;
        if (!empty($custom_param))
        {

            $custom_param_len = strlen($custom_param);
            if ($custom_param_len >= FDFS_REMOTE_FILENAME_MAX_LEN)
            {
                $body .= substr($group_name, 0, FDFS_REMOTE_FILENAME_MAX_LEN);
            }
            else
            {
                $body .= $custom_param;
                $body .= str_repeat("\000", FDFS_REMOTE_FILENAME_MAX_LEN - $custom_param_len);
            }

            $body_len = FDFS_GROUP_NAME_MAX_LEN + FDFS_REMOTE_FILENAME_MAX_LEN;
        } 

		if (fwrite($tracker_server['sock'], $body, $body_len) != $body_len)
		{
			error_log("server: ${server['ip_addr']}:${server['port']}, "
				. "send data fail");
			return FDFS_EIO;
		}
	}

	if (($result=self::fdfs_recv_response($tracker_server, TRACKER_QUERY_STORAGE_STORE_BODY_LEN, 
		$in_buff, $in_bytes)) != 0)
	{
		return $result;
	}

	$storage_server = array(
			'ip_addr' => trim(substr($in_buff, 
				FDFS_GROUP_NAME_MAX_LEN, FDFS_IPADDR_SIZE-1)), 
			'port' => self::fdfs_buff2long(substr($in_buff, 
				FDFS_GROUP_NAME_MAX_LEN+FDFS_IPADDR_SIZE-1, FDFS_PROTO_PKG_LEN_SIZE)),
			'store_path_index' => ord(substr($in_buff, 
				FDFS_GROUP_NAME_MAX_LEN+FDFS_IPADDR_SIZE-1+FDFS_PROTO_PKG_LEN_SIZE)),
			'sock' => -1
		);

	return 0;
}
}
?>
