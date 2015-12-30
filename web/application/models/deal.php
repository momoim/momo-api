<?php defined('SYSPATH') or die('No direct access allowed.');

//地球半径，平均半径为6371km
define(EARTH_RADIUS, 6371);

/**
 * 短信model
 */
class Deal_Model extends Model 
{
	
	/**
	 * 实例
	 * @var Contact_Model
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return Contact_Model
	 */
	public static function &instance() {
		if (! isset(self::$instance)) {
			self::$instance = new Deal_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct();
		$mg_instance = new MongoClient(Kohana::config('uap.mongodb'));
        $mongo = $mg_instance->selectDB(MONGO_DB_FEED);
        $this->mongo_deal = $mongo->selectCollection ('deals');
	}
	
	
	
	/**
	 * 
	 * @param $title
	 * @param $description
	 * @param $price
	 * @param $sync
	 * @param $private
	 * @param $image
	 * @param $location
	 * @return unknown_type
	 */
	public function create($title,$description,$price,$private,$image,$location,$user_id,$sync) {
		$deal_id = api::uuid();
		$setters = array(
			'deal_id'=>$deal_id,
			'uid'=>$user_id,
			'longitude'=>$location['longitude']?$location['longitude']:0,
			'latitude'=>$location['latitude']?$location['latitude']:0,
			'price'=>$price,
			'private'=>$private,
			'created_at'=>time(),
			'modified_at'=>time()
		);
		if ($this->db->insertData('deals', $setters)) {
			$image  = $this->_warp_image( $image);
			if($this->_insert_mongo(array('deal_id'=>$deal_id,'uid'=>$user_id,'title'=>$title,'description'=>$description,'image'=>$image,'sync'=>$sync))) 
				return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @param $uptime
	 * @param $downtime
	 * @param $longitude
	 * @param $latitude
	 * @param $scope
	 * @param $private
	 * @param $trustworthiness
	 * @return unknown_type
	 */
	public function public_lists($uptime,$downtime,$longitude,$latitude,$scope=0,$private=0,$trustworthiness,$pagesize,$uid=0,$weight=0,$user_id=0) {
		$lists = array();
		$contact_relation = false;
		$list_sql = "SELECT `id`, `deal_id`, `uid`,`price`, `longitude`, `latitude`, `created_at` FROM deals WHERE 1=1 ";
		$count_sql = "SELECT COUNT(id) AS count FROM deals WHERE 1=1 ";
		if($uptime)
			$sql .= "AND `modified_at` < $uptime ";
		if($downtime)
			$sql .= "AND `modified_at` > $downtime "; 
		if($uid)
			$sql .= "AND `uid` = $uid "; 
		if($user_id) {
			$contact_uids = $this->_get_relation_contact_uids($user_id);
		}
		if($longitude && $latitude && $scope) {
			$squares = $this->_square_point($longitude, $latitude,$scope);	
			$sql .= "AND latitude>{$squares['right-bottom']['lat']} AND latitude<{$squares['left-top']['lat']} AND longitude>{$squares['left-top']['lng']} and longitude<{$squares['right-bottom']['lng']} ";
		}
		if(($uid == 0 && $user_id==0) || $uid != $user_id)
			$sql .= "AND private=$private ";
		$sql .= " AND status=0 ";
		$query = $this->db->query($count_sql.$sql);
		$count_result = $query->result_array(FALSE);
		$count = (int)$count_result[0]['count'];
		
		$sql .= " ORDER BY `created_at` DESC LIMIT $pagesize";
		$query = $this->db->query($list_sql.$sql);
		$result = $query->result_array(FALSE);
		
		//@todo for deal start
		$all_uids = array($user_id);
		foreach($result as $v){
		    $all_uids[] = $v['uid'];
		}
		$units = User_Model::instance()->get_unit_dict($all_uids);
		$same_units_uids = array();
		foreach ($units as $u){
		    if($u['unitid']==$units[$user_id]['unitid']){
		        $same_units_uids[] = $u['uid'];
		    }
		}
		//@todo for deal end
		
		if($query->count() > 0){
			foreach($result as $k => $v) {
				if($contact_uids)
					$contact_relation = in_array($v['uid'],$contact_uids);
				
				$lists[$k]['id'] = (int)$v['id'];
				$lists[$k]['title'] = '';
				$lists[$k]['weight'] = $contact_relation?1:0;
				$lists[$k]['relation'] = $contact_relation?'contact,':'';
				$lists[$k]['relation'] .= in_array($v['uid'], $same_units_uids)?'company,':'';
				$lists[$k]['relation'] = trim($lists[$k]['relation'],',');
				$lists[$k]['image'] = array();
				$lists[$k]['price'] = (float)$v['price'];
				$lists[$k]['created_at'] = (int)$v['created_at'];
				$lists[$k]['location'] = array('longitude'=>(float)$v['longitude'],'latitude'=>(float)$v['latitude']);
				$deal_detail = $this->_select_mongo($v['deal_id']);
				if($deal_detail) {
					$lists[$k]['sync'] = $deal_detail['sync']?$deal_detail['sync']:array();
					$lists[$k]['title'] = $deal_detail['title'];
					$lists[$k]['image'] = $this->_format_image($deal_detail['image'],'small');
				}
			}
		}
		return array('count'=>$count,'data'=>$lists);
	}
	
	/**
	 * 
	 * @param $uptime
	 * @param $downtime
	 * @param $trustworthiness
	 * @param $pagesize
	 * @return unknown_type
	 */
	public function private_lists($uptime,$downtime,$trustworthiness,$pagesize,$user_id) {
		$lists = array();
		$same_unit_uids = $relation_uids = array();
		$friend_uids = Friend_Model::instance()->get_user_link_cache ( $user_id );
		$same_unit = $this->_get_same_unit($user_id);
		if($same_unit) {
			foreach($same_unit as $v) {
				$same_unit_uids[] = $v['uid'];
			}
		}
		$relation_uids = array_merge($friend_uids,$same_unit_uids);
		$list_sql = "SELECT `id`, `deal_id`, `uid`,`price`, `longitude`, `latitude`, `created_at` FROM deals WHERE 1=1 ";
		$count_sql = "SELECT COUNT(id) AS count FROM deals WHERE 1=1 ";
		if($uptime)
			$sql .= "AND `modified_at` < $uptime ";
		if($downtime)
			$sql .= "AND `modified_at` > $downtime "; 
		$sql .= "AND uid in (".join(',',$relation_uids).") AND status=0 ";
		$query = $this->db->query($count_sql.$sql);
		$count_result = $query->result_array(FALSE);
		$count = (int)$count_result[0]['count'];
		
		$sql .= " ORDER BY `created_at` DESC LIMIT $pagesize";
		$query = $this->db->query($list_sql.$sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0){
			foreach($result as $k => $v) {
				$lists[$k]['id'] = (int)$v['id'];
				$lists[$k]['title'] = '';
				$lists[$k]['image'] = array();
				$lists[$k]['weight'] = '1';
				$lists[$k]['relation'] = in_array($v['uid'],$same_unit_uids)?'company':'contact';
				$lists[$k]['price'] = (float)$v['price'];
				$lists[$k]['created_at'] = (int)$v['created_at'];
				$lists[$k]['location'] = array('longitude'=>(float)$v['longitude'],'latitude'=>(float)$v['latitude']);
				$deal_detail = $this->_select_mongo($v['deal_id']);
				if($deal_detail) {
					$lists[$k]['sync'] = $deal_detail['sync']?$deal_detail['sync']:array();
					$lists[$k]['title'] = $deal_detail['title'];
					$lists[$k]['image'] = $this->_format_image($deal_detail['image'],'small');
				}
			}
		}
		return array('count'=>$count,'data'=>$lists);
	}
	
	/**
	 * 
	 * @param $pagesize
	 * @param $user_id
	 * @return array
	 */
	public function personal_lists($user_id,$friend_id=0,$pagesize=10) {
		$lists = array();
		$sql = "SELECT `id`, `deal_id`, `uid`,`price`,`status`, `longitude`, `latitude`, `created_at` FROM deals WHERE uid='{$user_id}' ";
		if($friend_id) {
			if($user_id!=$friend_id) {
				$friend_uids = Friend_Model::instance()->get_user_link_cache ( $user_id );
				if(!in_array($friend_id,$friend_uids))
					$sql .= "AND private=0 ";
			}
		} else {
			$sql .= "AND private=0 ";
		}
		$sql .= "ORDER BY `created_at` DESC LIMIT $pagesize";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0){
			foreach($result as $k => $v) {
				$lists[$k]['id'] = (int)$v['id'];
				$lists[$k]['title'] = '';
				$lists[$k]['status'] = (int)$v['status'];
				$lists[$k]['image'] = array();
				$lists[$k]['price'] = (float)$v['price'];
				$lists[$k]['created_at'] = (int)$v['created_at'];
				$lists[$k]['location'] = array('longitude'=>(float)$v['longitude'],'latitude'=>(float)$v['latitude']);
				$deal_detail = $this->_select_mongo($v['deal_id']);
				if($deal_detail) {
					$lists[$k]['sync'] = $deal_detail['sync']?$deal_detail['sync']:array();
					$lists[$k]['title'] = $deal_detail['title'];
					$lists[$k]['image'] = $this->_format_image($deal_detail['image'],'small');
				}
			}
		}
		return $lists;
	}
	
	/**
	 * 
	 * @param $deal_id
	 * @return array
	 */
	public function item($deal_id,$user_id=0) {
		$result = $this->get($deal_id);
		if($result) {
			$deal_user_info = sns::getuser($result['uid']);
			$return = array(
				'deal_id'=>(int)$result['id'],
				'user'=>array('id'=>(int)$result['uid'],'name'=>$deal_user_info['realname'],'mobile'=>$deal_user_info['mobile'],'avatar'=>sns::getavatar ($result['uid'],'small')),
				'status'=>(int)$result['status'],
				'private'=>(int)$result['private'],
				'price'=>(float)$result['price'],
				'location'=>array('longitude'=>(float)$result['longitude'],'latitude'=>(float)$result['latitude']),
				'created_at'=>(int)$result['created_at']
			);
			if($user_id) {
				$name = $this->_get_relation_contacts_name($user_id,$result['uid']);
				$return['weight'] = $name?1:0;
				$return['relation']['contact'] = array('name'=>$name);
				/*** @todo for deal start ***/
				$units = User_Model::instance()->get_unit_dict(array($user_id, $result['uid']));
				if($units[$result['uid']] && $units[$user_id]['unitid']==$units[$result['uid']]['unitid']){
				    $return['relation']['company'] = array('name'=>$units[$result['uid']]['unitname'],'username'=>$units[$result['uid']]['username']);
				}
				/*** @todo for deal end ***/
			}
			$deal_detail = $this->_select_mongo($result['deal_id']);
			if($deal_detail) {
				$return['sync'] = $deal_detail['sync']?$deal_detail['sync']:array();
				$return['title'] = $deal_detail['title'];
				$return['description'] = $deal_detail['description'];
				$return['image'] = $this->_format_image( $deal_detail['image']);
			}
		}
		return $return;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function count() {
		
	}

	/**
	 * 
	 * @param $deal_id
	 * @return array
	 */
	public function get($deal_id) {
		$result = array();
		$query = $this->db->fetchData('deals', '*',array('id'=>$deal_id));
		$result = $query->result_array(FALSE);
		if($result)
			return $result[0];
	}
	
	/**
	 * 
	 * @param $deal_id
	 * @return array
	 */
	public function update($id,$user_id,$deal_id,$letters) {
		foreach($letters as $k => $v) {
			if(in_array($k,array('status','remark','price')) && $v)
				$sql_letters[$k] = $v;
			if(empty($v))
				unset($letters[$k]);
		}
		if($sql_letters)
			$this->db->updateData('deals', $sql_letters, "id = '{$id}' AND uid='{$user_id}'");
		if($deal_id && $letters) {
			$letters['image']  = $this->_warp_image( $letters['image']);
			if(!$letters['image'])
				unset($letters['image']);
			
			$this->_update_mongo($deal_id,$user_id,$letters);
		}
		return true;
	}
	
	/**
	 * 
	 * @param $user_id
	 * @return array
	 */
	public function stat($user_id) {
		$sql = "SELECT COUNT( d.id ) AS total, COUNT( dd.id ) AS success FROM deals d LEFT JOIN deals dd ON dd.id = d.id AND dd.status =1 WHERE d.uid =".$user_id;
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return $result[0];
	}
	
	/**
	 * 
	 * @param $user_id
	 * @return unknown_type
	 */
	private function _get_relation_contact_uids ($user_id) {
		$contact_uids = array();
		$contact_uids = Friend_Model::instance()->get_user_link_cache ( $user_id );
		return $contact_uids;
	}
	
	private function _get_same_unit($user_id) {
		$sql = "SELECT mu.* FROM  `members_units` m LEFT JOIN members_units mu ON m.unitid = mu.unitid WHERE m.uid =".$user_id;
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result)
			return $result;
		return array();
	}
	
	/**
	 * 
	 * @param $user_id
	 * @param $contact_uid
	 * @return unknown_type
	 */
	private function _get_relation_contacts_name($user_id,$contact_uid) {
		$name = '';
		$contacts = $this->_get_relation_contact_lsits($user_id);
		if(count($contacts)>0 && is_array($contacts)) {
			foreach($contacts as $contact) {
				if($contact['user_id'] == $contact_uid)
					$name = $contact['name'];
			}
		}
		return $name;
	}
	
	/**
	 * 
	 * @param $user_id
	 * @return unknown_type
	 */
	private function _get_relation_contact_lsits($user_id) {
		$res = array();
		//$key = 'momo_contacts_relation_'.$user_id;
		//$res = Cache::instance()->get($key);
		//if(!$res) {
			$contact_lists = Contact_Model::instance()->get($user_id,null,'',1);
			if(count($contact_lists)>0 && is_array($contact_lists)) {
				foreach($contact_lists as $contact) {
					if(count($contact['tels'])>0 && is_array($contact['tels'])) {
						foreach($contact['tels'] as $tel) {
							if($tel['type'] == 'cell')
								$relation[]=array('name'=>$contact['formatted_name'],'mobile'=>$tel['value']);
						}
					}
				}
				if($relation) {
					$res = User_Model::instance()->create_at($relation,$user_id,0);
					//if($res)
						//Cache::instance()->set($key, $res, NULL, 86400);
				}
			}
		//}
		return $res;
	}
	
	
	/**
	 * 
	 * @param $lng
	 * @param $lat
	 * @param $distance
	 * @return array
	 */
	private function _square_point($lng, $lat,$distance = 0.5){
	    $dlng =  2 * asin(sin($distance / (2 * EARTH_RADIUS)) / cos(deg2rad($lat)));
	    $dlng = rad2deg($dlng);
	    $dlat = $distance/EARTH_RADIUS;
	    $dlat = rad2deg($dlat);
	    return array(
		'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
		'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
		'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
		'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
		);
	 }
 
	/**
	 * 
	 * 将短信内容存储到mongo
	 * @param array $sms
	 */
	private function _insert_mongo($deal){
        $res=$this->mongo_deal->insert($deal,array('safe'=>TRUE));
        if($res['ok']) 
        	return true;
        return false;
    }
    
    /**
     * 
     * @param $image
     * @return unknown_type
     */
    private function _format_image($image = array(),$size='big') {
    	$return = array();
    	if(is_array($image) && count($image)>0) {
    		$return['url'] = $image[0]['url']?$image[0]['url']:'';
    		if($size=='small') {
    			$return['url'] = str_replace('_780.jpg','_130.jpg',$return['url']);
    		}
    		
    		$return['width'] = $image[0]['width']?(int)$image[0]['width']:0;
    		$return['height'] = $image[0]['height']?(int)$image[0]['height']:0;
    	}
    	return $return;
    }
    
    /**
     * 
     * @param $image
     * @return array
     */
    public function _warp_image($image=array()) {
    	$return = array();
    	if(is_array($image) && count($image)>0) {
    		foreach($image as $k => $v) {
    			if($v['id']) {
    				$img_info = Photo_Controller::getinfo ( $v ['id']);
	    			$return[$k]['url'] = $img_info[0]['src'];
	    			$return[$k]['width'] = $img_info[0]['width'];
	    			$return[$k]['height'] = $img_info[0]['height'];
    			}
    		}
    	}
    	return $return;
    }
	
	/**
	 * 
	 * 从mongo中获取短信内容
	 * @param array $sms
	 */
	private function _select_mongo($deal_id){
       $cols=$this->mongo_deal->findOne(array('deal_id'=>$deal_id));
       return $cols;
    }
	
	/**
	 * 
	 * 从mongo中删除短信内容
	 * @param array $sms
	 */
	private function _delete_mongo($deal_id){
       $cols=$this->mongo_deal->remove(array('deal_id'=>$deal_id));
       return $cols;
    }
    
    /**
     * 
     * @param $deal_id
     * @param $data
     * @return array
     */
    private function _update_mongo($deal_id,$user_id,$data){
		$newdata = array ('$set' => $data);
		return $this->mongo_deal->update ( array ("deal_id" => $deal_id,"uid" => $user_id ), $newdata);
    }
	
}