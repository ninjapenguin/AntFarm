<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Updater extends Controller_Farmable {

	protected $_workable_limit = 2;
	protected $_worker_count = 3;
	protected $_data_key = 'data';

	public function action_index()
	{
		$this->response->body(json_encode($this->request->post()));
	}
}