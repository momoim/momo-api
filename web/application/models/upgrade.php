<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [UAP Portal] (C)1999-2010 ND Inc.
 * 通讯录模型文件
 */
class Upgrade_Model extends Model
{

    /**
     * 构造函数
     */
    public function __construct ()
    {
        parent::__construct();
    }

    public function get_platform_data ($platform, $signed = 1, $install_id = '', 
		$phone_model = '', $is_beta = null, Array $otherInfo)
    {
        $return = array();
        if ($platform == 'j2me') 
	{
        	// j2me平台升级包jad文件返回
        	if (isset($phone_model) && !empty($phone_model)) {
        		$upgradeBrandObj = Upgrade_brand_Model::getInstance();
        		$dlIds = $upgradeBrandObj->getDlId($phone_model);
        		$sql = <<<EOQ
		        	SELECT a.`id`,a.`platform`, a.`version`,a.`publish_date`,
		        		a.`download_url`,a.`remark`,a.`force_update`, 
		        		a.`view_version`, a.`file_size`, a.`channel`,
		        		UB.brand_id
		        	FROM `upgrade` a, upgrade_brand AS UB
		        	WHERE (a.`ug_ext` = 'jad' OR RIGHT(a.download_url, 3) = 'jad') 
		        		AND a.id = UB.upgrade_id AND UB.brand_id = '$phone_model' 
EOQ;
        	}
        	
        	if (!isset($phone_model) || empty($phone_model)) {
        		$sql = <<<EOQ
        		SELECT a.`id`,a.`platform`, a.`version`,a.`publish_date`,
		        		a.`download_url`,a.`remark`,a.`force_update`, 
		        		a.`view_version`, a.`file_size`, a.`channel`
		        FROM `upgrade` a 
		        WHERE (a.`ug_ext` = 'jad' OR RIGHT(a.download_url, 3) = 'jad') 
		        AND mobile_brand = 0
EOQ;
        	}

        	if (!isset($phone_model) || empty($phone_model)) {
        		if (isset($signed)) {
        			if ($signed == 0 || $signed == false) {
        				$sql .= " AND a.signed = 0 ";
        			}
        			elseif (!empty($signed)) {
        				$sql .= " AND a.signed = 1 ";
        			} else {
        				$sql .= " AND a.signed = 1 ";
        			}
        		} else {
        			$sql .= " AND a.signed = 1 ";
        		}
        	} else {
        		
        	}
        	
        	if ($install_id != '') {
        		// 如果有传递渠道，则增加渠道条件
        		$sql .=	" AND a.channel = \"$install_id\"";
        	} else {
        		// 默认为momo
        		$sql .= " AND a.channel = 'momo'";
        	}

		if (!is_null($otherInfo['appid']))
		{
			$sql .= <<<EOQ
			 AND appid = '{$otherInfo['appid']}'
EOQ;
		}
		else
		{
			$sql .= <<<EOQ
			 AND (appid = 0 OR appid is null)
EOQ;
			
		}
		
        	// 是否内测，1：是，0：不是
			if ($is_beta !== null) {
				 $sql .= " AND a.alpha = $is_beta";
			}
		
			$sql .= " ORDER BY publish_date DESC, `version` DESC LIMIT 1";
		
        } else {
		// 除了J2ME外的其他平台
        	$sql = <<<EOQ
        	SELECT * FROM upgrade WHERE platform = '$platform' AND patch=0
EOQ;
		if ($install_id != '') 
		{
			$sql .=	" AND channel = \"$install_id\"";
		}

		if (!is_null($otherInfo['appid']))
		{
			$sql .= <<<EOQ
			 AND appid = '{$otherInfo['appid']}'
EOQ;
		}
		else
		{
			$sql .= <<<EOQ
			 AND (appid = 0 OR appid is null)
EOQ;
			
		}

			if ($phone_model != '') {
				$sql .= " AND mobile_brand = \"$phone_model\"";
			}

			if ($is_beta !== null) {
				 $sql .= " AND alpha = $is_beta";
			}
			$sql .=  " ORDER BY publish_date DESC, `version` DESC LIMIT 1";
        }

        $query = $this->db->query($sql);  
	$resultCount = $query->count();

	// 如果有传递appid，但是该应用不存，直接返回结果，不进行二次查询
	if (!is_null($otherInfo['appid']) && $resultCount === 0)
	{
		//d($query->count());exit;
		$firstQyRes = array();
		$firstQyRes['appid_not_exist'] = TRUE;
		return $firstQyRes;
	}

        if ($query->count()) 
	{
            $result = $query->result_array(FALSE);
            $return = $result[0];
            
            // 设置了机型，并且机型字段不为空值（NULL, null, 0, "", '', false）
            if (isset($phone_model) && !empty($phone_model)) {
            	$maxVersion = $this->getMaxVersForPltChnl($platform, $install_id);
            	if (!$return || $return['version'] < $maxVersion) {
            		// 返回通用包
            		// echo $return['version'] . ' ' . $maxVersion;exit;
            		$return = $this->get_platform_data ($platform, $signed, $install_id, '', $is_beta);
            	} elseif ($platform == 'j2me') {
            	$dlUrlLastPart = substr(strrchr($return['download_url'],'/'), 1);
            	
            	$pos = strrpos($return['download_url'],'/');
            	
            	$return['download_url'] = substr($return['download_url'], 0, $pos) . DIRECTORY_SEPARATOR . 'm' . $phone_model . '_' . $dlUrlLastPart;
            	} else {
            		;
            	}
            }
        }

        if (!$query->count() && (isset($phone_model) && !empty($phone_model))) {
        	$return = $this->get_platform_data ($platform, $signed, $install_id, '', $is_beta);
        }
        if (!$return) {
        	return false;
        }
        if ($platform == 'j2me') {
        	$jarFileSize = $this->getJarSize($return['version'], $return['publish_date']);
        	$return['file_size'] = $jarFileSize;
        }
        
        return $return;
    }
    
