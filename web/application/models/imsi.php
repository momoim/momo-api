<?php defined('SYSPATH') OR die('No direct access allowed.');

class Imsi_Model extends Model {
    
    public static $instances = null;
    
    public static function instance(){
        if ( !is_object(self::$instances) ){
            self::$instances = new Imsi_Model();
        }
        return self::$instances;
    }
    /**
     * imsi与mobile绑定
     * @param int $type 关联类型
     * @param string $imsi 手机卡imsi
     * @param string $mobile 手机号
     * @param string $zone_code 区号
     * @param boolean $can_login 是否可登录
     * @return boolean
     */
    public function link_imsi_mobile($type,$imsi,$mobile,$zone_code='86',$can_login=0){
        if(!$imsi || !$mobile){
            return FALSE;
        }
        $type = $type?1:0;
        $can_login = $can_login?1:0;
        
        //上行短信关联重新关联
        if($type == 1){
            $this->del_link($imsi,$mobile,$zone_code);
        }
        
        $set = array(
                'type'=>$type,
                'imsi'=>$imsi,
                'mobile'=>$mobile,
                'zone_code'=>$zone_code,
                'can_login'=>$can_login,
                'link_date'=>time(),
        );
        
        try{
            return $this->db->insert('imsi_mobile_link',$set);
        }catch(Exception $e){
            return FALSE;
        }
        
    }
    
    /**
     * 根据imsi或者mobile获取关联记录
     * @param string $imsi
     * @param string $mobile
     * @param string $zone_code
     * @return array
     */
    public function get_link($imsi='',$mobile='',$zone_code='86'){
        $r = array();
        if($imsi){
            $sql = 'SELECT * FROM imsi_mobile_link WHERE imsi='
                    . $this->db->escape($imsi);
        }elseif($mobile){
            if(!$zone_code){
                $zone_code = '86';
            }
            $sql = 'SELECT * FROM imsi_mobile_link WHERE mobile='
                    . $this->db->escape($mobile)
                    . ' AND zone_code='
                    . $this->db->escape($zone_code);
        }else{
            return $r;
        }
        
        $query = $this->db->query($sql);
        
        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
        }
        
        return $r;
    }
    
    /**
     * 根据imsi或者mobile删除关联记录
     * @param string $imsi
     * @param string $mobile
     * @param string $zone_code
     * @return boolean
     */
    public function del_link($imsi='',$mobile='',$zone_code='86'){
        if(!$zone_code){
            $zone_code = '86';
        }
        
        if($imsi && $mobile){
            $sql = 'DELETE FROM imsi_mobile_link WHERE imsi='
                    . $this->db->escape($imsi)
                    . ' OR (mobile='
                    . $this->db->escape($mobile)
                    . ' AND zone_code='
                    . $this->db->escape($zone_code)
                    . ')';
        }elseif($imsi){
            $sql = 'DELETE FROM imsi_mobile_link WHERE imsi='
                    . $this->db->escape($imsi);
        }elseif($mobile){
            $sql = 'DELETE FROM imsi_mobile_link WHERE mobile='
                    . $this->db->escape($mobile)
                    . ' AND zone_code='
                    . $this->db->escape($zone_code);
        }else{
            return FALSE;
        }
        
        return $this->db->query($sql);
    }

}