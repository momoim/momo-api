<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2011 ND Inc.
 */
class Stat_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
	}
	
	public function update_url_click() {
		$id = ( int )$this->input->get("id", 1);
		
		$urlClickStatisM = Url_click_statis_Model::getInstance();
		$urlClickStatisM->update($id);
	}
}