<?php defined('SYSPATH') or die('No direct script access.');

class Company_Model extends Model {
	public static $instances = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

	public static function &instance() {
		if (! is_object ( Company_Model::$instances )) {
			// Create a new instance
			Company_Model::$instances = new Company_Model ();
		}
		return Company_Model::$instances;
	}
    
    /**
     * 获取用户公司列表
     * @param Integer $uid
     * @param Integer $start
     * @param Integer $limit
     * @return Array
     */
    public function getCompanyList($uid)
    {
    	$query = $this->db->query("SELECT a.cid, b.name FROM company_member a LEFT JOIN company b ON a.cid = b.cid WHERE a.uid = $uid ORDER BY join_time DESC");
    	//$query = $this->db->fetchData('company_member', 'cid', array('uid' => $uid), array('join_time' => 'DESC'));
    	if ($query->count() == 0) {
    		return array();
    	}
        return $query->result_array(FALSE);
    }

    /**
     * 判断公司是否存在
     * @param String $name
     * @return Array|FALSE
     */
    public function checkCompanyName($name)
    {
        $this->db->where(array("name"=>$name))->get("company", 1);
        if ($query->count() == 0) {return false;}
        
        $result = $query->result_array(FALSE);
        return $result[0];
    }

    /**
     * 获取公司信息
     * @param Integer $company_id
     * @param Boolean $company_id
     * @return Array|FALSE
     */
    public function getCompanyInfo($company_id, $detail = false)
    {
        $this->db->from("company")->where(array("company.cid" => $company_id, "company.verify" => 1));
        
        if ($detail == true) {
            $this->db->select(array("company.*", "company_info.brief", "company_info.detail"))->join("company_info", "company_info.cid", "company.cid", "LEFT");
        }
        $query = $this->db->get();
        
        if ($query->count() == 0) {return false;}
        
        $result = $query->result_array(FALSE);
        return $result[0];
    }

    /**
     * 获取公司成员总数
     * @param Integer $company_id
     * @param Boolean $activity
     * @return Integera
     */
    public function getCompanyMemberTotal($company_id, $activity = true)
    {
        if ($activity == true) {
            $where = array("cid" => $company_id, "activity" => 1);
        } else {
            $where = array("cid" => $company_id);
        }
        
        return $this->db->where($where)->count_records("company_member");
    }

    /**
     * 获取公司成员列表
     * @param Integer $company_id
     * @param Integer $start
     * @param Integer $limit
     * @param Boolean $activity
     * @return Array
     */
    public function getCompanyMember($company_id, $start = 0, $limit = 20, $activity = true)
    {
		$query = $this->db->query("SELECT * FROM company_member WHERE cid = $company_id AND activity = 1 ORDER BY datetime DESC LIMIT $start, $limit");
		return $query->result_array(FALSE);
    }

    /**
     * 获取公司成员ID数组
     * @param Integer $company_id
     * @param Boolean $activity
     * @return Array
     */
    public function getCompanyMemberIds($company_id, $activity = true)
    {
        $this->db->select(array('uid'))->from("company_member");
        
        if ($activity == true) {
            $where = array("cid" => $company_id, "activity" => 1);
        } else {
            $where = array("cid" => $company_id);
        }
        $query = $this->db->where($where)->get();
        
        $ids = array();
        foreach ($query as $val) {
            $ids[] = $val->uid;
        }
        
        return $ids;
    }

    /**
     * 判断是否是公司成员
     * @param Integer $company_id
     * @param Integer $uid
     * @return Boolean
     */
    public function isCompanyMember($company_id, $uid)
    {
        $total = $this->db->where(array("cid" => $company_id, "uid" => $uid, "activity" => 1))->count_records("company_member");
        if ($total > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 加入公司
     * @param Integer $company_id
     * @param Integer $uid
     * @param Integer $grade
     * @param string $join_time
     * @param string $leave_time
     * @return Boolean
     */
    public function joinCompany($company_id, $uid, $grade = 2, $join_time = '', $leave_time = '')
    {
        $sql = "REPLACE INTO `company_member` (`cid`, `uid`, `grade`, `join_time`, `leave_time`, `datetime`, `activity`) VALUES ($company_id, $uid, $grade, '$join_time', '$leave_time', " . time() . ", 1)";
        $query = $this->db->query($sql);
        if ($query->count() == 0) {return false;}
        return true;
    }

    /**
     * 退出公司
     * @param Integer $company_id
     * @param Integer $uid
     * @return Boolean
     */
    public function quitCompany($company_id, $uid)
    {
        $return = $this->db->update("company_member", array("activity" => 0), array("cid" => $company_id, "uid" => $uid));
        
        return $return->count() ? TRUE : FALSE;
    }

    /**
     * 加入公司自动验证
     * @param Integer $company_id
     * @param string $no
     * @param string $name
     * @return Boolean
     */
    public function isWorker($cid, $no, $name)
    {
        $total = $this->db->where(array("cid"=>$cid, "staff_id"=>$no, "staff_name"=>$name))->count_records("company_staff");
        if ($total > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function searchMember($companyId, $userName, $sex, $start = 0, $pos = 20) {
		if(!$userName && !$sex) {
			return $this->getCompanyMember($companyId, $start, $pos);
		}
		$where = '';
		if($userName) {
			$where = "realname LIKE '%$userName%'";
		}
    	if($sex == 1 || $sex == 2) {
			if(!$where) {
				$where .= "sex = $sex";
			} else{
    			$where .= " AND sex = $sex";
			}
    	}
    	$query = $this->db->query("SELECT uid FROM company_member WHERE cid = $companyId AND uid IN (SELECT uid FROM membersinfo WHERE $where) LIMIT $start, $pos");
    	if ($query->count() == 0) {
    		return array();
    	}
        return $query->result_array(FALSE);
    }
    
    public function searchMemberTotal($companyId, $userName, $sex) {
		if(!$userName && !$sex) {
			return $this->getCompanyMemberTotal($companyId);
		}
    	$where = '';
		if($userName) {
			$where = "realname LIKE '%$userName%'";
		}
    	if($sex == 1 || $sex == 2) {
    		if(!$where) {
				$where .= "sex = $sex";
			} else{
    			$where .= " AND sex = $sex";
			}
    	}
    	$query = $this->db->query("SELECT COUNT(uid) AS num FROM company_member WHERE cid = $companyId AND uid IN (SELECT uid FROM membersinfo WHERE $where)");
    	if ($query->count() == 0) {
    		return 0;
    	}
        $result = $query->result_array(FALSE);
        return intval($result[0]['num']);
    }
    
}

