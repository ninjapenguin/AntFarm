<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Farmable extends Controller {

	/**
	 * number of items $_POST[$this->get_key()] should be distributed for
	 * @var int
	 */
	protected $_workable_limit = 2;

	/**
	 * Number of workers to distribute this request via
	 * @var int
	 */
	protected $_worker_count = 3;

	/**
	 * Name of key within which data is held
	 * @var string
	 */
	protected $_key_name = 'data';

	/**
	 * worker responses
	 * Responses received from ants
	 * @var Array
	 */
	protected $_responses = array();

	/**
	 * Divides the passed workload and handles the division of workload between
	 * multiple instances
	 */
	public function before()
	{
		// If original request and above the workable limit then we
		//  look to parallelise the work

		$m_target = $this->request->post($this->_key_name);

		if
		(
			$this->request->is_initial()
			AND isset($m_target)
			AND is_array($m_target)
			AND count($m_target) >= $this->_workable_limit
		)
		{
			// Instantiate gearman client
			$obj_gearman = new GearmanClient;
			$obj_gearman->addServer();

			// Divide the work into $this->_worker_count chunks for processing
			$int_chunk_size = round( count($m_target) / $this->_worker_count );

			$arr_chunks = array_chunk( $m_target, $int_chunk_size );

			// Reverse the route..
			$str_route = $this->request->uri();

			// Update the controller action to our own nullifier
			$this->request->action('nullifier');

			// Schedule each of the requests
			$c = 0;
			foreach ($arr_chunks as $chunk) {

				// Format the string to be passed to the worker by formatting the post
				$arr_d = $_POST;
				$arr_d[$this->_key_name] = $arr_chunks[$c];

				$str_data = $str_route . "#" . http_build_query($arr_d);

				$obj_gearman->addTask('make_request', $str_data);
				$c++;
			}

			// Set the complete requests callback
			$obj_gearman->setCompleteCallback(array($this,"complete"));

			// Execute the requests
			$obj_gearman->runTasks();
		}
	}

	final public function action_nullifier()
	{
		// Decode the responses so as to merge
		$arr_decoded = array();
		foreach ($this->_worker_responses as $str_resp) {
			$arr_decoded[] = json_decode($str_resp,1);
		}

		// Combine the responses and return!
		return $this->response->body(json_encode(call_user_func_array("arr::merge", $arr_decoded)));
	}

	final public function complete($task)
	{
		$this->_worker_responses[] = $task->data();
	}
}