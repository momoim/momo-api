<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 活动控制器
 */
class Event_Controller extends Controller
{
    /**
     * 活动模型
     * @var Event_Model
     */
    protected $model;
    /**
     * 用户ID
     * @var int
     */
    /**
     * 构造函数
     */
    public function __construct ()
    {
        parent::__construct();
        $this->user_id = $this->getUid();
        $this->model = Event_Model::instance();
    }

    /**
     * 获取活动列表
     */
    public function index ()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
		$filter = $this->input->get('filter', 'all');
		$filter = explode(',',$filter);
		$end = (int)($this->input->get('end', 1));
		$city = (int)($this->input->get('city', 0));
		$sort = $this->input->get('sort','time');
		$pagesize = (int)($this->input->get('pagesize', 10));
		$page = (int)($this->input->get('page', 1));
		$start = abs($pagesize * ($page - 1));
		$type_array = Kohana::config('event.request_type');
		$sort_array = Kohana::config('event.sort');
		$result = array('total'=>0, 'data'=>array());
		$apply_type = array();
		$private = 1;
		if(!array_key_exists($sort, $sort_array)) {
			$this->send_response(400, NULL, '400506:活动排序类型非法');
		}
		$sort = $sort_array[$sort];
		if(in_array('all',$filter))
			$private = 0;
		if($this->user_id>0) {
			foreach($filter as $type) {
				if(!in_array($type, $type_array)) {
		        	$this->send_response(400, NULL, '400504:请求的活动类型非法');
				}
				switch ($type) {
					case 'all' :
						$apply_type = '';
						break;
					case 'me_launch' :
						//我发起的活动
						$apply_type[] = -1;
						break;
					case 'me_joined' :
						//我参加的活动
						$apply_type[] = Kohana::config('event.apply_type.joined');
						break;
					case 'me_interested' :
						//我感兴趣的活动
						$apply_type[] = Kohana::config('event.apply_type.interested');
						break;
					case 'me_not_join' :
					case 'me_refuse' :
						//我不参加的活动
						$apply_type[] = Kohana::config('event.apply_type.refused');
						break;
					default :
						break;
				}
			}
		}
		$count = $this->model->getEventNum($this->user_id, $apply_type, $end,$city,$private);
		$data = $this->model->getEventList($this->user_id, $apply_type, $end,$city,$start, $pagesize,$sort,$private);
		$event_list = $this->_arrange_event_list($data);
        $result['total'] = $count;
        $result['data'] = $event_list;
        $this->send_response(200, $result);
    }
    
    /**
     * 获取活动详细信息
     */
    public function show ($id)
    {
	    if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
    	if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
		$list_user = (int)($this->input->get('list_user', 0));
		$data = $this->model->getEvent($id,$this->user_id);
		if($data) {
			$event  = $this->_arrange_event_item($data,true,$list_user);
			$this->send_response(200, $event);
		}
        $this->send_response(400, NULL, '400502:活动不存在');
    }

    /**
     * 创建活动
     */
    public function create ()
    {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400505:活动信息非法');
        }
        $post = new Validation($data);
		$post->add_rules('title', 'required', 'length[1, 30]');
		$post->add_rules('content', 'required', 'length[0, 10000]');
		$post->add_rules('start_time', 'required');
		//$post->add_rules('end_time', 'required');
		//$post->add_rules('assemble_location', 'required');
		$post->add_rules('event_location', 'required');
		$post->add_rules('type', 'required', 'numeric', array($this, '_check_type_validation'));
		$post->add_callbacks(TRUE, array($this, '_check_time_validation'));
		
		if ($post->validate()) {
			$form = $post->as_array();
			$event = array();
			$event['organizer'] = $this->user_id;
			$event['type'] = $form['type'];
			$event['title'] = $form['title'];
			$event['summary'] = empty($form['summary'])?'':$form['summary'];
			$event['deadline'] = empty($form['deadline'])?0:$form['deadline'];
			$event['apply_desc'] = empty($form['apply_desc'])?'':$form['apply_desc'];
			$event['content'] = $form['content'];
			$event['start_time'] = $form['start_time'];
			if($form['end_time'])
				$event['end_time'] = $form['end_time'];
			$event['city'] = $form['city']?$form['city']:3501;
			$event['private'] = (int)$form['private'];
			$event['fee'] = $form['fee']?$form['fee']:'';
			if($form['apply_doc']) {
				if(!$this->_check_apply_doc($form['apply_doc']))
					$this->send_response(400, NULL, '400413:apply_doc信息非法');
				$event['apply_doc'] = serialize($form['apply_doc']);
			}
				
			$event['status'] = 1;
			$event['create_time'] = time();
			$event['event_location'] = serialize($form['event_location']);
			if($form['assemble_location'])
				$event['assemble_location'] = serialize($form['assemble_location']);
			$event_id = $this->model->add($event);
			if($event_id) {
				if($form['image'])
					$this->_create_event_image($event_id,$form['image']);
				if($form['apply_doc']) 
					$this->_create_event_apply_doc($event_id,$form['apply_doc']);
				//创建群组
				$group_id = $this->_create_group($event);
				if($group_id) {
					$this->_add_group_user($group_id, $this->user_id,Kohana::config('group.grade.master'));
					$this->model->update($event_id,array('gid'=>$group_id));
				}
					
				$user = sns::getuser($this->user_id);
				$eventUser = array(
					'eid' => $event_id,
					'pid' => 0,
					'uid' => $this->user_id,
					'name' => $user['realname'],
					'mobile' => $user['mobile'],
					'apply_type' => Kohana::config('event.apply_type.joined'),
					'apply_time' => time(),
					'grade' => Kohana::config('event.grade.creator')
				);
				$this->model->applyEvent($eventUser);
				
				$opt = array('event'=>array('id'=>$event_id,'name'=>$event['title'],'cover'=>''),'no_sign'=>1);
				$event_url = MO_EVENT.'event/show/'.$event_id;
				$short_title = str::strLen($event['title'])>10?str::cnSubstr($event['title'],0,10).'..':$event['title'];
				$event_short_url = url::getShortUrl($event_url);
				$content = '【'.$event_short_url.' 】由'.$user['realname'].'发起的活动,快来参加吧';
				$this->send_event_mq(Kohana::config('uap.xiaomo'),$this->user_id,$content,$opt);
						
				$this->send_response(200, array('id' => floatval($event_id),'gid'=>(int)$group_id));
			}
			$this->send_response(400, NULL, '400413:活动发布失败');
		}
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'type':
					$this->send_response(400, NULL, '400405:活动类型为空');
					break;
				case 'start_time':
					$this->send_response(400, NULL, '400406:活动开始时间为空');
					break;
				case 'end_time':
					$this->send_response(400, NULL, '400407:活动结束时间为空');
					break;
				case 'assemble_location':
					$this->send_response(400, NULL, '400408:集合地点为空');
					break;
				case 'event_location':
					$this->send_response(400, NULL, '400409:活动地点为空');
					break;
				case 'end_before_start':
					$this->send_response(400, NULL, '400410:活动结束时间必须大于开始时间');
					break;
				case 'content':
					$this->send_response(400, NULL, '400411:活动内容为空');
					break;
				default:
					$this->send_response(400, NULL, '400412:活动信息非法');
					break;
			}
		}
		$this->send_response ( 400, NULL, '400412:活动信息非法');
    }
    

    /**
     * 
     * 添加活动照片
     * @param $id
     */
    public function add_image ($id=NULL)
    {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400604:照片列表为空');
        }
    	if(empty($id)) {
        	$this->send_response(400, NULL, '400601:活动ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($this->model->getApplyType(array('eid'=>$id,'uid'=>$this->user_id)) != Kohana::config('event.apply_type.joined')) {
        	$this->send_response(400, NULL, '400603:没有权限添加');
        }
        $post = new Validation($data);
		$post->add_rules('image', 'required');
		$post->add_callbacks(TRUE, array($this, '_check_image_validation'));
		
		if ($post->validate()) {
			$form = $post->as_array();
			$num = $this->_create_event_image($id,$form['image']);
			$this->send_response(200, array('num' => $num));
		}
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'image':
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
				case 'image_id_invalid':
					$this->send_response(400, NULL, '400605:照片id为空');
					break;
				default:
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
			}
		}
		$this->send_response ( 400, NULL, '400606:活动信息非法');
    }
    

    /**
     * 
     * 添加活动照片
     * @param $id
     */
    public function add_cover_image ($id=NULL)
    {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400604:照片列表为空');
        }
    	if(empty($id)) {
        	$this->send_response(400, NULL, '400601:活动ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($this->model->getApplyType(array('eid'=>$id,'uid'=>$this->user_id)) != Kohana::config('event.apply_type.joined')) {
        	$this->send_response(400, NULL, '400603:没有权限添加');
        }
        $post = new Validation($data);
		$post->add_rules('image', 'required');
		$post->add_callbacks(TRUE, array($this, '_check_image_validation'));
		
		if ($post->validate()) {
			$form = $post->as_array();
			Event_Image_Model::instance()->deleteByEvent($id,1);
			$num = $this->_create_event_image($id,$form['image'],1);
			$this->send_response(200, array('num' => $num));
		}
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'image':
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
				case 'image_id_invalid':
					$this->send_response(400, NULL, '400605:照片id为空');
					break;
				default:
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
			}
		}
		$this->send_response ( 400, NULL, '400606:活动信息非法');
    }
    /**
     * 
     * 修改活动照片
     * @param $id
     */
    public function update_cover_image ($id=NULL)
    {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400604:照片列表为空');
        }
    	if(empty($id)) {
        	$this->send_response(400, NULL, '400601:活动ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($this->model->getApplyType(array('eid'=>$id,'uid'=>$this->user_id)) != Kohana::config('event.apply_type.joined')) {
        	$this->send_response(400, NULL, '400603:没有权限添加');
        }
        $post = new Validation($data);
		$post->add_rules('image', 'required');
		$post->add_callbacks(TRUE, array($this, '_check_image_validation'));
		
		if ($post->validate()) {
			$form = $post->as_array();
			Event_Image_Model::instance()->deleteByEvent($id,1);
			$num = $this->_create_event_image($id,$form['image'],1);
			$this->send_response(200, array('num' => $num));
		}
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'image':
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
				case 'image_id_invalid':
					$this->send_response(400, NULL, '400605:照片id为空');
					break;
				default:
					$this->send_response(400, NULL, '400604:照片列表为空');
					break;
			}
		}
		$this->send_response ( 400, NULL, '400606:活动信息非法');
    }
    
    /**
     * 
     * 修改活动信息
     * @param $id
     */
    public function update($id=NULL) {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400412:活动信息非法');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400502:活动不存在');
        }
        $post = new Validation($data);
        if(isset($data['title']))
			$post->add_rules('title', 'required', 'length[1, 30]');
        if(isset($data['content']))
			$post->add_rules('content', 'required', 'length[0, 10000]');
        if(isset($data['start_time']))
			$post->add_rules('start_time', 'required');
        //if(isset($data['cover_image']))
		//	$post->add_callbacks(TRUE, array($this, '_check_cover_image_validation'));
        if(isset($data['event_location']))
			$post->add_rules('event_location', 'required');
        if(isset($data['type']))
			$post->add_rules('type', 'required', 'numeric', array($this, '_check_type_validation'));
        if(isset($data['start_time']) && isset($data['end_time']))
			$post->add_callbacks(TRUE, array($this, '_check_time_validation'));
        if(isset($data['status']))
			$post->add_callbacks(TRUE, array($this, '_check_status_validation'));
		$send_notice = $data['send_notice']?(int)$data['send_notice']:0;
			
		if ($post->validate()) {
			$form = $post->as_array();
			$event = array();
			if(!empty($form['type']))
				$event['type'] = $form['type'];
			if(!empty($form['title']))
				$event['title'] = $form['title'];
			if(!empty($form['summary']))
				$event['summary'] = $form['summary'];
			if(!empty($form['content']))
				$event['content'] = $form['content'];
			if(!empty($form['start_time']))
				$event['start_time'] = $form['start_time'];
			if(!empty($form['end_time']))
				$event['end_time'] = $form['end_time'];
			if(!empty($form['city']))
				$event['city'] = $form['city'];
			if(!empty($form['deadline']))
				$event['deadline'] = $form['deadline'];
			if(!empty($form['fee']))
				$event['fee'] = $form['fee'];
			if(!empty($form['status']))
				$event['status'] = (int)$form['status'];
			if(!empty($form['assemble_location']))
				$event['assemble_location'] = serialize($form['assemble_location']);
			if(!empty($form['event_location']))
				$event['event_location'] = serialize($form['event_location']);
			$event['update_time'] = time();
			$event['apply_desc'] = $form['apply_desc'];
			if($form['apply_doc']) {
				if(!$this->_check_apply_doc($form['apply_doc']))
					$this->send_response(400, NULL, '400413:apply_doc信息非法');
			}
			$event['apply_doc'] = $form['apply_doc']?serialize($form['apply_doc']):'';
			$event['private'] = $form['private']?(int)$form['private']:0;
			if($event['start_time']) {
				$this->_check_event_status($event['start_time'],$event['end_time'],$event['status'],$id,$event['deadline']);
			}
			if($this->model->update($id,$event)) {
				if($send_notice) {
					$content = '活动"'.$event_info['title'].'"信息更新,详情:';
					$this->event_user_notify($id,$event_info['title'],$event_info['organizer'],$content);
				}
				$this->_update_group($id,$event);
				$this->send_response(200);
			}
			$this->send_response ( 400, NULL, '400413:活动信息更新失败');
		}
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'type':
					$this->send_response(400, NULL, '400405:活动类型为空');
					break;
				case 'start_time':
					$this->send_response(400, NULL, '400406:活动开始时间为空');
					break;
				case 'end_time':
					$this->send_response(400, NULL, '400407:活动结束时间为空');
					break;
				case 'assemble_location':
					$this->send_response(400, NULL, '400408:集合地点为空');
					break;
				case 'event_location':
					$this->send_response(400, NULL, '400409:活动地点为空');
					break;
				case 'end_before_start':
					$this->send_response(400, NULL, '400410:活动结束时间必须大于开始时间');
					break;
				case 'status_invalid':
					$this->send_response(400, NULL, '400413:活动状态不合法');
					break;
				case 'content':
					$this->send_response(400, NULL, '400411:活动内容为空');
					break;
				default:
					$this->send_response(400, NULL, '400412:活动信息非法');
					break;
			}
		}
		$this->send_response ( 400, NULL, '400412:活动信息非法');
    }
    
    /**
     * 
     * @return unknown_type
     */
    public function event_user_notify($eid,$title,$organizer,$content) {
    	$event_user = $this->model->getEventUser($eid,Kohana::config('event.apply_type.joined'));
    	$cover = Event_Image_Model::instance()->getCover($eid);
		$cover = $cover?$cover:'';
		$opt = array('event'=>array('id'=>$eid,'name'=>$title,'cover'=>$cover),'no_sign'=>1);
    	if(is_array($event_user) && count($event_user) > 0) {
    		foreach($event_user as $user) {
    			$device_id = md5 ( $user['mobile'] . '_'.'0' );
				$token = User_Model::instance()->request_access_token ( 0, $user['uid'], $device_id,Kohana::config('event.appid'));
				$event_url = MO_EVENT.'event/show/'.$eid.'?token='.$token['oauth_token'];
				$event_short_url = url::getShortUrl($event_url);
				$ev_content = $content.$event_short_url;
				$this->send_event_mq($organizer,$user['uid'],$ev_content,$opt);
    		}
    	}
    }
    
    /**
     * 
     * 活动报名
     * @param $id
     */
    public function user_apply($id = NULL) {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400412:活动信息非法');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400506:活动不存在');
        }
        if($post['apply_type']==Kohana::config('event.apply_type.joined') && empty($data['user'])) {
        	$this->send_response(400, NULL, '400508:活动报名信息为空');
        }
		if($event_info['organizer']==$this->user_id && $post['apply_type']==Kohana::config('event.apply_type.refused')) {
			$this->send_response(400, NULL, '400507:活动创建者不能不参加活动');
		}
        $update_apply_type = false;
        $post = new Validation($data);
		$post->add_rules('apply_type', 'required', 'numeric', array($this, '_check_type_validation'));
		if ($post->validate()) {
			$form = $post->as_array();
			$user_info = sns::getuser($this->user_id);
			$apply_info = $this->model->getApplyInfo(array('eid'=>$id,'pid'=>0,'uid'=>$this->user_id));
			if($apply_info) {
				$update_apply_type = true;
			} else {
				if(time() > $event_info['deadline'])
       				$this->send_response(400, NULL, '400509:活动报名已截止');
			}
			$eventUser = array(
				'eid' => $id,
				'pid' => 0,
				'uid' => $this->user_id,
				'name' => $user_info['realname'],
				'mobile' => $user_info['mobile'],
				'apply_type' => $post['apply_type'],
				'apply_time' => time(),
				'grade' => Kohana::config('event.grade.normal')
			);
			if(!empty($post['apply_doc'])) {
				if(!$this->_check_user_apply_doc_valid($id,$post['apply_doc']))
					$this->send_response(400, NULL, '400531:报名信息非法');
				$this->model->addUserApplyDoc($id,$this->user_id,0,$user_info['realname'],$post['apply_doc']);
			}
			$this->model->applyEvent($eventUser,$update_apply_type);
			
			//家属报名
			$this->_dependent_apply_event($post['dependent'],$id,$this->user_id,$post['apply_type']);				
			if(($post['apply_type'] == Kohana::config('event.apply_type.joined') || $post['apply_type'] == Kohana::config('event.apply_type.interested'))) { 
				if($post['apply_type'] == Kohana::config('event.apply_type.joined'))
					$content = $user_info['realname'].'参加了活动"'.$event_info['title'].'"';
				else
					$content = $user_info['realname'].'对活动"'.$event_info['title'].'"表示感兴趣';
				$opt = array('event'=>array('id'=>$id,'name'=>$event_info['title'],'cover'=>''),'no_sign'=>1);
				$this->send_event_mq(Kohana::config('uap.xiaomo'),$event_info['organizer'],$content,$opt);
				$this->_add_group_user($event_info['gid'],$this->user_id,Kohana::config('group.grade.normal'));
			}elseif($post['apply_type'] == Kohana::config('event.apply_type.refused')) {
				$this->_del_group_user($event_info['gid'], $this->user_id);
			}
			$this->send_response(200);
		}
		
		$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'apply_type':
					$this->send_response(400, NULL, '400405:活动类型为空');
					break;
				case 'user_name_empty':
					$this->send_response(400, NULL, '400502:名字为空');
					break;
				case 'user_mobile_empty':
					$this->send_response(400, NULL, '400503:手机号为空');
					break;
				case 'user_mobile_format':
					$this->send_response(400, NULL, '400504:手机号格式不正确');
					break;
			}
    	}
    }
    
    /**
     * 
     * 删除报名活动的家属
     */
    public function delete_dependent($id = NULL) {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400506:活动不存在');
        }
        $this->model->deleteEventUser(array('pid'=>$this->user_id,'uid'=>0,'eid'=>$id));
        $this->send_response(200);
    }
    
    /**
     * 
     * 用户参与列表
     * @param $id
     */
    public function user_list($id = NULL) {
    	if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400506:活动不存在');
        }
        $type = $this->input->get('type', 0);
        if($type==0)
        	$type = '1,2,3,4';
        $type_exp = explode(',',$type);
    	$type_array = Kohana::config('event.apply_type');
    	$user_list = array();
        foreach($type_exp as $apply_type) {
        	if(!in_array($apply_type, $type_array)) {
	        	$this->send_response(400, NULL, '400504:活动类型非法');
			}
			$type_array_flip = array_flip($type_array);
			$user = $this->model->getEventUser($id,$apply_type);
			$lists = $this->_arrange_user_list($user,$apply_type,$event_info);
			$user_list[$type_array_flip[$apply_type]]['number'] = count($lists);
			$user_list[$type_array_flip[$apply_type]]['user'] = $lists;
        }
        $this->send_response(200,$user_list);
    }
    
    /**
     * 
     * 修改成员状态
     * @param $id
     */
    public function user_update($id = NULL) {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400412:活动信息非法');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400502:活动不存在');
        }
        if($this->user_id != $event_info['organizer']) {
        	$this->send_response(400, NULL, '400512:限活动创建者使用');
        }
    	$type_array = Kohana::config('event.apply_type');
    	if(!$data['user'])
    		$this->send_response(400, NULL, '400415:用户数据非法');
        foreach($data['user'] as $user) {
        	if(!in_array($user['apply_type'], $type_array)) {
	        	$this->send_response(400, NULL, '400504:活动类型非法');
			}
			$a = array('eid'=>$id,'uid'=>$user['id']);
			$apply_doc = $user['apply_doc']?$user['apply_doc']:array();
			$dependent = $user['dependent']?$user['dependent']:array();
			
			$apply_type = $this->model->getApplyType(array('eid'=>$id,'uid'=>$user['id']));
			if($event_info['organizer']==$user['id'] && $user['apply_type']==Kohana::config('event.apply_type.refused')) {
				$this->send_response(400, NULL, '400507:活动创建者不能改状态为不参加');
			}
			$user_update = array('apply_type'=>$user['apply_type']);
			if($apply_doc)
				$user_update['apply_doc'] = serialize($apply_doc);
        
			if(!empty($apply_doc)) {
				if(!$this->_check_user_apply_doc_valid($id,$apply_doc))
					$this->send_response(400, NULL, '400531:报名信息非法');
				$this->model->addUserApplyDoc($id,$user['id'],0,'',$apply_doc);
			}	
				
			$this->model->updateApplyEvent($id,$user['id'],$user_update);
			
			$this->_dependent_apply_event($dependent,$id,$user['id'],$user['apply_type']);
			
        	if(Tab_Model::instance()->get($user['id'],Kohana::config('group.type.event'),$id)) {
				if($user['apply_type'] == Kohana::config('event.apply_type.refused')) 
					$this->_del_group_user($id, $user['id']);
			} else {
				$this->_add_group_user($id, $user['id'],Kohana::config('group.grade.normal'));
			}
        }
        $this->send_response(200);
    }
    
    /**
     * 
     * 查询用户报名状态
     * @param $id
     */
    public function user_status($id=NULL) {
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $uid = $data['uid']?(int)$data['uid']:0;        
        if(empty($uid)) {
        	$this->send_response(400, NULL, '400511:用户ID为空');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400506:活动不存在');
        }
    	$apply_type = $this->model->getApplyType(array('eid'=>$id,'uid'=>$uid));
    	
    	$this->send_response(200, array('status'=>$this->_check_apply_type($apply_type,$id,$uid)));
    }
    
    /**
     * 
     * 活动邀请
     */
    public function invite($id=NULL) {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400501:活动ID为空');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400412:活动信息非法');
        }
        $event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400506:活动不存在');
        }
        if(empty($data['user'])) {
        	$this->send_response(400, NULL, '400508:活动报名信息为空');
        }
        $return = array();
        $update_apply_type = false;
        $post = new Validation($data);
        $post->add_rules('user', 'required');
		$post->add_callbacks(TRUE, array($this, '_check_user_validation'));
		if ($post->validate()) {
			$form = $post->as_array();
			if(count($form['user']>0)) {
				$user_array = $this->_get_event_uid($form['user']);
				$i=0;
				$cover = Event_Image_Model::instance()->getCover($id);
				$cover = $cover?$cover:'';
				$opt = array('event'=>array('id'=>$id,'name'=>$event_info['title'],'cover'=>$cover),'no_sign'=>1);
				foreach($user_array as $mobile => $user) {
					$i++;
					if($this->user_id == $user['user_id'] || empty($user['user_id']))
						continue;
					$apply_type = $this->model->getApplyType(array('eid'=>$id,'uid'=>$user['user_id']));
					if(!$apply_type || $apply_type == Kohana::config('event.apply_type.refused')) {
						if($apply_type == Kohana::config('event.apply_type.refused'))
							$update_apply_type = true;
						$eventUser = array(
							'eid' => $id,
							'pid' => 0,
							'uid' => $user['user_id'],
							'name' => $user['name'],
							'mobile' => $mobile,
							'apply_type' => Kohana::config('event.apply_type.unconfirmed'),
							'apply_time' => time(),
							'invite_by' => $this->user_id,
							'grade' => Kohana::config('event.grade.normal')
						);
						$this->model->applyEvent($eventUser,$update_apply_type);
					}
					if(!in_array($apply_type,array(Kohana::config('event.apply_type.joined'),Kohana::config('event.apply_type.interested')))) {
						$return[] = array('uid'=>$user['user_id'],'name'=>$user['name'],'mobile'=>$mobile,'avatar'=>sns::getAvatar($user['user_id']));
						$device_id = md5 ( $mobile . '_'.'0' );
						$token = User_Model::instance()->request_access_token ( 0, $user['user_id'], $device_id,Kohana::config('event.appid'));
						$event_url = MO_EVENT.'event/show/'.$id.'?token='.$token['oauth_token'];
						$event_short_url = url::getShortUrl($event_url);
						$content = '邀请你参加活动:'.$event_short_url;
						$this->send_event_mq($this->user_id,$user['user_id'],$content,$opt);
					} else {
						$this->send_response(400, NULL, '400511:该用户已报名');
					}
				}
				$this->send_response(200,array('num'=>$i,'user'=>$return));
			}
		}
    	$errors = $post->errors();
    	foreach($errors as $key=>$value) {
			switch($key) {
				case 'user_name_empty':
					$this->send_response(400, NULL, '400502:名字为空');
					break;
				case 'user_mobile_empty':
					$this->send_response(400, NULL, '400503:手机号为空');
					break;
				case 'user_mobile_format':
					$this->send_response(400, NULL, '400504:手机号格式不正确');
					break;
			}
    	}
    }
    
	/**
     * 
     * 发通知
     * @param int $sender_uid
     * @param int $receiver_uid
     * @param int $sms_count
     */
    public function send_event_mq($sender_uid,$receiver_uid,$content,$opt=array()) {
    	$sender=array('id'=>$sender_uid,'name'=>sns::getrealname($sender_uid),'avatar'=>sns::getavatar($sender_uid));
    	$receiver=array(array('id'=>$receiver_uid,'name'=>sns::getrealname($receiver_uid),'avatar'=>sns::getavatar($receiver_uid)));
    	$content= array('text'=>$content);
		$sms=array(
			'kind'=>'sms',
 			'data'=>array(
    					'id'=>api::uuid(),
    					'sender'=>$sender,
    					'msgtype'=>1,
    					'receiver'=>$receiver,
    					'timestamp'=>ceil(microtime(TRUE)*1000),
						'opt'=>$opt,
    					'content'=>$content,
    					'client_id'=>0,
    				)
    			);
		mq::send(json_encode($sms),$receiver_uid, 'momo_event', Kohana::config('event.appid'), $this->getUid());
    }
    
    /**
     * 
     * 获取城市列表
     */
    public function city() {
    	$city = $this->model->getCity();
    	$this->send_response(200,$city);
    }
    
    /**
     * 
     * @return unknown_type
     */
    public function add_event_apply_doc($id=NULL) {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $doc = $data['doc']?$data['doc']:array();
        if(count($doc) > 0) {
        	foreach($doc as $v) {
        		if(empty($v['title']))
        			$this->send_response(400, NULL, '400606:标题为空');
        	}
        } else {
        	$this->send_response(400, NULL, '400609:doc为空');
        }
    	$event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($event_info['organizer'] != $this->user_id) {
        	$this->send_response(400, NULL, '400603:没有权限');
        }
        foreach($doc as $v) {
        	$did[] = $this->model->addEventApplyDoc($id,$v['title']);
        }
        if(count($did) > 0) {
        	$this->send_response(200,array('did'=>$did));
        }
        $this->send_response(400,NULL, '400607:添加失败');
    }
    
    /**
     * 
     * @return unknown_type
     */
    public function delete_event_apply_doc($id=NULL) {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
    	$doc = $data['doc']?$data['doc']:array();
        if(count($doc) > 0) {
        	foreach($doc as $v) {
        		if(empty($v['did']))
        			$this->send_response(400, NULL, '400609:信息id为空');
        	}
        } else {
        	$this->send_response(400, NULL, '400609:doc为空');
        }
    	$event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($event_info['organizer'] != $this->user_id) {
        	$this->send_response(400, NULL, '400603:没有权限');
        }
    	foreach($doc as $v) {
        	$this->model->deleteEventApplyDoc($v['did']);
        }
        $this->send_response(200);
    }
    
    /**
     * 
     * @return unknown_type
     */
    public function update_event_apply_doc($id=NULL) {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
    	$doc = $data['doc']?$data['doc']:array();
        if(count($doc) > 0) {
        	foreach($doc as $v) {
        		if(empty($v['did']))
        			$this->send_response(400, NULL, '400609:信息id为空');
        		if(empty($v['title']))
        			$this->send_response(400, NULL, '400606:标题为空');
        	}
        } else {
        	$this->send_response(400, NULL, '400609:doc为空');
        }
    	$event_info = $this->model->get($id);
        if(!$event_info) {
        	$this->send_response(400, NULL, '400602:活动不存在');
        }
        if($event_info['organizer'] != $this->user_id) {
        	$this->send_response(400, NULL, '400603:没有权限添加');
        }
    	foreach($doc as $v) {
        	$this->model->updateEventApplyDoc($v['did'],$v['title']);
        }
        $this->send_response(200);
    }

    
    /**
     * 检查活动类型值是否合法的回调方法
     * @param  $type   活动类型值
     */
    
    public function _check_apply_doc($apply_doc) {
    	if(is_array($apply_doc) && count($apply_doc) >0) {
    		foreach($apply_doc as $k => $v) {
    			if(empty($v['title']))
    				return false;
    		}
    		return true;
    	}
    	return false;
    }
    /**
     * 检查活动类型值是否合法的回调方法
     * @param  $type   活动类型值
     */
    public function _check_type_validation($type) {
    	$type_array =  array_values(Kohana::config('event.type'));
    	if(!in_array($type, $type_array)) {
    		return FALSE;
    	}
    	return TRUE;
    }
    
    /**
     * 检查活动时间是否合法的回调方法
     * @param  $type   活动类型值
     */
    public function _check_time_validation($post) {
    	$array = $post->as_array();
    	if($array['end_time'] && $array['start_time'] >= $array['end_time']) {
       		$post->add_error('end_before_start', 'end_time_before_or_equal_start_time');
    	}
    }
    
    /**
     * 
     * 检查活动状态
     * @param $post
     */
    public function _check_status_validation($post) {
    $array = $post->as_array();
    	if($array['status']) {
       		if(!array_key_exists($array['status'], Kohana::config('event.status')))
       			$post->add_error('status_invalid', 'status_invalid');
    	}
    }
    
    /**
     * 
     * 检查照片合法性
     * @param $image
     */
    public function _check_image_validation($post) {
   	 	$array = $post->as_array();
    	foreach($array['image'] as $v) {
    		if(!$v['id'])
       			$post->add_error('image_id_invalid', 'image_id_invalid');
    	}
    }
    
    /**
     * 检查用户是否合法的回调方法
     * @param  $type   活动类型值
     */
    public function _check_user_validation($post) {
    	$array = $post->as_array();
    	foreach($array['user'] as $k => $v) {
	    	if(empty($v['name'])) {
	    		$post->add_error('user_name_empty', 'user_name_empty');
	    	}
	    	if(empty($v['mobile'])) {
	    		$post->add_error('user_mobile_empty', 'user_mobile_empty');
	    	}
	    	if(! international::check_is_valid ( '86',$v['mobile'] )){
	    		$post->add_error('user_mobile_format', 'user_mobile_format');
	    	}
    	}
    }
    
    /**
     * 
     * 校验dependent
     * @param array $dependent
     */
    private function _check_dependent_apply($dependent,$eid,$uid,$apply_type) {
    	if(count($dependent) > 0) {
    		foreach($dependent as $v) {
    			if($this->model->getApplyType(array('eid'=>$eid,'pid'=>$uid,'name'=>$v['name'],'apply_type'=>$apply_type)))
    				return true;
    		}
    	}
    	return false;
    }
    
    /**
     * 
     * @param $eid
     * @param $apply_doc
     * @return boolean
     */
    private function _check_user_apply_doc_valid($eid,$apply_doc) {
    	$doc_ids = $this->model->getEventApplyDocId($eid);
    	if($apply_doc && count($doc_ids) > 0) {
    		foreach($apply_doc as $v) {
    			if(!in_array($v['did'],$doc_ids))
    				return false;
    		}
    		return true;	
    	}
    	return false;
    }
    
    /**
     * 
     * 家属报名
     * @param unknown_type $dependent
     * @param unknown_type $eid
     * @param unknown_type $uid
     */
    private function _dependent_apply_event($dependent,$eid,$uid,$apply_type) {
    	$this->model->deleteEventUser(array('pid'=>$uid,'uid'=>0,'eid'=>$eid));
    	if(count($dependent) > 0 && is_array($dependent)) {
    		foreach($dependent as $v) {
    			$eventUser = array();
		    	$eventUser = array(
					'eid' => $eid,
					'pid' => $uid,
					'uid' => 0,
					'name' => $v['name'],
					'mobile' => $v['mobile']?$v['mobile']:'',
					'apply_type' => $apply_type,
					'apply_time' => time(),
					'grade' => Kohana::config('event.grade.normal')
				);
				if(!empty($v['apply_doc'])) {
					$this->model->addUserApplyDoc($eid,0,$uid,$v['name'],$v['apply_doc']);
				}
				$this->model->applyEvent($eventUser);
    		}
    	}
    }
    
    /**
     * 
     * 获取用户uid
     * @param array $user
     */
    private function _get_event_uid($user) {
    	$data = array();
		$result = User_Model::instance()->create_at($user, $this->user_id, 0);
		
		if(count($result) > 0) {
			foreach($result as $v) {
				$data[$v['mobile']] = array('user_id'=>$v['user_id'],'name'=>$v['name']);	
			}
		}
		return $data;
    }
    
    /**
     * 
     * @param $uid
     * @param $eid
     * @param $apply_doc
     * @return unknown_type
     */
    private function _create_user_apply_doc($uid,$eid,$apply_doc) {
    	$sql = "SELECT ";
    }
    
    /**
     * 创建群组
     * @param  $event_info   活动信息
     */
    private function _create_group($event_info) {
    	//创建活动群组
    	$groupInfo['type'] = Kohana::config('group.type.event');
    	$groupInfo['privacy'] = Kohana::config('group.privacy.public');
    	$groupInfo['gname'] = $event_info['title'];
		$groupInfo['notice'] = $event_info['summary'];
		$groupInfo['create_time'] = time();
		$groupInfo['modify_time'] = time();
		$groupInfo['creator_id'] = $this->user_id;
		$groupInfo['master_id'] = $this->user_id;
		$groupInfo['member_number'] = 1;
		$group_id = Group_Model::instance()->add($groupInfo);
		return $group_id;
    }
    
    /**
     * 更新群组
     * @param  $event_info   活动信息
     */
    private function _update_group($gid,$event_info) {
    	//创建活动群组
    	if($event_info['title'])
    		$groupInfo['gname'] = $event_info['title'];
    	if($event_info['summary'])
			$groupInfo['notice'] = $event_info['summary'];
		$groupInfo['modify_time'] = time();
		Group_Model::instance()->modifyGroup($gid,$groupInfo);
		return true;
    }
    
    /**
     * 添加到群组
     * @param  $event_info   活动信息
     */
    private function _add_group_user($gid,$uid,$grade) {
    	if(!Group_Model::instance()->getMemberGrade($gid,$uid)) {
			//添加群成员
			Group_Model::instance()->addGroupMember($gid,$uid, $grade);
			//添加群tab
			Tab_Model::instance()->create($uid,Kohana::config('group.type.event'),$gid);
    	}
    }

    /**
     * 从群组中删除
     * @param  $event_info   活动信息
     */
    private function _del_group_user($gid,$uid) {
		//从群组中删除
		if(Group_Model::instance()->delGroupMember($gid,$uid)) {
			Group_Model::instance()->reduceMemberNum($gid);
		}
		//从tab中删除
		Tab_Model::instance()->delete($uid, Kohana::config('group.type.event'), $gid);
    }
    
    /**
     * 
     * 整理活动列表数据
     * @param $data
     */
    private function _arrange_event_list($data) {
    	$arranged_list = array();
    	if(count($data) > 0) {
    		foreach($data as $key => $item) {
    			$arranged_list[$key] = $this->_arrange_event_item($item,false);
    		}
    	}
    	return $arranged_list;
    }
    
    /**
     * 
     * 整理活动内容
     * @param unknown_type $item
     */
    private function _arrange_event_item($item,$is_item=true,$list_user=false) {
    	$arranged_item = array();
    	if(!empty($item)) {
	        $arranged_item['id'] = $item['eid'];
	        $arranged_item['gid'] = $item['gid'];
	        $arranged_item['type'] = $item['type'];
			$arranged_item['title'] = $item['title'];
			$arranged_item['summary'] = $item['summary'];
			$arranged_item['content'] = $item['content'];
	    	$arranged_item['organizer'] = array('id'=>$item['organizer'],'name'=>sns::getrealname( $item ['organizer'] ),'avatar'=>sns::getavatar ( $item ['organizer'] ));
			$arranged_item['start_time'] = $item['start_time'];
			$arranged_item['end_time'] = $item['end_time'];
			$event_images = $this->_list_event_image($item['eid']);
			$arranged_item['cover_image'] = array();
			if($event_images['cover_image']) {
				$arranged_item['cover_image'] = $event_images['cover_image'];
			}
			$arranged_item['images'] = $event_images['list_image']?$event_images['list_image']:array();
			$arranged_item['assemble_location'] = $item['assemble_location']?unserialize($item['assemble_location']):array();
			$arranged_item['event_location'] = $item['event_location']?unserialize($item['event_location']):array();
			$arranged_item['fee'] = $item['fee'];
			$arranged_item['deadline'] = $item['deadline'];
			$arranged_item['apply_desc'] = $item['apply_desc'];
			$arranged_item['private'] = $item['private'];
			$arranged_item['apply_doc'] = $this->model->getEventApplyDoc($item['eid']);
			$arranged_item['city'] = $this->model->getCityName($item['city']);
			if($this->user_id)
				$arranged_item['apply_type'] = $this->_check_apply_type($item['apply_type'],$item['eid'],$this->user_id);
			$arranged_item['joined'] = array('number'=>$this->model->getUserCount(array('eid'=>$item['eid'],'apply_type'=>Kohana::config('event.apply_type.joined'))));
			$arranged_item['interested'] = array('number'=>$this->model->getUserCount(array('eid'=>$item['eid'],'apply_type'=>Kohana::config('event.apply_type.interested'))));
			if($is_item) {
				$refused_user = $this->model->getEventUser($item['eid'],Kohana::config('event.apply_type.refused'));
				$arranged_item['refused']['number'] = $this->model->getUserCount(array('eid'=>$item['eid'],'apply_type'=>Kohana::config('event.apply_type.refused')));
				$unconfirmed_user = $this->model->getEventUser($item['eid'],Kohana::config('event.apply_type.unconfirmed'));
				$arranged_item['unconfirmed']['number'] = $this->model->getUserCount(array('eid'=>$item['eid'],'apply_type'=>Kohana::config('event.apply_type.unconfirmed')));
				//必须要登录情况下才能看到成员列表
				if($list_user && $this->user_id) {
					$joined_user = $this->model->getEventUser($item['eid'],Kohana::config('event.apply_type.joined'));
					$arranged_item['joined']['user'] = $this->_arrange_user_list($joined_user,Kohana::config('event.apply_type.joined'),$item);
					$interested_user = $this->model->getEventUser($item['eid'],Kohana::config('event.apply_type.interested'));
					$arranged_item['interested']['user'] = $this->_arrange_user_list($interested_user,Kohana::config('event.apply_type.interested'),$item);
					$arranged_item['refused']['user'] = $this->_arrange_user_list($refused_user,Kohana::config('event.apply_type.refused'),$item);
					$arranged_item['unconfirmed']['user'] = $this->_arrange_user_list($unconfirmed_user,Kohana::config('event.apply_type.unconfirmed'),$item);
				}
			}
			$arranged_item['status'] = $this->_check_event_status($item['start_time'],$item['end_time'],$item['status'],$item['eid'],$item['deadline']);
    	}
		return $arranged_item;
    }
    
    /**
     * 
     * 整理用户列表
     * @param unknown_type $data
     */
    private function _arrange_user_list($data,$apply_type,$event_info) {
    	$arranged_list = array();
    	if(count($data) > 0) {
    		foreach($data as $key => $item) {
    			$arranged_list[$key] = $this->_arrange_user_item($item,$apply_type,$event_info);
    		}
    	}
    	return $arranged_list;
    }
    
    /**
     * 
     * 整理用户信息
     * @param array $item
     * @param int $apply_type
     */
    private function _arrange_user_item($item,$apply_type,$event_info) {
    	$arranged_item = array();
    	if(!empty($item)) {
    		 $arranged_item['uid'] = $item['uid'];
    		 $arranged_item['pid'] = $item['pid'];
    		 $arranged_item['name'] = $item['name'];
    		 $arranged_item['avatar'] = sns::getavatar($item['uid']);
    		 if($this->user_id==$event_info['organizer'] || $this->user_id==$item['uid'] || $this->user_id==$item['pid']) {
	    		 $arranged_item['mobile'] = $item['mobile'];
	    		 if($apply_type == Kohana::config('event.apply_type.joined'))
	    		 	$arranged_item['apply_doc'] = $this->model->getUserApplyDoc($item['eid'],$item['uid'],$item['pid'],'',$item['name']);
    		 }
    	}
    	return $arranged_item;
    }
    
    
    /**
     * 
     * 整理活动照片.
     * @param array $image
     */
    private function _create_event_image($event_id,$image,$force_cover=0) {
    	$num = 0;
    	foreach($image as $k => $v) {
    		$data = array();
    		if($v['id']) {
	    		$data['pid'] = $v['id'];
	    		$data['eid'] = $event_id;
	    		$data['uid'] = $this->user_id;
    			$data['title'] = isset($v['title'])?trim($v['title']):'';
    			$data['created'] = time();
    			$data['width'] = 0;
    			$data['height'] = 0;
    			$data['banner'] = $v['banner']==1?1:0;
    			if($force_cover) {
    				$data['cover']=1;
    			} else {
    				$data['cover'] = $v['cover']==1?1:0;
    			}
    			$data['url'] = '';
    		 	$url = Photo_Controller::geturl ( $v ['id']);
    		 	if($url[0])
    		 		$data['url'] = $url[0];
				$size = Photo_Controller::getinfo ( $v ['id']);
				if($size[0]) {
					$data['width'] = $size[0]['width'];
					$data['height'] = $size[0]['height'];
				}
				if(empty($data['url'])) 
					continue;
				Event_Image_Model::instance()->create($data);
				$num++;
    		}
    	}
    	return $num;
    }
    
    /**
     * 
     * @param $event_id
     * @param $apply_doc
     * @return 
     */
    private function _create_event_apply_doc($event_id,$apply_doc) {
    	foreach($apply_doc as $v) {
    		$doc = $this->model->getEventApplyDoc($event_id,$v['title']);
    		if(!$doc)
    			$this->model->addEventApplyDoc($event_id,$v['title']);
    	}
    	return true;
    }
    
    /**
     * 
     * 整理活动照片
     * @param array $image
     */
    private function _arrange_event_image($image) {
    	foreach($image as $k => $v) {
    		if($v['cover']) {
    			$cover_image[] = $this->_format_image($v,true);
    		} else {
    			$list_image[] = $this->_format_image($v);
    		}
    	}
    	return array('cover_image'=>$cover_image,'list_image'=>$list_image);
    }
    
    /**
     * 
     * 格式化图片数据
     * @param unknown_type $v
     */
    private function _format_image($v,$show_banner=false) {
    	$image = array();
    	$image['id'] = $v['pid'];
    	if($show_banner)
    		$image['banner'] = $v['banner']==1?1:0;
    	$image['title'] = $v['title'];
    	$image['url'] = $v['url']?$v['url']:'';
    	$image['meta'] = array('width'=>(int)$v['width'],'height'=>(int)$v['height']);
    	if(empty($v['height']) || empty($v['width'])) {
			$size = Photo_Controller::getinfo ( $v ['id']);
    	if($size) 
			$image['meta'] = array('width'=>(int)$size[0]['width'],'height'=>(int)$size[0]['height']);
    	}
    	if(empty($image['url'])) {
    		$url = Photo_Controller::geturl ( $v ['pid']);
    		if($url)
    			$image['url'] = $url[0];
    	}
    	return $image;
    }
    
    /**
     * 
     * 获取活动照片
     * @param unknown_type $event_id
     */
    private function _list_event_image($event_id) {
    	$image = Event_Image_Model::instance()->listByEvent($event_id);
    	if($image) {
    		return $this->_arrange_event_image($image);
    	}
    	return array();
    }

    /**
     * 
     * 格式化活动报名
     * @param int $start_time
     * @param int $end_time
     */
    private function _check_apply_type($apply_type,$eid,$uid) {
    	//$apply_type_cn = Kohana::config('event.apply_type_cn');
    	$apply_type = (int) $apply_type;
    	//if($apply_type == 0) {
    	//	if($this->model->be_event_invite($eid,$uid))
    	//		return Kohana::config('event.apply_type.unconfirmed');
    	//}
    	return $apply_type;
    }
    
    /**
     * 
     * 格式化活动状态
     * @param int $start_time
     * @param int $end_time
     */
    private function _check_event_status($start_time,$end_time,$status,$eid,$deadline=0,$update=true) {
    	$now = time();
    	$current_status = $status;
    	if($now <= $deadline) {
    		$current_status=1;
    	}elseif($now > $deadline) {
    		$current_status=2;
    		if($end_time && $now > $end_time)
    			$current_status=3;
    	}
    	if($update && $status != $current_status) 
    		$this->model->update($eid, array('status'=>$current_status));
    	return $current_status;
    }
    
} // End Event Controller


