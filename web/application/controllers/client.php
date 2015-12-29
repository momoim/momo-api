<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 
 * 动态安装包生成
 * web端如果有登录要传web_token，没有登录不传web_token
 *
 */
class Client_Controller extends Controller {
    
    private $key='d0gtnbf$#1fHOer25&^';
    
    //包装下载地址MoMo.{升级表id}.{平台扩展}
    public function j2me(){
        $db = Database::instance();
        
        if($this->input->get('signed')){
            $signed=1;
        }else{
            $signed=0;
        }
        $channel=$this->input->get('install_id');
        $brand=$this->input->get('phone_model');
        if ($channel == '3gmo') {
        	$channel = 'momo';
        }
        
        if($brand){
            $sql="SELECT u.*,b.brand_id,b.upgrade_id FROM `upgrade` u 
            LEFT JOIN `upgrade_brand` b ON (b.upgrade_id=u.id) 
            WHERE u.platform = 'j2me' AND u.signed=$signed AND u.channel='$channel' AND RIGHT(u.download_url, 3) = 'jad' AND b.brand_id='$brand' 
            ORDER BY u.`id` DESC LIMIT 1";
            $query = $db->query($sql);
        }
        
        // 如果没有传机型或者找不到该机型则使用通用包
        if(! $query || ! $query->count()){
            $sql="SELECT * FROM `upgrade` 
            WHERE platform = 'j2me' AND signed=$signed AND channel='$channel' AND RIGHT(download_url, 3) = 'jad' AND mobile_brand = 0
            ORDER BY `id` DESC LIMIT 1";
            $query = $db->query($sql);
        }
        
        $res = $query->result_array(FALSE);
        if($res){
            $r=$this->_get_package_url($res[0], 'jad');
            $this->send_response(200,$r);
    	}else{
    	    $this->send_response(404,'','无安装包');
    	}
    }
    
    private function _get_appname($appid){
        $appname = '移动MOMO';
        
        $db = Database::instance();
        if($appid > 11){
            $query = $db->query("SELECT osr_application_title FROM oauth_server_registry WHERE osr_id={$appid}");
        
            $res = $query->result_array(FALSE);
            if($res[0] and $res[0]['osr_application_title']){
                $appname = $res[0]['osr_application_title'];
            }
        }
        
        return $appname;
    }
    
    private function _get_upgrade_table($beta){
        $table = 'upgrade';
        if($beta){
            $table .= '_beta';
        }
        
        return $table;
    }
    
    public function plist($data){
    	$arr = explode("\t", base64_decode(strtr($data, '-_', '+/')));
    	$appid = $arr[0];
    	$version_str = $version = $arr[1];
    	$download_url = $arr[2];
    
    	if($appid==29){
    		$appname = '91来电秀';
    		$identifier = 'com.nd.91show';
    		$imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
    		$breakjail = "（需越狱）";
    	}elseif($appid==36){
    		$appname = '91问医';
    		$identifier = 'com.nd.hospitaladvisor';
    		$imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
    		$breakjail = "";
    	}elseif($appid==35){
    		$appname = '91美发';
    		$identifier = 'com.nd.hair';
    		$imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
    		$version_str = $breakjail = "";
    	}else{
    		$appname = '移动MOMO';
    		$identifier = 'com.nd.momo';
    		$imgurl = 'http://d.momo.im/icons/icon.png';
    		$breakjail = "（需越狱）";
    	}
    
    	$imgurl_big = preg_replace('/(\.png)$/is', '@2x$1', $imgurl);
    
    	echo <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
   <key>items</key>
   <array>
       <dict>
           <key>assets</key>
           <array>
               <dict>
                   <key>kind</key>
                   <string>software-package</string>
                   <key>url</key>
                   <string><![CDATA[{$download_url}]]></string>
               </dict>
               <dict>
                   <key>kind</key>
                   <string>display-image</string>
                   <key>needs-shine</key>
                   <true/>
                   <key>url</key>
                   <string>{$imgurl}</string>
               </dict>
               <dict>
                   <key>kind</key>
                   <string>full-size-image</string>
                   <key>url</key>
                   <string>{$imgurl_big}</string>
               </dict>
           </array>
           <key>metadata</key>
           <dict>
               <key>bundle-identifier</key>
               <string>{$identifier}</string>
               <key>bundle-version</key>
               <string>{$version}</string>
               <key>kind</key>
               <string>software</string>
               <key>subtitle</key>
               <string>{$appname}</string>
               <key>title</key>
               <string>{$appname} {$version_str}{$breakjail}</string>
           </dict>
       </dict>
   </array>
</dict>
</plist>
EOF;
    	exit();
    }
    
