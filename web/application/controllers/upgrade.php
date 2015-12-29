<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * 用户反馈模块
 * @author Administrator
 *
 */
class Upgrade_Controller extends Controller 
{

    public function __construct() 
    {
        parent::__construct();
        $this->model = new Upgrade_Model();
    }

    public function index() 
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $data = $this->get_data();
         
        $support_platform = false;

        if (isset($data['text'])) 
	{
		$is_sign = 0;
		list($source, $install_id, $phone_model, $version, $is_sign) = explode(',', $data['text']);
	} 
	else 
	{
		if (isset($data['appid']))
		{
			$appid = (int)$data['appid'];
		}
		else
		{
			$appid = NULL;
		}

		$source = isset($data['source'])?trim($data['source']):'';
		$version = isset($data['version'])?trim($data['version']):'';
		$version = (float)(sprintf("%.3f", $version));
		if (empty($version))
		{
			$version = 0.001;
		}
		//d($version, "版本号");

            if (!isset($data['signed'])) {
            	$is_sign = 1;
            } else {
            	$is_sign = $data['signed'];
            	if ($is_sign === 'true') {
            		$is_sign = true;
            	} elseif ($is_sign === 'false') {
            		$is_sign = false;
            	}
            }
            // echo Kohana::debug($is_sign);exit;
            
            // 如果没有设置渠道，则默认为'1'
            $install_id = isset($data['install_id']) ? (string)$data['install_id'] : 'momo';
            
            // 如果没有设置机型，则默认为空
            if($appid) {
            	$phone_model = '';
            } else {
            	$phone_model = isset($data['phone_model']) ? (string)$data['phone_model'] : '';
            }
            
            
            // 如果没有设置内测，默认为否
            $is_beta = isset($data['is_beta']) ? (int)$data['is_beta'] : 0;
            
        }

        if(empty($source)) {
            $this->send_response(400, NULL, '400903:平台类型为空');
        }
        if(empty($version)) {
            $this->send_response(400, NULL, '400904:版本号为空');
        }
        $support_platform = Kohana::config('upgrade.platform');
         
        foreach($support_platform as $k => $v) 
	{
		// 判断该平台的升级包是否存在
            if($source == $k) {
                $is_support_platform = true;
                break;
            }
        }

        if(!$is_support_platform) {
            $this->send_response(400, NULL, '400905:该平台暂不支持');
        }
        $platform = $support_platform[$source];
	//d($platform);
        
        if (($platform == 'j2me' || $platform == 'android') && $install_id == "3gmo") 
	{
		// 来源渠道
            $install_id = "momo";
        }
            
	//d($platform, "升级的手机固件");
	//d($appid, "应用ID");
	$otherInfo = array();
	$otherInfo['appid'] = $appid;

	$source_data = $this->model->get_platform_data(
			$platform, 
			$is_sign, 
			$install_id, 
			$phone_model, 
			$is_beta, 
			$otherInfo
			);

	if (is_array($source_data) 
		&& isset($source_data['appid_not_exist'])
		&& $source_data['appid_not_exist'] === TRUE)
	{
		$this->send_response(400, NULL, '400911:该应用尚未发布版本');
	}

        if(empty($source_data)) 
	{
		// 升级响应为空
            //$this->send_response(400, NULL, '400906:该平台暂无版本');
            $source_data = $this->model->get_platform_data($platform, $is_sign, $install_id, '', $is_beta, $otherInfo);
        }
        
        if(empty($source_data) && !empty($phone_model)) 
	{
        	//$this->send_response(400, NULL, '400906:该平台暂无版本');
        	$source_data = $this->model->get_platform_data($platform, 1, $install_id, '', $is_beta);
        }
        
        $clientVersion = (float)(sprintf("%.3f", $source_data['version']));
        if($clientVersion <= $version) {
            $this->send_response(400, NULL, '400907:无更新版本');
        }
        
        // 如果平台是iphone，则使用plist文件
        if ($platform == 'iphone' && preg_match('/\.ipa$/is', $source_data['download_url'])) {
            $qstring = rtrim(strtr(base64_encode("{$appid}\t{$source_data['version']}\t{$source_data['download_url']}"), '+/', '-_'), '=');
        	$return = array('current_version'=>$source_data['view_version'],'file_size'=>$source_data['file_size'],'publish_date'=>$source_data['publish_date'],'download_url'=>$source_data['download_url'],'remark'=>$source_data['remark'], 'force_update' => $source_data['force_update'], 'plist'=>url::base()."client/plist/{$qstring}");
        	if($appid == 35){
        		//$return['plist'] = 'http://meiyegj.com/router/proxy.php?url=' . urlencode($return['plist']);
        	}
        	$return['pkg_download_url'] = $return['download_url'];
        	$return['download_url'] = $return['plist'];
        } else {
        	$return = array('current_version'=>$source_data['view_version'],'file_size'=>$source_data['file_size'],'publish_date'=>$source_data['publish_date'],'download_url'=>$source_data['download_url'],'remark'=>$source_data['remark'], 'force_update' => $source_data['force_update']);
        	$return['pkg_download_url'] = $return['download_url'];
        }
        
