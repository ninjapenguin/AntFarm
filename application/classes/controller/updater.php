<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Updater extends Controller_Farmable {

	protected $_workable_limit = 2;
	protected $_worker_count = 3;
	protected $_data_key = 'data';

	public function action_index()
	{
		// Grab the data posted to us and affect in some way..
		$arr_data = $this->request->post();

		// Just update the data to show that we really have been here!!
		foreach ($arr_data[$this->_data_key] as $key => &$value) {
			sleep(1);
			$value = "affected: {$value}";
		}

		$this->response->body(json_encode($arr_data));
	}
}