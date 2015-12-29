<?php

/**
 * Define the website environment status. When this flag is set to TRUE, some
 * module demonstration controllers will result in 404 errors. For more information
 * about this option, read the documentation about deploying Kohana.
 *
 * @see http://docs.kohanaphp.com/installation/deployment
 */ 

define('FDFS_PROTO_CMD_QUIT',  82);
define('TRACKER_PROTO_CMD_SERVER_LIST_GROUP',  91);
define('TRACKER_PROTO_CMD_SERVER_LIST_STORAGE',  92);
define('TRACKER_PROTO_CMD_SERVER_RESP',  90);

define('TRACKER_PROTO_CMD_SERVICE_QUERY_STORE_WITHOUT_GROUP',  101);
define('TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ONE', 102);
define('TRACKER_PROTO_CMD_SERVICE_QUERY_UPDATE', 103);
define('TRACKER_PROTO_CMD_SERVICE_QUERY_STORE_WITH_GROUP', 104);
define('CMD_QUERY_STORE_WITH_GROUP_REMOTE_FILENAME', 106);
define('TRACKER_PROTO_CMD_SERVICE_QUERY_FETCH_ALL', 105);
define('TRACKER_PROTO_CMD_SERVICE_RESP',  100);

define('STORAGE_PROTO_CMD_UPLOAD_FILE',  11);
define('STORAGE_PROTO_CMD_DELETE_FILE',  12);
define('STORAGE_PROTO_CMD_SET_METADATA',  13);
define('STORAGE_PROTO_CMD_DOWNLOAD_FILE',  14);
define('STORAGE_PROTO_CMD_GET_METADATA',  15);
define('STORAGE_PROTO_CMD_RESP',  10);
define('FDFS_REMOTE_FILENAME_MAX_LEN',  256);

/**
 * for overwrite all old metadata
 */
define('STORAGE_SET_METADATA_FLAG_OVERWRITE', 'O');

/**
 * for replace, insert when the meta item not exist, otherwise update it
 */
define('STORAGE_SET_METADATA_FLAG_MERGE', 'M');

define('FDFS_PROTO_PKG_LEN_SIZE',  8);
define('FDFS_PROTO_CMD_SIZE',  1);
define('FDFS_GROUP_NAME_MAX_LEN',  16);
define('FDFS_IPADDR_SIZE',  16);
define('FDFS_RECORD_SEPERATOR',  "\001");
define('FDFS_FIELD_SEPERATOR',   "\002");

define('FDFS_PROTO_HEADER_LENGTH',  FDFS_PROTO_PKG_LEN_SIZE+2);
define('TRACKER_QUERY_STORAGE_FETCH_BODY_LEN',  FDFS_GROUP_NAME_MAX_LEN
		+ FDFS_IPADDR_SIZE - 1 + FDFS_PROTO_PKG_LEN_SIZE);
define('TRACKER_QUERY_STORAGE_STORE_BODY_LEN',  FDFS_GROUP_NAME_MAX_LEN
		+ FDFS_IPADDR_SIZE + FDFS_PROTO_PKG_LEN_SIZE);

define('FDFS_PROTO_HEADER_CMD_INDEX',  FDFS_PROTO_PKG_LEN_SIZE);
define('FDFS_PROTO_HEADER_STATUS_INDEX',  FDFS_PROTO_PKG_LEN_SIZE+1);

/* errno define, other errnos please see /usr/include/errno.h in UNIX system */
define('FDFS_EINVAL', 22);
define('FDFS_EIO', 5);

define('FDFS_DOWNLOAD_TO_BUFF',       1);
define('FDFS_DOWNLOAD_TO_FILE',       2);
define('FDFS_DOWNLOAD_TO_CALLBACK',   3);

define('FDFS_FILE_ID_SEPERATOR',   '/');
define('FDFS_FILE_EXT_NAME_MAX_LEN',   5);

?>