    public function iphone(){
        $beta = (int)$_GET['beta'];
        $table = $this->_get_upgrade_table($beta);
        
        $appid = (int)$this->appid;
        if(!$appid){
            $appid = (int)$_GET['appid'];
        }
        //$appname = $this->_get_appname($appid);
        
        if($appid==29){
            $appname = '91来电秀';
            $identifier = 'com.nd.91show';
            $imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
            $breakjail = "（需越狱）";
        }elseif($appid==36){
            $appname = '91问医';
            $identifier = 'com.nd.hospitaladvisor';
            $imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
            $breakjail = "";
        }elseif($appid==35){
        	$appname = '91美发';
        	$identifier = 'com.nd.hair';
        	$imgurl = 'http://d.momo.im/icons/'.$appid.'/icon.png';
        	$breakjail = "";
        }else{
            $appname = '移动MOMO';
            $identifier = 'com.nd.momo';
            $imgurl = 'http://d.momo.im/icons/icon.png';
            $breakjail = "（需越狱）";
        }
        
        $imgurl_big = preg_replace('/(\.png)$/is', '@2x$1', $imgurl);
        
        $db = Database::instance();
        $query = $db->query("SELECT * FROM {$table} WHERE platform='iphone' AND appid={$appid} ORDER BY `id` DESC LIMIT 1");
        $res = $query->result_array(FALSE);
        if($res){
            if($appid==29){
                $r['download_url']=$res[0]['download_url'];
                $this->send_response(200,$r);
            }
            $r=$this->_get_package_url($res[0], 'ipa', $beta);
            
            $qstring = rtrim(strtr(base64_encode("{$appid}\t{$res[0]['version']}\t{$r['download_url']}"), '+/', '-_'), '=');
            $this->plist($qstring);
    	}else{
    	    $this->send_response(404,'','无安装包');
    	}
    }
    
    public function android(){
        $beta = (int)$_GET['beta'];
        $channel_cond = "";
        $table = $this->_get_upgrade_table($beta);
        
        $appid = (int)$this->appid;
        if(!$appid){
            $appid = (int)$_GET['appid'];
        }
        
        $db = Database::instance();
        if($this->getUid()){
        	if(!$beta){
        		$channel_cond = " AND channel= '3gmo'";
        	}
            $query = $db->query("SELECT * FROM {$table} WHERE platform='android' AND appid={$appid}{$channel_cond} ORDER BY `id` DESC LIMIT 1");
        }else{
        	if(!$beta){
        		$channel_cond = " AND channel= 'momo'";
        	}
            $query = $db->query("SELECT * FROM {$table} WHERE platform='android' AND appid={$appid}{$channel_cond} ORDER BY `id` DESC LIMIT 1");
        }
        $res = $query->result_array(FALSE);
        if($res[0]){
            $r=$this->_get_package_url($res[0], 'apk', $beta);
            $this->send_response(200,$r);
    	}else{
    	    $this->send_response(404,'','无安装包');
    	}
    }
    
