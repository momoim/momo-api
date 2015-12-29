<?php
namespace Driver;

use Core;
use Uploader;
use MongoLogger;

class NDCS {
	
	static $NDCS_PATH = '/data/ndcs/';
	
	public static function gen_filename($filename)
	{
		//like M00/FD/17/CgHy9VJo4Bvx2jPGAA6n1qC3idw8623666
		$filename = str_replace('/', '_', $filename);
		$hash = strtoupper(md5(microtime()));
		
		$ndcs_path = 'N00/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
		if (!is_dir(self::$NDCS_PATH . $ndcs_path))
		{
		    mkdir(self::$NDCS_PATH . $ndcs_path, 0777, true);
		}
		
		$ndcs_filename = $ndcs_path . '/' . substr($hash, 4) . $filename;
		
		return $ndcs_filename;
	}
	
	public static function download_file_to_file($fs_group, $fs_file, $local_file, $offset=0, $length=0)
	{
		
		/**
		 * 从旧的fastdfs目录搜索文件
		 * 去掉类似 M00/ 的4个前缀
		 */
		$ndcs_file = self::$NDCS_PATH . $fs_file;
		
		if(file_exists($ndcs_file)){
			$ndcs_f = fopen($ndcs_file, 'rb');
			
			if($ndcs_f){
				$local_f = fopen($local_file, 'wb');
				
				fseek($ndcs_f, $offset);
				
				if($length > 0){
					$getneed = TRUE;
				}else{
					$getneed = FALSE;
				}
				
				while(!feof($ndcs_f)){
					$datalen = 8096;
					
					if($getneed){
						//已经取了 $length 字节，退出
						if($length <= 0){
							break;
						}

						if($length < $datalen){
							$datalen = $length;
						}
						
						$length -= $datalen;
					}
					
					$data = fread($ndcs_f, $datalen);
					fwrite($local_f, $data);
				}
				
				fclose($local_f);
				fclose($ndcs_f);
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public static function upload_by_filename($local_file)
	{
		
		if(file_exists($local_file)){
			$local_f = fopen($local_file, 'rb');
			
			if($local_f){
				$pathinfo = pathinfo($local_file);
				$filename = $pathinfo['basename'];
				$fs_file = self::gen_filename($filename);
				
				$ndcs_file = self::$NDCS_PATH . $fs_file;
				$ndcs_f = fopen($ndcs_file, 'wb');
				
				if($ndcs_f){
					while(!feof($local_f)){
						$data = fread($local_f, 8096);
						fwrite($ndcs_f, $data);
					}
					
					fclose($ndcs_f);
					fclose($local_f);
					
					return array(
							'group_name' => 'group1',
							'filename' => $fs_file,
					);
				}
					
				fclose($local_f);
			}
		}
		
		return NULL;
	}
	
	public static function download_file_to_buff($fs_group, $fs_file)
	{
		$ndcs_file = self::$NDCS_PATH . $fs_file;
		
		if(file_exists($ndcs_file)){
			$ndcs_f = fopen($ndcs_file, 'rb');
			if($ndcs_f){
				$contents = stream_get_contents($ndcs_f);
				
				fclose($ndcs_f);
				return $contents;
			}
		}
		
		return NULL;
	}

}