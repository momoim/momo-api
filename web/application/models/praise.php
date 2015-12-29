<?php defined('SYSPATH') or die('No direct script access.');
//赞模型

class Praise_Model extends Model {

    public function __construct() {
        parent::__construct();
    }
    
	/**
    * 新增某对象的赞
    * @param  String 被赞的应用类型，如日记 投票 以momoserver feedtype表为准
    * @param  Integer 被赞的对象ID
    * @param  Integer 被赞的用户ID
    * @param  Integer 说赞的用户ID
    * @param  String 说赞的姓名
	* @return Integer
    */
	public function addPraise($tplname, $objid, $owner, $uid, $name) {
		//判断是否己赞过
		$query = $this->db->where(array('tplname'=>$tplname,'objid'=>$objid,'uid'=>$uid))->get('praise', 1, 0);
		if($res = $query->result_array(FALSE)){
			return (int)$res[0]['id'];
		}
		
		$field  = array('tplname'=>$tplname,'objid'=>$objid,'owner'=>$owner,'uid'=>$uid,'name'=>(string)$name,'addtime'=>time());
		return $this->db->insertData('praise', $field);
	}
	
	/*
	 * 取得某对象赞的总数
    * @param  String 被赞的应用类型，如日记 投票 以momoserver feedtype表为准
    * @param  Integer 被赞的对象ID
	* @return Integer
	 */
	public function getPraiseCount($tplname, $objid)
	{
		return $this->db->getCount('praise',"`tplname`='$tplname' AND `objid`='$objid'");
	}
	
	/*
	 * 取得赞过某对象的用户信息
    * @param  String 被赞的应用类型，如日记 投票 以momoserver feedtype表为准
    * @param  Integer 被赞的对象ID
    * @param  Integer 偏移量
    * @param  Integer 限制条数
    * @param  Integer 查看者ID（首页调用的因为要找出查看者本自己的ID）
	* @return Array
	 */
	public function getPraiseUser($tplname, $objid, $start=0, $limit=12, $vid=null)
	{
		if(null===$vid){
			return $this->db->getAll('praise', '*', "`tplname`='$tplname' AND `objid`='$objid' ORDER BY id DESC", "$start, $limit");
		}else{
			$SQL = "(SELECT `id`,`uid`,`name` FROM praise  WHERE `tplname`='$tplname' AND `objid`='$objid' AND uid=$vid LIMIT 1) UNION (SELECT `id`,`uid`,`name` FROM praise  WHERE `tplname`='$tplname' AND `objid`='$objid' ORDER BY id DESC LIMIT 0, 2)";
			$res = $this->db->query($SQL);
			return $res->result_array(FALSE);
		}
	}
}
