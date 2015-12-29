<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 群联系人分组控制器文件
 */
/**
 * 群联系人控制器
 */
class Group_Contact_Controller extends Controller
{
    /**
     * 是否发布模式
     */
    const ALLOW_PRODUCTION = TRUE;
    //protected $user_id = 10901978;
    /**
     * 联系人模型
     * @var Group_Contact_Model
     */
    protected $model;

    public function __construct ()
    {
        parent::__construct();
        $this->user_id = $this->getUid();
        $this->model = Group_Contact_Model::instance();
//        if (empty($this->user_id)) {
//            $this->user_id = 10750797;
//        }
    }

    /**
     * 获取联系人列表
     */
    public function index ($id = NULL)
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
     	if (! is_numeric($id) or empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
    	$groupModel = new Group_Model();
        $groupInfo = $groupModel->getGroupInfo($id);
        if(!$groupInfo) {
        	$this->send_response(400, NULL, '400402:群不存在');
        }
        $result = array();
        $grade = $groupModel->getMemberGrade($id, $this->user_id);
        if($grade < Kohana::config('group.grade.normal')) {
        	$this->send_response(400, NULL, '400411:非群成员，无权限查看联系人列表');
        }
        if($groupInfo['type'] == Kohana::config('group.type.public')) {
        	//公开群无联系人
        	$this->send_response(200, $result);
        }
		$groupManager = $groupModel->getGroupManager($id);
		$managerIdList = array();
		foreach($groupManager as $val) {
			$managerIdList[] = intval($val['uid']);
		}
        $info = $this->input->get('info', 0);
        $info = $info == 1 ? $info : 0;
        $list = $this->model->get($id);
        if (! empty($list)) {
        	$friendModel = new Friend_Model();
            foreach ($list as $id => $value) {
				$grade = 0;
            	$value['id'] = floatval($value['id']);
            	
            	$value['is_friend'] = 0;
                if($value['momo_user_id'] > 0) {
	                $isFriend = $friendModel->check_isfriend($this->user_id, $value['momo_user_id']);
	                if($isFriend) {
	                	$value['is_friend'] = 1;
	                }
					if($value['momo_user_id'] == $groupInfo['creator_id']) {
						$grade = 3;
					} else if(in_array($value['momo_user_id'], $managerIdList)) {
						$grade = 2;
					} else {
						$grade = 1;
					}
                }
				$value['grade'] = $grade;
                $result[] = $info == 0 ? array('id' => floatval($id),
                'modified_at' => $value['modified_at']) : $value;
            }
        }
        $this->send_response(200, $result);
    }