    /**
     * 
     * Enter description here ...
     * @param array $package
     * @param string $type jad|ipa|apk
     */
    private function _get_package_url($package,$type,$beta=FALSE){
        if($this->getUid()){
            $fileidentify=(isset($package['brand_id']) && $package['brand_id']) 
                            ? "{$package['id']}_{$package['brand_id']}" 
                            : "{$package['id']}";
            $pathinfo=pathinfo($package['download_url']);
            $filename="{$fileidentify}/{$type}/{$pathinfo['basename']}";
            $req['uid']=$this->getUid();
            $req['timestamp']=time();
            $req['beta']=(int)$beta;
            $req['token']=md5($filename.$req['uid'].$req['timestamp'].$req['beta'].$this->key);
            $qs=http_build_query($req);
            $r['download_url']=url::site('client/download/'.$filename.'?'.$qs);
        }else{
            //如果是j2me并且有机型号
            if($type=='jad' && $package['brand_id']){
                $r['download_url']=preg_replace('@([^/]+)$@', 'm'.$package['brand_id'].'_$1', $package['download_url']);
            }else{
                $r['download_url']=$package['download_url'];
            }
        }
        
        return $r;
    }
    
    //发送下载客户端短信
    public function sms_client(){
        $mobile=$this->input->get('mobile');
        $mobile=ltrim($mobile.'', '0');
        if(!preg_match('/[1-9][0-9]{5,20}/', $mobile)){
            $this->send_response(400,'','40001:手机号码格式错误');
        }
        
        $zone_code=$this->input->get('zone_code','86');
        $zone_code=ltrim(intval($zone_code).'', '0+');
        
        $platform=$this->input->get('platform');
        if(!$platform){
            $this->send_response(400,'','40002:没有带平台参数');
        }
        
        $db = Database::instance();
        if($platform == 2){
            $query = $db->query("SELECT * FROM `upgrade` WHERE platform = 'iphone' AND channel='momo' ORDER BY `id` DESC LIMIT 1");
            $res = $query->result_array(FALSE);
        }elseif($platform == 1){
            $query = $db->query("SELECT * FROM `upgrade` WHERE platform = 'android' AND channel='momo' ORDER BY `id` DESC LIMIT 1");
            $res = $query->result_array(FALSE);
        }elseif($platform == 6){
            $query = $db->query("SELECT * FROM `upgrade` WHERE platform = 'j2me' AND signed=1 AND channel='momo' AND RIGHT(download_url, 3) = 'jad' ORDER BY `id` DESC LIMIT 1");
            $res = $query->result_array(FALSE);
        }else{
            $res=NULL;
        }
        
        if($res){
            $download_url=$res[0]['download_url'];
        }else{
            $this->send_response(400,'','40003:不支持的平台');
        }
        
        $day_start=strtotime(date("Y-m-d") . " 00:00:00")*1000;
        $day_end=strtotime(date("Y-m-d") . " 23:59:59")*1000;
        
        $query = $db->query("SELECT COUNT(0) AS total FROM im_smslog WHERE receiver_zonecode='$zone_code' AND receiver_mobile='$mobile' AND `timestamp` BETWEEN $day_start AND $day_end");
        $res = $query->result_array(FALSE);
        if($res && $res[0]['total']>2){
            $this->send_response(400,'','40005:不能对同一个手机号发送太多短信');
        }
        
        $download_url='http://m.momo.im/d/';
        
        $userModel=new User_Model();
        if($zone_code != '86'){
            $content="Download MOMO at ".$download_url;
        }else{
            $content="移动MOMO下载地址 ".$download_url;
        }
        if($userModel->sms_global($mobile,$content,$zone_code)){
            $this->send_response(200);
        }else{
            $this->send_response(400,'','40004:短信发送失败');
        }
    }
    
