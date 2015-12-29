<?php defined('SYSPATH') OR die('No direct access allowed.');

//修正 kohana 的 memcache key 中不允许使用 "/" (eg."foo/bar") 的问题
class Cache extends Cache_Core {

	/**
	 * Replaces troublesome characters with underscores.
	 *
	 * @param   string   cache id
	 * @return  string
	 */
	protected function sanitize_id($id) {
		// Change slashes and spaces to underscores
		return str_replace(array('\\', ' '), '_', $id);
	}

} // End Cache