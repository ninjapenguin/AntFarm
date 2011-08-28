<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Worker extends Controller {

	public function action_index()
	{
		// Purge and terminate ob
		while (ob_get_level()) ob_end_flush();

		# Create our worker object.
		$gearman_mworker= new GearmanWorker;

		# Add default server (localhost).
		$gearman_mworker->addServer();

		# Register function "reverse" with the server. Change the worker function to
		# "reverse_fn_fast" for a faster worker with no output.
		$gearman_mworker->addFunction("make_request", array($this, "worker"));

		while($gearman_mworker->work())
		{
			if ($gearman_mworker->returnCode() != GEARMAN_SUCCESS)
			{
				echo "return_code: " . $gearman_mworker->returnCode() . "\n";
				break;
			}
		}
	}

	public function worker($job)
	{
		echo " << SERVING " . $job->workload() . "\n";

		$arr_pieces = explode('#', $job->workload());

		// Assign the data
		$str_uri = $arr_pieces[0];
		parse_str($arr_pieces[1], $arr_post);

		// Create and execute the request..
		$str_data =  Request::factory($str_uri)
			->post($arr_post)
			->execute()
			->body();

		return $str_data;
	}
}