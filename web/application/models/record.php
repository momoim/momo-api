<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * 模型
 *
 */

class Record_Model extends Model {
	public static $instances = null;
	
    public function __construct() {
        parent::__construct();
    }
	
	public static function &instance() {
		if (! is_object ( Record_Model::$instances )) {
			// Create a new instance
			Record_Model::$instances = new Record_Model ();
		}
		return Record_Model::$instances;
	}

    //保存记录
    public function saveRecord($array) {
        return $this->db->insertData('record', $array);
    }

    //删除随表单提交的图片记录
    public function delByTmp($pid) {
        return $this->db->query("delete from tmpuploadimg where pid in (" . $pid . ")");
    }

    //插入提到的好友
    public function insertRecordFriend($rid, $fid) {
        $count = $this->db->getCount('record_friends', "rid='$rid' and fid='$fid'");
        if ($count < 1) //如果没有存在已提到的好友，则插入
            $this->db->insertData('record_friends', array('rid' => $rid, 'fid' => $fid));
    }

    public function getRecord($uid,$type,$start,$pos,$sort='date') {
        $wh		= '';
        if ($type ==2) {
            $rs		= $this->getMetionRecord($uid,$post,$start);
        }
        else
            $rs		= $this->db->fetchData('record','',array('uid'=>$uid),array('id'=>'desc'),$pos,$start);
        return $rs;
    }

    private function getMetionRecord($uid,$start,$pos) {
        $rs		= $this->db->fetchData('record_friends','rid',array('fid'=>$uid),$start,$pos);//取得提到我的日记id
        if ($rs->count()>0) {
            foreach ($rs as $row) {
                $rids[]	= $row->rid;
            }
            $imp	= implode(',',$rids);
            return	$this->db->query("select * from record where id in($imp)");
        }
        return false;
    }

    public function insertRecord($metion,$insert) {
        $id		= $this->db->insertData('record',$insert);
        if ($id) {
            if ($metion) {
                $ms	= explode(',',$metion);
                foreach ($ms as $m) {
                    $insert2	= array(
                            'rid'	=> $id,
                            'fid'	=> $m
                    );
                    $this->db->insertData('record_friends',$insert2);
                }
            }
            return true;
        }
        else
            return false;
    }

    public function deleteRecord($uid,$id) {
        $uid2	= $this->db->getOne('record','uid',"id='$id'");
        if ($uid == $uid2) {
            $this->db->deleteData('record',"id=$id");//删除这条广播
            //$this->db->deleteData('comment',array('appid'=>$id,'appdescribe'=>'record'));//删除评论及回复
            $this->db->deleteData('record_friends',array('rid'=>$id));//删除提到的广播
            $this->db->deleteData('record_collage',array('rid'=>$id));//删除所有收藏这条广播的记录
            return true;
        }
        else
            return false;
    }
    //删除广播，提到某人
    public function delAboutSb($rid) {
        $count = $this->db->getCount('record_friends', "rid='$rid'");
        if ($count >= 1) {
            return $this->db->deleteData('record_friends', array('rid' => $rid));
        }
    }
}
