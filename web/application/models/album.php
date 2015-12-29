<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [MOMO] (C)1999-2009 ND Inc.
 * 相册模型文件
 */

class Album_Model extends Model {
     
    public function __construct() {
        parent::__construct();
        $this->uid = Session::instance()->get('uid');
    }
      
    public function test(){ 
		$this->photo_model = new Photo_Model;
		print_r($this->photo_model->wpThumb('2017',true));exit;
		$data = $this->db->getAll("album_user_dynamic","album_id","user_id=7");
		var_dump(count($data));exit;
        return count($data); 
          $data = $this->db->getAll('album_pic','pic_id',"album_id =4829");
		  print_r($data);exit;
	}
    /**
     * 检查用户对加密相册的访问权限
     * @param int $user_id
     * @param int $album_id
     */
     public function checkAlbumPermission($album_id, $user_id) {
        return $this->db->getOne('album_pwd_users','album_id',"album_id = $album_id AND user_id = $user_id");
     }

    /**
     * 获取相册封面图片信息
     * @param int $pic_id 
     */
     public function getAlbumCoverInfo($pic_id) {
        return $this->db->getRow('album_pic','pic_width, pic_height, degree',"pic_id = $pic_id");
     }
	 
	 /**
     * 获取相册封面图片信息
     * @param int $pic_id 
     */
     public function getGroupAlbumCoverInfo($pic_id) {
        return $this->db->getRow('album_group_pic','pic_width, pic_height, degree',"pic_id = $pic_id");
     }

    /**
     * 旋转角度转化
     * @param int $degree 
     */
     public function tran_degree($degree) {
        if($degree == 1) {
            $return_degree = 90;

        } else if($degree == 2) {
            $return_degree = 180;

        } else if($degree == 3) {
            $return_degree = 270;

        } else {
            $return_degree = 0;
            
        }

        return $return_degree;
     }
     
