<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 数据库操作模块
 *
 */

class Database extends Database_Core {
	/**
	 * 是否在执行事务
	 * @var bool
	 */
	protected $in_trans = FALSE;

    public function __construct($config=array()) {
        parent::__construct($config);
    }

    /**
     * 转换where数组
     *
     * @param array $where
     */
    private function convert_where($where) {
        if (is_array($where)) {
            foreach ($where as $key=>$value) {
                $this->where($key, $value);
            }
        }
    }	

    /**
     * 返回查询总数
     *
     * @param string $table
     * @param array $data
     * @return integer
     */
    public function getCount($table, $where='') {
        return $this->getOne($table, 'COUNT(*)', $where);
    }

    /**
     * 插入数据
     *
     * @param string $tablename
     * @param array $post
     * @return integer
     */
    public function insertData($tablename, $post) {
        $this->set($post);
        $return = $this->insert($tablename);

        return (count($return) > 0) ? $return->insert_id() : FALSE; 
    }

    /**
     * 更新数据
     *
     * @param string $tablename
     * @param array $data
     * @param mix $where
     * @return boolen
     */
    public function updateData($tablename,$data,$where='') {
        $return = $this->update($tablename, $data,$where); 

        return (count($return) > 0) ? count($return) : FALSE; 
    }

    /**
     * 删除数据
     *
     * @param string $tablename
     * @param mix $where
     * @return boolen
     */    
    public function deleteData($tablename, $where='') {
        $return = $this->delete($tablename, $where); 
        
        return (count($return) > 0) ? count($return) : FALSE;
    }

    /**
     * 取得某个字段的值
     *
     * @param string $table
     * @param string $field
     * @param array $where
     * @return string
     */
    public function getOne($table, $field, $where='') {
        if ($where) $where = " WHERE $where ";

        $SQL = "SELECT $field FROM $table $where";
        $rs = $this->query($SQL);
        $return = FALSE;

        foreach ($rs as $row) $return = @$row->$field;
        return $return;
    }

    //取得某行的数据
    public function getRow($table, $field='*', $where='') {
        if ($where) $where = " WHERE $where ";

        $SQL = "SELECT $field FROM $table $where";
        $rs = $this->query($SQL);
        $row = FALSE;

        foreach ($rs->result(FALSE) as $row) return $row;
    }

    //取得所有结果集的数据
    public function getAll($table, $field='*', $where='', $limit='') {
        if ($where) $where = " WHERE $where ";
        if ($limit) $limit = " LIMIT $limit ";

        $SQL = "SELECT $field FROM $table $where $limit";
        $rs = $this->query($SQL);
        $return = FALSE;

        foreach ($rs->result(FALSE) as $row) $return[] = $row;
        return $return;
    }

    /**
     * 取得表数据
     *
     * @param string $tablename
     * @param array $fields
     * @param array $where
     * @param array $order
     * @param integer $limit
     * @param integer $start
     * @return array
     */
    public function fetchData($tablename, $fields='*', $where='', $order='', $limit='', $start=0) {
        if (is_array($fields)) {
            $fields	= implode(',',$fields);
        }
        $this->select($fields);
        if ($where) $this->convert_where($where);
        if ($order) {
            foreach ($order as $key=>$value) {
                if (!$key) {
                    $key	= $value;
                    $value	= 'ASC';
                }
                $this->orderby($key, $value);
            }
        }
        if ($limit) $this->limit($limit, $start);
        $rs = $this->get($tablename);
        return $rs->result();
    }
    
	
    /**
     * 析构默认回滚
     */
	public function __destruct ()
	{
		self::rollback();
	}
	
	/**
	 * 开始事务
	 */
	public function begin ()
	{
		if (!$this->in_trans) {
			$this->query('SET AUTOCOMMIT=0');
		}
		$this->in_trans = TRUE;
	}

	/**
	 * 是否在事务处理
	 */
	public function in_trans ()
	{
		return $this->in_trans;
	}
	
	/**
	 * 提交事务
	 */
	public function commit ()
	{
		if ($this->in_trans) {
			$this->query('COMMIT');
			$this->query('SET AUTOCOMMIT=1');
			$this->in_trans = FALSE;
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * 回滚事务
	 */
	public function rollback ()
	{
		if ($this->in_trans) {
			$this->query('ROLLBACK');
			$this->query('SET AUTOCOMMIT=1');
		}
		$this->in_trans = FALSE;
	}
}