        if($platform == 'windows_mobile') {
            $patch = $this->model->get_platform_patch($platform,$version,$source_data['version']);
            if($patch) {
                $return = array('pre_version'=>$patch['pre_version'],'current_version'=>$patch['view_version'],'patch_download_url'=>$patch['download_url'],'patch_file_size'=>$patch['file_size'],'patch_remark'=>$patch['remark'], 'force_update' => $source_data['force_update']);
            }
        }
        $this->send_response(200,$return);
        exit;
    }
    
    public function lists() {
    	if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
	$return = array();
        $channel = $this->input->get('install_id');
        if (!isset($channel) || empty($channel)) {
        	$channel = 'momo';
        }
    	$result = $this->model->get_all_platform($channel);
    	if ($result) {
	    	foreach ($result as $value) {
	    		$return[] = array('platform' => $value['platform'], 'current_version'=>$value['view_version'],'file_size'=>$value['file_size'],'publish_date'=>$value['publish_date'],'download_url'=>$value['download_url'],'remark'=>$value['remark'], 'force_update' => $value['force_update'], 'signed' => (bool)$value['signed']);
	    	}
    	} 
    	$this->send_response(200,$return);
    }
    
    public function get()
    {
    	if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $channel = $this->input->get('install_id');
        $platform = $this->input->get('platform');
        $signed = $this->input->get('signed');
        $ext = $this->input->get('ext');
        if (!isset($channel) || empty($channel)) {
        	$channel = 'momo';
        }
        if (!isset($platform) || empty($platform)) {
        	$platform = 'android';
        }
        if (!isset($signed) || empty($signed)) {
        	$signed = 0;
        }
        if (!isset($ext) || empty($ext)) {
        	$ext = 'rar';
        }
        $result = $this->model->getAppInfo($channel, $platform, $signed, $ext);
         
        if ($result) {
        $appInfo = $result[0];
    	$return = array();
    	$return = array(
    		'version' => $appInfo['version'],
    		'download_url' => $appInfo['download_url'],
    		'file_size' => $appInfo['file_size'],
    		'publish_date' => $appInfo['publish_date']
    	);
        } else {
        	$return = '';
        }
        $this->send_response(200,$return);
    	//$result = $this->model->get_all_platform($channel);
    }
    
    /**
     * 获取指定机型的j2me升级包，网站使用
     *
     */
    public function website_j2me() {
    	if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $phoneModel = $this->input->get('phone_model');
        $isSigned = $this->input->get('signed');
        $isAlpha = $this->input->get('is_beta');
        
        // 取得所有的包
        $all = $this->input->get('all');
        
        if (!is_numeric($phoneModel)) {
    		$phoneModel = 0;
    	}
    	if (!is_numeric($isSigned)) {
    		$isSigned = 1;
    	}
    	if (!is_numeric($isAlpha)) {
    		$isAlpha = 0;
    	}
    	
    	$return = array();
    	
    	if (isset($all)) {
    		$appInfo = $this->model->getJ2mePkg(0, 0, 0);
    		$appInfo = $this->model->setJ2meUrl($appInfo, 0, 0, 0);
    		$return[] = $appInfo;
    		$appInfo = $this->model->getJ2mePkg(0, 1, 0);
    		$appInfo = $this->model->setJ2meUrl($appInfo, 0, 1, 0);
    		$return[] = $appInfo;
    	} else {
    		$appInfo = $this->model->getJ2mePkg($phoneModel, $signed, $alpha);
    		$appInfo = $this->model->setJ2meUrl($appInfo, $phoneModel, $isSigned, $isAlpha);
    		$return[] = $appInfo;
    	}
        // echo Kohana::debug($appInfo);exit;
        $this->send_response(200,$return);
    }
    
    public function beta() {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $appid = (int)$data['appid'];
        $source = (int)$data['source'];
        $version = trim($data['version']);
        if(!$appid) {
            $this->send_response(400, NULL, '40011:appid为空');
        }
        if(!$version) {
            $version = 0;
        }
        $support_platform = Kohana::config('upgrade.platform');
        foreach($support_platform as $k => $v)  {
            if($source == $k) {
                $is_support_platform = true;
                break;
            }
        }
        if(!$is_support_platform) {
            $this->send_response(400, NULL, '400905:该平台暂不支持');
        }
        $platform = $support_platform[$source];
        $res = $this->model->getUpgradeBeta($appid,$platform,$version);
        
        if($res) {
        	$patch = array('current_version'=>$res['version'],'file_size'=>$res['file_size'],'publish_date'=>date('Y-m-d H:i:s',$res['publish_date']),'download_url'=>$res['download_url'],'remark'=>$res['remark']);
        	$patch['pkg_download_url'] = $patch['download_url'];
        	if($res['plist']){
        		$patch['download_url'] = $patch['plist'] = $res['plist'];
        	}
        	$this->send_response(200,$patch);
        }
        $this->send_response(400, NULL, '400907:无更新版本');
    }
}