     //初始化相册封面
		public function initAlbumCover($album_id) {
			$rs = $this->db->getRow("album_user_album", "pic_num, cover_pic_id, cover_pic_url", "album_id=$album_id");
			if($rs) {
				$pic_num = $rs['pic_num'];
				$cover_pic_id = $rs['cover_pic_id'];
				$cover_pic_url = $rs['cover_pic_url'];
				//相册有数量、无封面
				if($pic_num && !$cover_pic_id) {
					$pic_id = $this->db->getOne("album_pic", "pic_id", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,1");
					if(!$pic_id) {
						$this->db->query("UPDATE album_user_album SET pic_num = '0' WHERE album_id = $album_id");
						$cover_pic_id = 0;
						$cover_pic_url = "";
						$pic_num = 0;
					} else {
						$cover_pic_id = $pic_id;
						$cover_pic_url = "thumb/".$pic_id."_160.jpg"; 
					}
				} else if(!$pic_num && $cover_pic_id) {
					$pic_num = $this->db->getCount("album_pic", "album_id=$album_id");
					if(!$pic_num) {
						$cover_pic_id = 0;
						$cover_pic_url = ""; 				
					} else {
						$pic_id = $this->db->getOne("album_pic", "pic_id", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,1");
						$cover_pic_id = $pic_id;
						$cover_pic_url = "thumb/".$pic_id."_160.jpg"; 
					}
				} else {
					//获取正常的相册封面
				}

				$this->db->updateData("album_user_album", array("cover_pic_id" => $cover_pic_id, "cover_pic_url" => $cover_pic_url, "pic_num" => $pic_num), array("album_id" => $album_id));
				return array("cover_pic_id" => $cover_pic_id, "cover_pic_url" => $cover_pic_url, "pic_num" => $pic_num);
			}			
		} 

        //初始化群组相册封面
		public function initGroupAlbumCover($album_id) {
			$rs = $this->db->getRow("album_group_album", "pic_num, cover_pic_id, cover_pic_url", "album_id=$album_id");
			if($rs) {
				$pic_num = $rs['pic_num'];
				$cover_pic_id = $rs['cover_pic_id'];
				$cover_pic_url = $rs['cover_pic_url'];
				//相册有数量、无封面
				if($pic_num && !$cover_pic_id) {
					$pic_id = $this->db->getOne("album_group_pic", "pic_id", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,1");
					if(!$pic_id) {
						$this->db->query("UPDATE album_group_album SET pic_num = '0' WHERE album_id = $album_id");
						$cover_pic_id = 0;
						$cover_pic_url = "";
						$pic_num = 0;
					} else {
						$cover_pic_id = $pic_id;
						$cover_pic_url = "groupthumb/".$pic_id."_80.jpg"; 
					}
				} else if(!$pic_num && $cover_pic_id) {
					$pic_num = $this->db->getCount("album_group_pic", "album_id=$album_id");
					if(!$pic_num) {
						$cover_pic_id = 0;
						$cover_pic_url = ""; 				
					} else {
						$pic_id = $this->db->getOne("album_group_pic", "pic_id", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,1");
						$cover_pic_id = $pic_id;
						$cover_pic_url = "groupthumb/".$pic_id."_80.jpg"; 
					}
				} else {
					//获取正常的相册封面
				}

				$this->db->updateData("album_group_album", array("cover_pic_id" => $cover_pic_id, "cover_pic_url" => $cover_pic_url, "pic_num" => $pic_num), array("album_id" => $album_id));
				return array("cover_pic_id" => $cover_pic_id, "cover_pic_url" => $cover_pic_url, "pic_num" => $pic_num);
			}			
		} 
     
    /**
     * 根据用户ID获取相册列表
     * @param int $user_id
     * @param int $start
     * @param int $pos
     * @return array
     */
    public function getAlbumListByUid($user_id, $start = 0, $pos = 0, $privacy = 0, $is_for_upload = false) { 
		$sql = "";
		if($pos) $sql = "LIMIT $start, $pos"; 
		$rs = $this->db->getAll("album_user_album", "SQL_CALC_FOUND_ROWS album_id, album_name, user_id, create_dt, update_dt, album_pwd_prompt, pic_num, cover_pic_id, cover_pic_url, privacy_lev, album_desc, album_spot, album_default", "user_id=$user_id AND album_default != 2  AND pic_num != 0 ORDER BY album_id DESC $sql");
		if($rs) {
			//获取总的数量 
			if($sql) {
			   $total_query = $this->db->query("SELECT FOUND_ROWS();");
			   $total_result = $total_query->result_array(FALSE);
			   $limit_total = $total_result[0]['FOUND_ROWS()']; 
			}
			$result = array();
			foreach($rs as $i => $value) {
					$result['data']['data'][$i]['album_id'] = $value['album_id'];
					$result['data']['data'][$i]['user_id'] = $user_id;
					$result['data']['data'][$i]['create_time'] = $this->_transTime(date('YmdHis', $value['create_dt']));
					$result['data']['data'][$i]['update_time'] = $this->_transTime(date('YmdHis', $value['update_dt']));
					$result['data']['data'][$i]['pic_num'] = $value['pic_num'];
					$result['data']['data'][$i]['album_desc'] = $value['album_desc'];
					$result['data']['data'][$i]['album_default'] = $value['album_default'];
					$result['data']['data'][$i]['pics'] = $this->getPhoto($value['album_id'], Kohana::config('album.covernum'));
			}
			$result['data']['total'] = count($result['data']['data']);
			$result['data']['limit_total']  = $limit_total ? $limit_total : count($result['data']['data']);
			return $result;
		} else {
			return null;
		}
    }

	//获取照片
	//$pos获取的数量
	public function getPhoto($album_id, $pos) {
		$result = $this->db->getAll("album_pic", "pic_id, pic_title, pic_width, pic_height, degree", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,$pos");
		if(!$result) return null;
		foreach($result as $key => $row) {
			$return[$key]['pic_id'] = $row['pic_id'];
			$return[$key]['pic_title'] = $row['pic_title'];
			$return[$key]['pic_width'] = $row['pic_width'];
			$return[$key]['pic_height'] = $row['pic_height'];
			if($row['pic_width'] > $row['pic_height']) {
				$return[$key]['width'] = 130;
				$return[$key]['height'] =  intval($row['pic_height'] / ($row['pic_width'] / 130));
			} else {
				$return[$key]['height'] = 130;
				$return[$key]['width'] =  intval($row['pic_width'] / ($row['pic_height'] / 130));
			
			}
			$return[$key]['degree'] = $this->tran_degree($row['degree']);
			$return[$key]['url'] = Kohana::config('album.thumb'). 'thumb/' . $row['pic_id']."_160.jpg";
		}
		return $return;
	}
  
	/**
     * 根据群组ID获取相册列表
     * @param int $group_id
     * @param int $start
     * @param int $pos
     * @return array
     */
    public function getAlbumListByGid($group_id, $start = 0, $pos = 0) { 
		$sql = "LIMIT $start, $pos"; 
		$rs = $this->db->getAll("album_group_album", "SQL_CALC_FOUND_ROWS album_id, album_name, user_id, create_dt, update_dt, pic_num, cover_pic_id, cover_pic_url, privacy_lev,  album_desc", "group_id=$group_id AND  pic_num != 0 ORDER BY album_id DESC $sql");
		
		if($rs) {
			//获取总的数量 
			if($sql) {
			   $total_query = $this->db->query("SELECT FOUND_ROWS();");
			   $total_result = $total_query->result_array(FALSE);
			   $limit_total = $total_result[0]['FOUND_ROWS()']; 
			}
			$result = array();
			foreach($rs as $i => $value) {
					$result['data']['data'][$i]['album_id'] = $value['album_id'];
					$result['data']['data'][$i]['album_name'] = $value['album_name'];
					$result['data']['data'][$i]['user_id'] = $value['user_id'];
					$result['data']['data'][$i]['create_time'] = $this->_transTime(date('YmdHis', $value['create_dt']));
					$result['data']['data'][$i]['update_time'] = $this->_transTime(date('YmdHis', $value['update_dt']));
					$result['data']['data'][$i]['pic_num'] = $value['pic_num']; 
					$result['data']['data'][$i]['album_desc'] = $value['album_desc']; 
					$result['data']['data'][$i]['pics'] = $this->getGroupPhoto($value['album_id'], Kohana::config('album.covernum'));
			}
			$result['data']['total'] = count($result['data']['data']);
			$result['data']['limit_total']  = $limit_total ? $limit_total : count($result['data']['data']);
			return $result;
		} else {
			return null;
		}
    }
	//获取群组照片
	//$pos获取的数量
	public function getGroupPhoto($album_id, $pos) {
		$result = $this->db->getAll("album_group_pic", "pic_id, pic_title, pic_width, pic_height, degree", "album_id=$album_id ORDER BY pic_id DESC LIMIT 0,$pos");
		if(!$result) return null;
		foreach($result as $key => $row) {
			$return[$key]['pic_id'] = $row['pic_id'];
			$return[$key]['pic_title'] = $row['pic_title'];
			$return[$key]['pic_width'] = $row['pic_width'];
			$return[$key]['pic_height'] = $row['pic_height'];
			if($row['pic_width'] > $row['pic_height']) {
				$return[$key]['width'] = 130;
				$return[$key]['height'] =  intval($row['pic_height'] / ($row['pic_width'] / 130));
			} else {
				$return[$key]['height'] = 130;
				$return[$key]['width'] =  intval($row['pic_width'] / ($row['pic_height'] / 130));			
			}
			$return[$key]['degree'] = $this->tran_degree($row['degree']);
			$return[$key]['url'] = Kohana::config('album.thumb'). 'groupthumb/' . $row['pic_id']."_160.jpg";
		}
		return $return;
	}
    /**
     * 获取好友的相册
     * @param int $uid 用户ID
     * @return Array
     */
    public function getFriendAlbum($uid, $start = 0, $pos = 10) {
        //获取所有的好友
        $fids = "";
        $uid = $this->uid;
        if(!$this->Friend) $this->Friend = new Friend_Model;
        $rs = $this->Friend->getAllFriendIDs($uid);

		//获取限制访问照片的黑名单
		//$this->Account = new Account_Model;
		//$blackList = $this->Account->getUserlimitList($uid, 'photopermit');
		//if($blackList && $rs)  $rs = array_diff($rs, $blackList);

        if($rs) $fids = implode(",", $rs); 
        if(!$fids) return null;
        $data = $this->db->getAll("album_user_dynamic","album_id","user_id IN ($fids)");
		if($data) {
			foreach($data as $key => $row) {
				if($key == 0) $album_ids = $row['album_id'];
				else $album_ids .= ','.$row['album_id'];
			}
		}
        //无好友最新相册
        if(!$album_ids) return null;
        $return = array();
        $i = 0;
		//显示最近90天内上传的好友照片
		$t = time()-60*60*24*90;
        $result = $this->db->getAll("album_user_album", "SQL_CALC_FOUND_ROWS album_id, album_name, user_id, create_dt, update_dt, album_pwd_prompt, pic_num, cover_pic_id, cover_pic_url, privacy_lev, create_dt, update_dt, album_desc, album_spot, album_default", "album_id IN ($album_ids)  AND create_dt > $t ORDER BY update_dt DESC LIMIT $start,$pos"); 
		
	    $total_query = $this->db->query("SELECT FOUND_ROWS();");
	    $total_result = $total_query->result_array(FALSE);
	    $limit_total = $total_result[0]['FOUND_ROWS()'];
		if($result) {
			foreach( $result as $key => $value ) {
					$return['data']['data'][$i]['album_id'] = $value['album_id'];
					$return['data']['data'][$i]['album_name'] = $value['album_name'];
					$return['data']['data'][$i]['user_id'] = $value['user_id'];
					$return['data']['data'][$i]['create_time'] = $this->_transTime(date('YmdHis', $value['create_dt']));
					$return['data']['data'][$i]['update_time'] = $this->_transTime(date('YmdHis', $value['update_dt']));
					$return['data']['data'][$i]['pic_num'] = $value['pic_num'];
					$return['data']['data'][$i]['privacy_lev'] = $value['privacy_lev'];
					$return['data']['data'][$i]['album_desc'] = $value['album_desc'];
					$return['data']['data'][$i]['album_default'] = $value['album_default'];
					$return['data']['data'][$i]['permission'] = true ;
					$return['data']['data'][$i]['pics'] = $this->getPhoto($value['album_id'], Kohana::config('album.covernum'));
					$i++;           
			}
		}
        $resultArray['code'] = 200;
        $resultArray['data']['total'] = count($return['data']['data']);
        $resultArray['data']['limit_total'] = $limit_total;
        $resultArray['data']['data'] = $return['data']['data']; 
        return $resultArray;
    }

    /**
     * 创建相册
     * @param array $album_info
     * @return array
     */
    public function addAlbum($album_info, $fids="") { 
        if($album_info) {
            $album_info['user_id'] = $this->uid;
            $album_info['create_dt'] = time();
            $album_info['update_dt'] = time();
            $album_info['category_id'] = $album_info['album_category_id'];
            unset($album_info['album_category_id']);
        }
		if($album_info['privacy_lev'] == 1) $album_info['privacy_lev'] = 2;
        $album_id = $this->db->insertData("album_user_album", $album_info);
        if($album_id) { 
			//相册照片排序
			$this->db->insertData("album_pic_sorts", array("album_id" => $album_id, "sorts" => "", "update_dt" => time()));
            //更新相册排序
            $album_sorts = $this->db->getOne('album_sorts','sorts','user_id='.$this->uid);
            if(!$album_sorts) {
                $album_sorts = $album_id;
            } else {
                $album_sorts = $album_id.','.$album_sorts;
            }
            $album_sorts = $this->db->updateData('album_sorts',array('sorts'=>$album_sorts),array('user_id' => $this->uid));
            //更新用户最新相册
            if($album_info['privacy_lev'] == 2) {
                $return = $this->db->updateData('album_user_dynamic', array('album_id'=>$album_id, 'update_dt' => time()), array('user_id' => $this->uid));
                if(!$return) {
                    $return = $this->db->insertData('album_user_dynamic', array('album_id'=>$album_id, 'update_dt' => time(),'user_id' => $this->uid));
                }
            }
			//指定好友可见
			if($album_info['privacy_lev'] == 3 && $fids) {
				$fid = explode(",", $fids);
				foreach($fid as $row){
					$this->db->insertData("album_pwd_users", array("user_id" => $row, "album_id" => $album_id));
				}
			}
            $result['code'] = 200;
            $result['data'] = $album_id; 
        } else {
            $result['code'] = 500;
            $result['data']['msg'] = ''; 
        }

        return $result;
    }

    /**
     * 更新相册信息
     * @param int $album_id
     * @param array $album_info
     * @return array
     */
    public function updateAlbumInfo($album_id, $album_info, $fids="") {
        if(!$album_info || !$album_info['album_name'] || !$album_info['privacy_lev']) {
            $result['code'] = 405;
            $result['data']['msg'] = '';
        }
        if($album_info['privacy_lev'] == 3 && $fids == "") { 
            $result['code'] = 405;
            $result['data']['msg'] = '';
        }
		
		$album_info['update_dt'] = time();
        $return = $this->db->updateData("album_user_album", $album_info, array("album_id" => $album_id));
        if($return) {
			$this->db->deleteData("album_pwd_users",array("album_id" => $album_id));
			if($album_info['privacy_lev'] == 3) {
				if($fids) {
					$fid = explode(",", $fids);
					foreach($fid as $row){
						$this->db->insertData("album_pwd_users", array("user_id" => $row, "album_id" => $album_id));
					}
				}
			}
            $result['code'] = 200;
            $result['data'] = ''; 
        } else {
            $result['code'] = 500;
            $result['data']['msg'] = ''; 
        }
        return $result;
    }

    /**
     * 删除相册
     * @param int $album_id
     * @return array
     */
    public function deleteAlbumByAid($album_id) {
        //删除相册 album_user_album
        //删除图片 album_pic album_pic_desc album_pic_sorts
        //更新用户相册排序
        //判断用户是否有权限
        $return = $this->db->getRow("album_user_album", "album_id, privacy_lev, album_default", "album_id = $album_id AND user_id =".$this->uid);
        if(!$return || $return['album_default'] > 0 ) {
            $result['code'] = 403;
            $result['data']['msg'] = '无权限';
        } else {             
            $return = $this->db->deleteData("album_user_album","album_id=$album_id");
            if($return) {
                $result['code'] = 200;                  
				//更新用户最新相册动态表
				$return = $this->db->getOne("album_user_dynamic", "id", "album_id=$album_id AND user_id=".$this->uid);
				if($return) {
					$uid = $this->uid;
					$dynamic_album_id = $this->db->getOne("album_user_album","album_id","user_id=$uid AND pic_num != 0 AND album_default != 2 ORDER BY update_dt DESC");
					if($dynamic_album_id) {
						$return = $this->db->updateData('album_user_dynamic', array('album_id'=>$dynamic_album_id, 'update_dt' => time()), array('user_id' => $uid));
					} else {                            
						$this->db->deleteData("album_user_dynamic","user_id=$uid");
					}
				} 
            }

            //更新TTServer
            $this->TT = new TTServer;
            $data = $this->db->getAll("album_pic","pic_id, file_md5","album_id=$album_id");
			if($data) {
				$this->Feed = new Feed_Model;
				foreach($data as $row) {                
					$this->TT->del($row['file_md5']);
					//动态模板'typeid'=>13,'typename'=>'photo_comment'
					$this->Feed->delFeed(13, $row['pic_id']);
				}
				$this->db->deleteData("album_pic","album_id=$album_id");  	
				//删除相册的动态
				//动态模板'typeid'=>21,'typename'=>'album'
				$this->Feed->delFeed(26, $album_id);
			}  
        } 

        return $result;
    }
	//删除群组相册
	public function deleteGroupAlbumByAid($album_id, $user_id) {
		if(!$album_id) return array("code" => 403);
		//判断权限
		$rs = $this->db->getOne("album_group_album", "group_id", "album_id=$album_id AND user_id=$user_id");
		if(!$rs) return array("code" => 403);
		//删除群组相册
		$rs = $this->db->deleteData("album_group_album", array("album_id"=>$album_id));
		if($rs) {
			//上传动态
			$this->Feed = new Feed_Model;
			$this->Feed->delFeed(27, $album_id);
            //更新TTServer
            $this->TT = new TTServer;
			//删除群组相册中的照片
			$data = $this->db->getAll("album_group_pic", "pic_id, file_md5", "album_id=$album_id");
			if($data) {
				foreach($data as $row) {
					$this->Feed->delFeed(20, $row['pic_id']);
					$this->TT->del($row['file_md5']);
				}
				$this->db->deleteData("album_group_pic", array("album_id"=>$album_id));
			}
			return array("code" => 200);
		}
		return array("code" => 500);
	}

	//获取指定好友的id
	public function getAlbumFidID($album_id, $pic_id = 0) {
		if(!$album_id && !$pic_id) return null;
		if(!$album_id) {
			$album_id = $this->db->getOne("album_pic","album_id","pic_id=$pic_id");
		}
		$result = $this->db->getAll("album_pwd_users","user_id","album_id=$album_id");
		if(!$result) return null;
		foreach($result as $key => $row) {
			if($key == 0) $fids = $row['user_id'];
			else  $fids =  $fids.','.$row['user_id'];
		}
		return $fids;
	}

    /**
     * 获取相册信息
     * @param int $album_id
     * @return array
     */
    public function getAlbumInfoByAid($album_id) {
        $result = array();
        $return = $this->db->getRow("album_user_album", "album_id, user_id, create_dt, update_dt, album_name, pic_num, cover_pic_id, cover_pic_url, privacy_lev, album_pwd_prompt, album_desc, album_spot, album_default", "album_id=$album_id");
        if($return) {            
            $result['code'] = 200;
            if($return['cover_pic_url']) $return['cover_pic_url'] = Kohana::config('album.thumb').$return['cover_pic_url'];
			if($return['privacy_lev'] == '3' ) {
				$return['fids'] = $this->getAlbumFidID($album_id);
			} else {
				$return['fids'] = "";
			}
            $result['data'] = $return;  
        } else {
            $result['code'] = 204;
            $result['data']['msg'] = '';    
        } 
        return $result;
    }
	//获取群相册信息
	public function getGroupAlbumInfoByAid($album_id) {
		$return = $this->db->getRow("album_group_album","user_id, create_dt, update_dt, group_id, album_desc", "album_id=$album_id");
		if($return) {
			$result['code'] = 200;
			$return['gname'] = $this->getGroupName($return['group_id']);
		} else {
			$result['code'] = 500;			
		}
		$result['data'] = $return;
		return $result;
	}
	 //获取群组名册
	 public function getGroupName($group_id) {
		return $this->db->getOne("`group`","gname","gid=$group_id");
	}
    /**
     * 相册密码校验
     * @param array $album_id 相册ID
     * @param array $data 密码数据
     * @return array
     */
    public function checkAuth($album_id, $data) {
        $return = $this->db->getRow("album_user_album","user_id, album_pwd","album_id=$album_id");
        if(!$return || $return['album_pwd'] != $data['password']) {            
            $result['code'] = 403;
            $result['data']['msg'] = '密码错误';
        } else {
            if($this->uid != $return['user_id']) {
                $this->db->insertData("album_pwd_users", array("album_id" => $album_id, "user_id" => $this->uid)); 
            }
            $result['code'] = 200;
        }
        return $result;
    }

    //时间格式转换
    private function _transTime($time) {
        $year = substr($time, 0, 4);
        $month = substr($time, 4, 2);
        $day = substr($time, 6, 2);
        $hour = substr($time, 8, 2);
        $minute = substr($time, 10, 2);
        $second = substr($time, 12, 2);
        return sns::gettime(mktime($hour, $minute, $second, $month, $day, $year), 'Y年m月d日');
    }
 
    
    /**
     * 获取用户相册数量
     * @param array $user_id 
     */
     public function getAlbumNum($user_id) {
		 
        $user_id = $user_id ? $user_id : $this->uid;
		return $this->db->getCount("album_user_album","user_id=$user_id AND album_default != 2");

		$count = 0;
		$album_sorts = $this->db->getOne('album_sorts','sorts','user_id='.$user_id);
		if($this->uid == $user_id) {
			$album_sorts_array = explode(",", $album_sorts);
			$count = count($album_sorts_array);
		} else {   
			if(!$this->Friend) $this->Friend = new Friend_Model;
			$is_friend = $this->Friend->getCheckIsFriend($this->uid, $user_id);
			if($is_friend) {
				$rs = $this->db->getAll("album_user_album","album_id,privacy_lev","album_id IN ($album_sorts)");   
				if($rs) {
					foreach($rs as $row) {
						if($row['privacy_lev'] == '2') {
							$count++;
						} else if ($row['privacy_lev']== '3') {
							//指定好友可见相册是否有权限
							$allow = $this->checkAlbumPermission($row['album_id'], $this->uid);
							if($allow) $count++;
						}
					} 
				} 
			}  
		}
		return $count;
    }

    public function getFriendAlbumNum($user_id) {
        //获取所有的好友
        $fids = "";
        if(!$this->Friend) $this->Friend = new Friend_Model;
        $rs = $this->Friend->getAllFriendIDs($user_id);

		//获取限制访问照片的黑名单
		$this->Account = new Account_Model;
		$blackList = $this->Account->getUserlimitList($user_id, 'photopermit');
		if($blackList && $rs)  $rs = array_diff($rs, $blackList);

        if($rs) $fids = implode(",", $rs); 
        if(!$fids) return null;
        
        $data = $this->db->getAll("album_user_dynamic","album_id","user_id IN ($fids)");
        return $data ? count($data) : 0; 
    }

    public function getPhotoNum($album_id) {
		return $this->db->getOne("album_user_album", "pic_num", "album_id=$album_id");
	}
    
	//获取相册的权限等级
	public function getAlbumPrivacy($album_id,$pic_id=0) {
		if($pic_id) {
			$album_id = $this->db->getOne("album_pic", "album_id", "pic_id=$pic_id");
		}
		if(!$album_id) return 0;
		return $this->db->getOne("album_user_album", "privacy_lev", "album_id=$album_id");
	}

	//注册时用户初始化相册数据
	public function initAlbumData($user_id) {
		//创建头像照 
		$data = array(
                'album_name' => '头像照',
                'privacy_lev' => 2,
                'album_desc' => '',
                'allow_comment' => 1,
                'allow_repost' => 1,
                'album_default' => 2,
                'create_dt' => time(),
                'update_dt' => time(),
				'user_id' => $user_id
            );
        $album_id_avatar = $this->db->insertData("album_user_album", $data);   
		 
		return $album_id_avatar;
	}

	//修改相册描述
	public function updateAlbumDesc($album_id, $desc, $group_id){
		if($group_id) {
			return $this->db->updateData("album_group_album", array("album_desc" => $desc), array("album_id" => $album_id, "group_id" => $group_id));
		} else {
			return $this->db->updateData("album_user_album", array("album_desc" => $desc), array("album_id" => $album_id));			
		}
	}

	//获取头像照信息
	public function getAvatarAlbum($user_id) {
		return $this->db->getRow("album_user_album", "album_id, album_name, album_default", "user_id=$user_id AND album_default=2");
	}
 
}