    /**
     * 根据版本，发布日期取得jar文件大小
     * 
     * @param float $version
     * @param int $publishDate
     */
    public function getJarSize($version, $publishDate) {
    	if (!isset($publishDate)) {
    		$publishDate = time();
    		$sql = <<<EOQ
    		SELECT a.`file_size` as fs
		        FROM `upgrade` a 
		        WHERE (a.`ug_ext` = 'jar' OR RIGHT(a.download_url, 3) = 'jar') 
		        AND a.publish_date < $publishDate
EOQ;
    	} else {
    	$sql = <<<EOQ
        		SELECT a.`file_size` as fs
		        FROM `upgrade` a 
		        WHERE (a.`ug_ext` = 'jar' OR RIGHT(a.download_url, 3) = 'jar') 
		        AND a.publish_date = $publishDate 
EOQ;
    	}
    	$query = $this->db->query($sql); 
    	$result = $query->result_array(FALSE); 
    	$return = $result[0];
    	return $return['fs'];
    }

    public function get_max_version ($platform)
    {
        $return = array();
        $query = $this->db->query(
        "SELECT max(version) as version FROM upgrade WHERE platform = '$platform'");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            $return = $result[0]['version'];
        }
        $return = (float)sprintf("%.3f", $return);
        return $return;
    }

    public function getMaxVersForPltChnl ($platform, $channel)
    {
        $return = array();
        $query = $this->db->query(
        "SELECT max(version) as version FROM upgrade WHERE platform = '$platform' AND channel = '$channel'");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            $return = $result[0]['version'];
        }
        $return = (float)sprintf("%.3f", $return);
        return $return;
    }
    
    
    public function get_platform_patch ($platform, $version, $max_patch_version)
    {
        $return = array();
        $query = $this->db->query(
        "SELECT * FROM upgrade WHERE platform = '$platform' AND version='$max_patch_version' AND pre_version='$version' AND patch=1 ORDER BY id DESC LIMIT 1");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            $return = $result[0];
        }
        return $return;
    }

    /**
     * 强制升级检测
     * @param int $source 数据来源
     * @param string $version 客户端版本
     * @return bool 是否需要强制升级
     */
    public static function check_is_force_update ($source, $version)
    {
        $db = Database::instance();
        $support_platform = Kohana::config('upgrade.platform');
        $platform = isset($support_platform[$source]) ? $support_platform[$source] : '';
        //j2me
        if ($source == 6) {
            $sign_sql = ' AND signed = 1 ';            
        } else {
            $sign_sql = '';
        }
        $query = $db->query(
        "SELECT * FROM `upgrade` WHERE `platform` = '$platform' AND `force_update` = 1" . $sign_sql . " ORDER BY id DESC LIMIT 1");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            if($result[0]['version'] > $version) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * 取得所有平台的最新版
     */
    public function get_all_platform ($channel = "momo", $appid = 0)
    {
    	$sql = <<<EOQ
    	SELECT * 
        FROM (SELECT * FROM `upgrade` WHERE `patch`=0 AND channel = "$channel" AND appid = "$appid" AND platform <> 'j2me' ORDER BY `id` DESC) tmp 
        GROUP BY `platform`
EOQ;
    	
        $query = $this->db->query($sql);
        $result = $query->result_array(FALSE);
        
        // $channel如果为"3gmo"，则改为"momo"
        if ($channel == "3gmo") {
        	$channel = "momo";
        }
        $sql = <<<EOQ
        SELECT * FROM (SELECT *, RIGHT(download_url, 3) AS ext 
        	FROM `upgrade` WHERE `patch`=0 AND channel = "$channel" AND platform = 'j2me' AND mobile_brand = 0 
        	ORDER BY signed DESC, `version` DESC, publish_date DESC) tmp GROUP BY ext,signed
EOQ;

        $query = $this->db->query($sql);
        $res = $query->result_array(FALSE);
        if ($res) {
        	if (is_array($res)) {
	        	foreach ($res as $j2meAppid => $j2meApp) {
	        		if ($j2meApp['ext'] == 'jad' ) {
	        			$jarFileSize = $this->getJarSize($j2meApp['version'], $j2meApp['publish_date']);
	        			$res[$j2meAppid]['file_size'] = $jarFileSize;
	        		}
	        	}
        	}
        	//echo Kohana::debug($res);exit;
        }
        $result = array_merge($result, $res);
        
        if (!isset($result[0]) || empty($result[0])) 
         	return false;
        return $result;
    }
    
    public function getAppInfo($channel = "momo", $platform = "android", $signed = 0, $ext='rar')
    {
    	if ($platform != 'j2me') {
	    	$sql = <<<EOQ
	    	SELECT * FROM `upgrade` WHERE `patch`=0 AND channel = "$channel" AND platform = "$platform" ORDER BY `version` DESC, publish_date DESC LIMIT 1
EOQ;
	        $query = $this->db->query($sql);
	        $result = $query->result_array(FALSE);
    	} else {
	        $sql = <<<EOQ
	        SELECT * FROM `upgrade` WHERE platform = 'j2me' AND channel = '$channel' AND RIGHT(download_url, 3) = '$ext' AND platform = "$platform" AND signed = $signed ORDER BY `id` DESC LIMIT 1
EOQ;

	        $query = $this->db->query($sql);
	        
	        $result = $query->result_array(FALSE);
    	}
        
        return $result;
    }
    
    /**
     * 获取j2me网站压缩包
     * 
     */
    public function getJ2mePkg($phoneModel = 0, $signed = 1, $alpha = 0) {
    	if (!is_numeric($phoneModel)) {
    		$phoneModel = 0;
    	}
    	if (!is_numeric($signed)) {
    		$signed = 1;
    	}
    	if (!is_numeric($alpha)) {
    		$alpha = 0;
    	}
    	$sql = <<<EOQ
    		SELECT * FROM upgrade 
    		WHERE platform = 'j2me' AND channel = 'momo' AND signed = $signed AND alpha = $alpha 
    		AND (ug_ext = 'jar' OR RIGHT(download_url, 3) = 'jar')  
EOQ;

    	if (empty($phoneModel)) {
    		$sql .= "AND mobile_brand = 0";
    	} else {
    		$sql =<<<EOQ
    		SELECT * FROM upgrade, upgrade_brand
    		WHERE platform = 'j2me' AND channel = 'momo' AND alpha = $alpha 
    		AND (ug_ext = 'jar' OR RIGHT(download_url, 3) = 'jar') AND brand_id = $phoneModel
    		AND upgrade.id = upgrade_brand.upgrade_id
EOQ;
    	}
    	
    	$sql .= " ORDER BY version DESC, publish_date DESC LIMIT 1";
    	
    	$query = $this->db->query($sql);
	    
	    $result = $query->result_array(FALSE);
	    
	    $customerVersion = (float)sprintf("%.3f", $result[0]['version']);
	    $maxVersion = $this->getMaxVersForPltChnl('j2me','momo');
	    
	    $mongoLog = new Log_Model();
	    $logMsg = Kohana::debug($result);
	    $logData = array(
	    	'content' => 'momoapi: ' . $logMsg,
	    	'source' => 0
	    );

	    if (empty($result[0]) || ($maxVersion > $customerVersion)) {
	    	// 返回通用包
	    	$result = $this->getJ2mePkg(0, $signed, $alpha);
	    	
	    	if (!$result) {
	    		$result = $this->getJ2mePkg(0, 1, 0);
	    	}
	    	$result[0]['customer'] = false;
	    	// echo Kohana::debug($result);exit;
	    } else {
	    	$result[0]['customer'] = true;
	    }

	    return $result;
    }
    
    public function setJ2meUrl($appInfo, $phoneModel = 0, $isSigned = 1, $isAlpha = 0) {
        $appInfo = $appInfo[0];
        if ($appInfo['customer'] && !empty($phoneModel)) {
        	// 处理定制包链接
        	$dlUrlLastPart = substr(strrchr($appInfo['download_url'],'/'), 1);
            	
            $pos = strrpos($appInfo['download_url'],'/');
            	
            $appInfo['download_url'] = substr($appInfo['download_url'], 0, $pos) . DIRECTORY_SEPARATOR . 'm' . $phoneModel . DIRECTORY_SEPARATOR . 'm' .$phoneModel . '_sign_' .$isSigned . '_alpha_' . $isAlpha . '_MoMo.zip';	
        } else {
        	// 处理通用包链接
        	$dlUrlLastPart = substr(strrchr($appInfo['download_url'],'/'), 1);
            	
            $pos = strrpos($appInfo['download_url'],'/');
            	
        	$appInfo['download_url'] = substr($appInfo['download_url'], 0, $pos) . DIRECTORY_SEPARATOR . 'j2me' . '_sign_' . $isSigned . '_alpha_' . $isAlpha . '_MoMo.zip';	
       }
       return $appInfo;
    }
    
    public function getUpgradeBeta($appid,$platform,$version) {
            $sql = "SELECT `appid`, `platform`, `version`, `publish_date`, `file_size`, `download_url`, `remark` FROM upgrade_beta 
            WHERE appid={$appid} AND platform='{$platform}' AND version>{$version} ORDER BY version DESC LIMIT 1";

            $query = $this->db->query($sql);
            $result = $query->result_array(FALSE);
            
            if($result[0]) {
                $res = $result[0];
                
                if($platform == 'iphone' && preg_match('/\.ipa$/is', $res['download_url'])){
                    $qstring = rtrim(strtr(base64_encode("{$appid}\t{$res['version']}\t{$res['download_url']}"), '+/', '-_'), '=');
                    $res['plist'] = url::base()."client/plist/{$qstring}";
                    if($appid == 35){
                    	//$res['plist'] = 'http://meiyegj.com/router/proxy.php?url=' . urlencode($res['plist']);
                    }
                }
                return $res;
            }
            return ;
    }
}
