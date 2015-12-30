<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 搜索控制器文件
 */

class Search_Controller extends Controller
{
    /**
     * 构造函数
     */
    public function __construct ()
    {
        parent::__construct();
        $this->user_id = $this->getUid();
    }
    
	/**
     * 
     * 话题搜索
     * @param $query
     */
    public function topic($query=NULL) {
    	if(empty($query)) 
    		$this->send_response(400, NULL, '400111:搜索内容为空');
    	$start = $this->input->get ( 'start', 0 );
    	$group_id = $this->input->get ( 'group_id', 0 );
    	$pagesize = $this->input->get ( 'pagesize', 20 );
    	$query = '#'.$query.'#';
    	$this->search($query, $start, $group_id, $pagesize);
    }
    
    /**
     * 
     * 搜索动态
     * @param $query
     */
    public function statuses($query=NULL) {
    	if(empty($query)) 
    		$this->send_response(400, NULL, '400111:搜索内容为空');
    	$start = $this->input->get ( 'start', 0 );
    	$group_id = $this->input->get ( 'group_id', 0 );
    	$pagesize = $this->input->get ( 'pagesize', 20 );
    	$query = '"'.$query.'"';
    	$this->search($query, $start, $group_id, $pagesize);
    }
    
    /**
     * 
     * 搜索
     * @param string $query
     * @param int $start
     * @param int $group_id
     * @param int $pagesize
     */
    private function search($query,$start,$group_id,$pagesize) {
    	$options = array(
            'offset'   => $start,
            'limit'    => $pagesize
        );
        if($group_id)
        	$options['group_id'] = $group_id;
    	$ids = $this->sphinx_query($query, $options);
        $cursor = $this->mongo_query($ids);
        if (is_null($cursor)) 
           return 0;
        $result = array();
        $i=0;
        while ($cursor->hasNext()) {
            $doc = $cursor->getNext();
            $result[] = Feed_Model::instance()->new_feedview($doc,1,$this->source);
            $i++;
        }
        return $this->send_response(200, array('data'=>$result));
    }
    
    /**
     * 
     * 构造sphinx查询
     * @param unknown_type $query
     * @param unknown_type $options
     */
    private function sphinx_query($query, $options) {
        $offset = $options['offset'];
        $limit = $options['limit'];
        $group_id = (isset($options['group_id']) && $options['group_id']>0)?$options['group_id']:'';
        $mix_ids = $this->get_user_mix_ids();
        $sphinx = $this->sphinx_instance(); 
        if ($group_id) 
            $sphinx->setFilter("group_id", array('group_id'=>$group_id));
        if (count($mix_ids) > 0) {
            $mix_id_filters = array();
            foreach ($mix_ids as $mix_id) {
                $crc = sprintf('%u',crc32($mix_id));
                array_push($mix_id_filters, $crc);
            }
            $sphinx->setFilter("mix_id", $mix_id_filters);
        }
        $ids = array();
        //$indexes = array( "momo_main", "momo_delta_l1", "momo_delta_l2" );
        //foreach ($indexes as $index) {
        $ids = $this->sphinx_search($sphinx, $query,"momo",$offset, $limit);
        //    $ids = array_merge($ids, $results);
        //}
        $sphinx->close();
        $count = count($ids);
        if ($count <= 0) {
        	if($offset > 0)
        		$this->send_response(200, array());
        	else 
        		$this->send_response(400, NULL, '400111:No doc matches'.$query);
        }
           
        return $ids;
    }
    
    /**
     * 
     * sphinx搜索
     * @param object $sphinx
     * @param string $query
     * @param string $index
     */
    private function sphinx_search($sphinx, $query, $index,$offset, $limit) {
        $sphinx->setMatchMode(SPH_MATCH_BOOLEAN);
        $sphinx->setSortMode(SPH_SORT_EXTENDED,'created_at DESC');
        $sphinx->setLimits($offset, $limit);
        $result = $sphinx->Query($query, $index);
        $ids = array();
        if (is_null($result)) 
        	$this->send_response(400, NULL, '400111:Null result returned'.$sphinx->GetLastError());
        if (!array_key_exists('matches', $result))
            return $ids;
        $matches = $result['matches'];
        foreach ($matches as $id => $match) {
            $doc = $match['attrs'];
            $id = sprintf("%08x%08x%08x%08x",
               $doc['id1']+0, $doc['id2']+0,
               $doc['id3']+0, $doc['id4']+0
            );
            array_push($ids, strtolower($id));
        }
        return $ids;
    }
    
    /**
     * 
     * mongo查询
     * @param array $ids
     */
    private function mongo_query($ids) {
        $collection = $this->mongo_instance();
        $cursor = $collection->find(
            array ('_id' => array( '$in' => $ids ))
        )->sort ( array ('last_updated' => - 1 ) );

        if (is_null($cursor)) 
            $this->send_response(400, NULL, '400111:Null cursor returned');
        return $cursor;
    }
    
    /**
     * 
     * mongo instance
     */
    private function mongo_instance() {
        $mongo_instance = new MongoClient ( Kohana::config ( 'uap.mongodb' ) );
        return $mongo_instance->selectDB ( MONGO_DB_FEED )->selectCollection ( 'feed_new' );
    }

    /**
     * 
     * sphinx instance
     */
    private function sphinx_instance() {
        $sphinx = new SphinxClient();
        $sphinx->setServer(Kohana::config ( 'uap.sphinx_host' ), Kohana::config ( 'uap.sphinx_port' ));
        if (!$sphinx->open())
            $this->send_response(400, NULL, '400111:cannot connect to sphinx daemon'.$sphinx->GetLastError());
        return $sphinx;
    }
    
    /**
     * 
     * 获取用户的mix_id
     */
    private function get_user_mix_ids() {
    	$uids = array();
    	//联系人
		$uids = Friend_Model::instance()->get_user_link_cache ( $this->user_id );
		//群组
		$group_array = Group_Model::instance()->getUserAllGroupId($this->user_id);
		if($group_array) {
			foreach ($group_array as $group)
				$uids[] = '1_'.$group['gid'];
		}
		array_push($uids, $this->user_id);
		return $uids;
    }
    
    
}