    //解析下载文件
    public function download($id,$filetype,$filename){
        //$fileinfo=explode('.', $filename);
        $beta=(int)$_GET['beta'];
        $upgrade_table = $this->_get_upgrade_table($beta);
        
        $fileinfo=array();
        $fileinfo[1]=$id;
        $fileinfo[2]=$filetype;
        $db = Database::instance();
        
        //根据id获取安装包信息
        $idinfo=explode('_', $id);
        
        $id=intval($idinfo[0]);
        if(isset($idinfo[1])){
            $sql="SELECT u.*,b.brand_id,b.upgrade_id FROM {$upgrade_table} u 
            LEFT JOIN `upgrade_brand` b ON (b.upgrade_id=u.id) 
            WHERE u.id = $id AND b.brand_id='$idinfo[1]' LIMIT 1";
        }else{
            $sql="SELECT u.*,b.brand_id,b.upgrade_id FROM {$upgrade_table} u 
            LEFT JOIN `upgrade_brand` b ON (b.upgrade_id=u.id) 
            WHERE u.id = $id LIMIT 1";
        }
        $query = $db->query($sql);
        $res = $query->result_array(FALSE);
        if($res){
            $downinfo=$res[0];
        }else{
            $this->send_response(400,'','40001无法下载');
        }
        
        if($fileinfo[2]=='jad' && preg_match('/\.jar$/i', $filename)){//获取jar包
            $brand_cond='';
            if($downinfo['brand_id']){
                //$brand_cond="AND b.brand_id='{$downinfo['brand_id']}' ";
            }
            $sql="SELECT u.*,b.brand_id,b.upgrade_id FROM {$upgrade_table} u 
            LEFT JOIN `upgrade_brand` b ON (b.upgrade_id=u.id) 
            WHERE u.platform = 'j2me' AND u.signed={$downinfo['signed']} AND u.channel='{$downinfo['channel']}' AND RIGHT(u.download_url, 3) = 'jar' $brand_cond
            ORDER BY u.`id` DESC LIMIT 1";
            $query = $db->query($sql);
            if($res = $query->result_array(FALSE)){
                $j2me=$res[0];
                $data=file_get_contents($j2me['download_url']);
                header("Content-Type: application/java-archive");
                header("Content-Length: ".strlen($data));
                exit($data);
            }else{
                $this->send_response(400,'','40001无法下载');
            }
        }
        
        $uid=$this->input->get('uid');
        $timestamp=$this->input->get('timestamp');
        $token=$this->input->get('token');
        $the_token=md5("{$fileinfo[1]}/{$filetype}/{$filename}".$uid.$timestamp.$beta.$this->key);
        if($token!=$the_token){//校验失败
            $data=file_get_contents($downinfo['download_url']);
            header("Content-Length: ".strlen($data));
            exit($data);
        }
        
        $userinfo=User_Model::instance()->get_user_info($uid);
        if(!$userinfo || !$userinfo['mobile']){//用户不存在或者无手机号
            $data=file_get_contents($downinfo['download_url']);
            header("Content-Length: ".strlen($data));
            exit($data);
        }
		$url_code = "";
		if((int)$downinfo['appid'] == 29)
		{
			$url_code = Url_Model::instance()->create('callshow', Kohana::config('uap.xiaomo'), '小秘-秀秀', $userinfo['uid'], $userinfo['realname'], $userinfo['mobile'],'86','','','',(int)$downinfo['appid']);
		}
		else
		{
			$url_code = Url_Model::instance()->create('sys', Kohana::config('uap.xiaomo'), '小秘', $userinfo['uid'], $userinfo['realname'], $userinfo['mobile']);	
		}
		
        if(!$url_code){
            $data=file_get_contents($downinfo['download_url']);
            header("Content-Length: ".strlen($data));
            exit($data);
        }
        if($fileinfo[2]=='jad'){
            $filecontent=file_get_contents($downinfo['download_url']);
            if($filecontent){
                $filecontent .= "AUTO_LOGIN: ".MO_SMS_JUMP.$url_code."\n";
                if($downinfo['brand_id']){
                    $filecontent .= "phone_model: ".$downinfo['brand_id']."\n";
                }
                header("Content-Type: text/vnd.sun.j2me.app-descriptor");
                header("Content-Length: ".strlen($filecontent));
                exit($filecontent);
            }else{
                $this->send_response(400,'','40002无法下载');
            }
        }elseif($fileinfo[2]=='ipa'){
            if(class_exists('ZipArchive')){
                $ipa_content=file_get_contents($downinfo['download_url']);
                
                $done=FALSE;
                if($ipa_content){
                    $filename=tempnam('/tmp', 'ipa_');
                    file_put_contents($filename, $ipa_content);
                    $appname = $this->_get_appname($downinfo['appid']);
                    
                    $zip = new ZipArchive();
                    if($zip->open($filename, ZIPARCHIVE::CREATE)){
                        $zip->addFromString("Payload/{$appname}.app/assets/auth.json", '{"autoLogin":"'.MO_SMS_JUMP.$url_code.'"}');
                        $zip->close();
                        $done=TRUE;
                    }
                }
                if($done){
                    $filecontent=file_get_contents($filename);
                    @unlink($filename);
                }else{
                    $this->send_response(400,'','40002无法下载');
                }
            }else{
                $filecontent=file_get_contents($downinfo['download_url']);
                if(!$filecontent) $this->send_response(400,'','40002无法下载');
            }
            
            header("Content-Type: application/x-zip");
            header("Content-Length: ".strlen($filecontent));
            exit($filecontent);
        }elseif($fileinfo[2]=='apk'){
            if(class_exists('ZipArchive')){
                $apk_content=file_get_contents($downinfo['download_url']);
                
                $done=FALSE;
                if($apk_content){
                    $filename=tempnam('/tmp', 'apk_');
                    file_put_contents($filename, $apk_content);
                    
                    $zip = new ZipArchive();
                    if($zip->open($filename, ZIPARCHIVE::CREATE)){
                        $zip->addFromString('assets/auth.json', '{"autoLogin":"'.MO_SMS_JUMP.$url_code.'"}');
                        $zip->close();
                        $done=TRUE;
                    }
                }
                
                if($done){
                    $keyfile=DOCROOT."_tools/momo.key";
                    $filename_signed=$filename.'_signed';
                    if((int)$downinfo['appid'] == 29)
                    {
                    	$keyfile=DOCROOT."_tools/momoshow.key";
                    	exec("/usr/bin/jarsigner -verbose -keystore $keyfile -keypass momo.android -storepass momo.android $filename momoshow");
                    }
                	else 
                	{
                		exec("/usr/bin/jarsigner -verbose -keystore $keyfile -keypass momo.android -storepass momo.android $filename momo");
                	}
                    
                    exec(DOCROOT."_tools/zipalign -v 4 $filename $filename_signed");
                    
                    $filecontent=file_get_contents($filename);
                    @unlink($filename);
                    @unlink($filename_signed);
                }else{
                    $this->send_response(400,'','40002无法下载');
                }
            }else{
                $filecontent=file_get_contents($downinfo['download_url']);
                if(!$filecontent) $this->send_response(400,'','40002无法下载');
            }
			
            header("Content-Type: application/vnd.android.package-archive");
            header("Content-Length: ".strlen($filecontent));
            exit($filecontent);
        }
    }
    
    public function statis()
    {
    	$data = $this->get_data();
    	$platform = $data['source'];
    	$channel = $data['install_id'];
    	$brand = $data['brand'];
    	$phone_model = $data['phone_model'];
    	if ($brand == 'kjava') {
    		
    	}
    	try {
    		$appDownload = new App_download_Model();
    		$appDownload->updateDownloadRecord($platform, $channel, $brand, $phone_model);
    		$return['msg'] = "success";
    		$this->send_response(200,$return,'');
    	} catch (Exception $e) {
    		$this->send_response(400,'','下载记录添加失败');
    	}
    	
    	exit;
    }
    
}