    /**
     * 获取单个联系人信息
     * @param int $id 联系人ID
     */
    public function show ($id = NULL)
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if (! is_numeric($id) or empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        } else {
            //try {
                $id = (int) $id;
                $result = $this->model->get(0, $id);
                if ($result === FALSE) {
                    $this->send_response(400, NULL, '400414:群联系人不存在');
                } else {
                	$group_id = $result['group_id'];
                	$groupModel = new Group_Model();
                	$groupInfo = $groupModel->getGroupInfo($group_id);
                	if(!$groupInfo) {
                		$this->send_response(400, NULL, '400402:群不存在');
                	}
                	$grade = $groupModel->getMemberGrade($group_id, $this->user_id);
                	if($grade < Kohana::config('group.grade.normal')) {
                		$this->send_response(400, NULL, '400412:非群成员，无权限查看联系人');
                	} 
                	$result['is_friend'] = 0;
					$result['momo_user_id'] = intval($result['user_id']);
					unset($result['momo_user_id']);
                	if($result['momo_user_id'] > 0) {
	                	$friendModel = new Friend_Model();
	                	$isFriend = $friendModel->check_isfriend($this->user_id, $result['momo_user_id']);
	                	if($isFriend) {
	                		$result['is_friend'] = 1;
	                	}
                	}
                    $this->send_response(200, $result);
                }
            //} catch (Exception $e) {
            //    $this->send_response(500, NULL, '服务器内部错误');
            //}
        }
    }

    /**
     * 批量获取联系人信息
     * @param int $id 联系人ID
     */
    public function show_batch ($id = NULL)
    {

    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    	if (! is_numeric($id) or empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
    	$groupModel = new Group_Model();
        $groupInfo = $groupModel->getGroupInfo($id);
        if(!$groupInfo) {
        	$this->send_response(400, NULL, '400402:群不存在');
        }
        $grade = $groupModel->getMemberGrade($id, $this->user_id);
        if($grade < Kohana::config('group.grade.normal')) {
        	$this->send_response(400, NULL, '400411:非群成员，无权限查看联系人详细信息');
        }
        
        $data = $this->get_data();
        $ids = isset($data['ids']) ? $data['ids'] : '';
		if(empty($ids)) {
			$this->send_response(400, NULL, '400215:群联系人ids为空');
		}
        $ids = explode(',', $ids);
        $result = array();
        if (count($ids) > 100) {
            $this->send_response(400, NULL, '400216:群联系人ids超过上限(100个)');
        }
        $contact_ids = array_keys($this->model->get($id));
        $friendModel = new Friend_Model();
        foreach ($ids as $cid) {
            if (in_array($cid, $contact_ids)) {
                $contact = $this->model->get($id, $cid);
                if ($contact !== FALSE) {
	                $contact['is_friend'] = 0;
                	if($contact['user_id'] > 0) {
	                	$isFriend = $friendModel->check_isfriend($this->user_id, $contact['user_id']);
	                	if($isFriend) {
	                		$contact['is_friend'] = 1;
	                	}
                	}
					$contact['momo_user_id'] = intval($contact['user_id']);
					unset($contact['user_id']);
                    $result[] = $contact;
                }
                unset($contact);
            }
       }
       $this->send_response(200, $result);
    }

    /**
     * 创建联系人
     */
    public function create_batch ()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $force = $this->input->get('force', 0);
        $force = $force == 1 ? 1 : 0;
        $result = array();
        if (! empty($data)) {
            foreach ($data as $key => $contact_arr) {
                try {
                    $contact = $this->_array_to_contact($contact_arr);
                    $contact->set_group_id($this->group_id);
                    $status = $this->model->add($contact, $force);
                    if ($status) {
                        $result[$key] = $contact->to_simple_array();
                    } else {
                        $result[$key] = array('id' => $contact->get_id(), 
                        'modified_at' => '');
                    }
                } catch (Group_Contact_Input_Exception $cie) {
                    //该联系人输入内容有误，无法转换为联系人对象
                    $result[$key] = array('id' => 0, 
                    'modified_at' => '');
                } catch (Exception $e) {
                    //服务器内部错误
                }
            }
            if (! empty($result)) {
                $this->model->clear_cache($this->group_id);
            }
        }
        $this->send_response(200, $result);
    }

    /**
     * 批量删除联系人
     */
    public function destroy_batch ()
    {
        $this->_update_by_tag('deleted', 1);
    }

    /**
     * 根据标签更新联系人
     * @param stirng $tag_name
     * @param string $tag_value
     */
    private function _update_by_tag ($tag_name, $tag_value)
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $ids = isset($data['ids']) ? $data['ids'] : '';
        $ids = explode(',', $ids);
        $result = array();
        if (empty($ids)) {
            $this->send_response(400, NULL, '400212:ids为空');
        } elseif (count($ids) > 100) {
            $this->send_response(400, NULL, '400214:ids超过上限（100个）');
        }
        if (! empty($ids)) {
            $used_ids = $this->model->update_by_tag($this->group_id, $ids, 
            $tag_name, $tag_value);
            if (! empty($used_ids)) {
                $list = $this->model->get($this->group_id);
                $contact_ids = array_keys($list);
                foreach ($used_ids as $id) {
                    if (in_array($id, $contact_ids)) {
                        $result[] = array('id' => (int) $id, 
                        'modified_at' => $list[$id]['modified_at']);
                    }
                }
            }
        }
        $this->send_response(200, $result);
    }

    /**
     * 过滤输入、创建联系人对象
     * @param array $data 联系人信息
     * @return Contact $contact
     */
    private function _array_to_contact ($data)
    {
        $contact = new Contact();
        $bjx_arr = Kohana::config_load('bjx');
        foreach ($data as $type => $value) {
            switch ($type) {
                case 'tels':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        foreach ($value as $val) {
                            //                            if (! in_array(trim($val['value']), $tmp)) {
                            //                                $tmp[] = trim($val['value']);
                            $values[] = array(
                            'value' => trim($val['value']), 
                            'type' => $val['type'], 
                            'city' => $this->model->get_tel_location(
                            trim($val['value'])), 
                            'pref' => ! empty($val['pref']) ? (int) $val['pref'] : 0);
                             //                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'ims':
                    if (! empty($value)) {
                        $values = $tmp = $protocols = array();
                        foreach ($value as $val) {
                            //                            $keys = array_keys($tmp, trim($val['value']));
                            //                            $key = isset($keys[0]) ? $keys[0] : - 1;
                            //                            if ($key < 0 or $protocols[$key] != $val['protocol']) {
                            //                                $tmp[] = trim($val['value']);
                            //                                $protocols[] = $val['protocol'];
                            $values[] = array(
                            'value' => trim($val['value']), 
                            'protocol' => $val['protocol'], 
                            'type' => $val['type']);
                             //                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'addresses':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        //                        $t = '';
                        foreach ($value as $val) {
                            //                            $t = trim($val['country']) . '|' .
                            //                             trim($val['region']) . '|' . trim($val['city']) .
                            //                             '|' . trim($val['street']) . '|' .
                            //                             trim($val['postal']);
                            //                            if (! in_array($t, $tmp)) {
                            $values[] = array(
                            'country' => trim($val['country']), 
                            'region' => trim($val['region']), 
                            'city' => trim($val['city']), 
                            'street' => trim($val['street']), 
                            'postal' => trim($val['postal']), 
                            'type' => $val['type']);
                             //                                $tmp[] = $t;
                        //                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'emails':
                case 'urls':
                case 'events':
                case 'relations':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        foreach ($value as $val) {
                            //                            if (! in_array(trim($val['value']), $tmp)) {
                            $tmp[] = trim(
                            $val['value']);
                            $values[] = array('value' => trim($val['value']), 
                            'type' => $val['type']);
                             //                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'contact_group_ids':
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? $value : array());
                    break;
                case 'birthday':
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? $this->_filter_birthday($value) : '');
                    break;
                case 'recycled':
                case 'favorited':
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? (int) $value : 0);
                    break;
                default:
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? $value : '');
                    break;
            }
        }
        $formatted_name = $this->model->name_to_formatted_name(
        $data['family_name'], $data['given_name']);
        //拼接后的全名为空，并且输入的全名不是空的，把全名拆分设置
        if (empty($formatted_name) and ! empty($data['formatted_name'])) {
            $name = $this->model->formatted_name_to_name(
            $data['formatted_name']);
            $contact->set_given_name($name['given_name']);
            $contact->set_family_name($name['family_name']);
        } else {
            $fn = $formatted_name;
        }
        if (! empty($fn)) {
            require_once Kohana::find_file('vendor', 'pinyin/c2p');
            $phonetic = getPinYin($fn, false, ' ');
            $tmp = explode(' ', $phonetic);
            $sort = '';
            if (is_array($tmp)) {
                foreach ($tmp as $t) {
                    $sort .= isset($t[0]) ? $t[0] : '';
                }
            }
            if (empty($sort) or ! preg_match("/[a-z]/", $sort[0])) {
                $sort = '#';
            }
            $sort = substr($sort, 0, 20);
            $contact->set_formatted_name($fn);
            $contact->set_phonetic(implode('', $tmp));
            $contact->set_sort($sort);
        } else {
            $contact->set_formatted_name('');
            $contact->set_phonetic('');
            $contact->set_sort('#');
        }
        $contact->set_source('web');
        return $contact;
    }

    /**
     * 过滤生日，把生日转化成yyyy-mm-dd格式
     * (回调方法必须是公共方法)
     * @param string $tel
     */
    public function _filter_birthday ($birthday)
    {
        $result = '';
        if (is_numeric($birthday) and strlen($birthday) == 8) {
            $month = substr($birthday, 4, 2);
            $day = substr($birthday, 6, 2);
            $year = substr($birthday, 0, 4);
            if (checkdate($month, $day, $year) and $year > 1900) {
                $result = $year . '-' . $month . '-' . $day;
            } else {
                $result = '';
            }
            unset($month, $day, $year);
        } elseif (preg_match("/^\d{4}-\d{1,2}-\d{1,2}$/", $birthday)) {
            $tmp = explode('-', $birthday);
            for ($i = count($tmp) - 1; $i > 0; $i --) {
                if (strlen($tmp[$i]) == 1) {
                    $tmp[$i] = '0' . $tmp[$i];
                }
            }
            if (checkdate($tmp[1], $tmp[2], $tmp[0]) and $tmp[0] > 1900) {
                $result = implode('-', $tmp);
            } else {
                $result = '';
            }
            unset($tmp);
        } else {
            $result = '';
        }
        return $result;
    }

    /**
     * 过滤电话前的国家码
     * (回调方法必须是公共方法)
     * @param string $tel
     */
    public function _filter_tel ($tel)
    {
        //        $tel = preg_replace("/^[0+]?86\s?|\D/", '', $tel);
        //        if (! preg_match("/^13[0-9]|^15[0-3|5-9]|^18[6-9]/", $tel) AND
        //         strlen($tel) > 9 AND $tel[0] != 0) {
        //            $tel = '0' . $tel;
        //        }
        return $tel;
    }

    /**
     * 过滤非标准的邮箱
     * (回调方法必须是公共方法)
     * @param string $email
     */
    public function _filter_email ($email)
    {
        //        if (! empty($email) and ! preg_match(
        //        '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD', 
        //        (string) $email)) {
        //            $email = '';
        //        }
        return $email;
    }
} // End Group_Contact_Controller
