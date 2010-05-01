<?php
/**
 * Port of PHP Quick Profiler by Ryan Campbell
 * Original URL: http://particletree.com/features/php-quick-profiler
 */
class Profiler_Profiler {
	/**
	 * Holds log data collected by Profiler_Console
	 * @var array
	 */
	public $output = array();

	/**
	 * Holds config data passed inot the constructor
	 * @var array
	 */
	public $config = array();
	
	/**
	 * The list of query types we care about for type specific stats
	 *
	 * @var array 
	 *
	 */
	protected $_queryTypes = array('select', 'update', 'delete', 'insert');

	/**
	 * Sets the configuration options for this object and sets the start time.
	 *
	 * Possible configuration options include:
	 * <ul>
	 * <li><strong>query_explain_callback:</strong> Callback used to explain queries. Follow format used by call_user_func</li>
	 * </ul>
	 *
	 * @param array $config List of configuration options
	 * @param int $startTime Time to use as the start time of the profiler
	 */
	public function __construct(array $config = array(), $startTime = null) {
		if (is_null($startTime)) {
			$startTime = microtime(true);
		}

		$this->startTime = $startTime;
		$this->config = $config;
	}

	/**
	 * Shortcut for setting the callback used to explain queries.
	 *
	 * @param string|array $callback
	 */
	public function setQueryExplainCallback($callback) {
		$this->config['query_explain_callback'] = $callback;
	}

	/**
	 * Shortcut for setting the callback used to interact with the MySQL
	 * query profiler.
	 *
	 * @param string|array $callback
	 */
	public function setQueryProfilerCallback($callback) {
		$this->config['query_profiler_callback'] = $callback;
	}

	/**
	 * Collects and aggregates data recorded by Profiler_Console.
	 */
	public function gatherConsoleData() {
		$logs = Profiler_Console::getLogs();


		foreach ($logs as $type => $data) {
			// Console data will already be properly formatted.
			if ($type == 'console') {
				continue;
			}

			// Ignore empty message lists
			if (!$data['count']) {
				continue;
			}

			foreach ($data['messages'] as $message) {
				$data = $message;

				switch ($type) {
					case 'logs':
						$data['type'] = 'log';
						$data['data'] = print_r($message['data'], true);
						break;
					case 'memory':
						$data['type'] = 'memory';
						$data['data'] = $this->getReadableFileSize($data['data']);
						break;
					case 'speed':
						$data['type'] = 'speed';
						$data['data'] = $this->getReadableTime(($message['data'] - $this->startTime) * 1000);
						break;
					case 'benchmarks':
						$data['type'] = 'benchmark';
						$data['data'] = $this->getReadableTime($message['end_time'] - $message['start_time']);
						break;
				}

				if (isset($data['type'])) {
					$logs['console']['messages'][] = $data;
				}
			}
		}

		$this->output['logs'] = $logs;
	}

	/**
	 * Gathers and aggregates data on included files such as size
	 */
	public function gatherFileData() {
		$files = get_included_files();
		$fileList = array();
		$fileTotals = array('count' => count($files), 'size' => 0, 'largest' => 0);

		foreach($files as $key => $file) {
			$size = filesize($file);
			$fileList[] = array('name' => $file, 'size' => $this->getReadableFileSize($size));
			$fileTotals['size'] += $size;

			if ($size > $fileTotals['largest']) {
				$fileTotals['largest'] = $size;
			}
		}
		
		$fileTotals['size'] = $this->getReadableFileSize($fileTotals['size']);
		$fileTotals['largest'] = $this->getReadableFileSize($fileTotals['largest']);

		$this->output['files'] = $fileList;
		$this->output['fileTotals'] = $fileTotals;
	}

	/**
	 * Gets the peak memory usage the configured memory limit
	 */
	public function gatherMemoryData() {
		$memoryTotals = array();
		$memoryTotals['used'] = $this->getReadableFileSize(memory_get_peak_usage());
		$memoryTotals['total'] = ini_get('memory_limit');

		$this->output['memoryTotals'] = $memoryTotals;
	}

	/**
	 * Gathers and aggregates data regarding executed queries
	 */
	public function gatherQueryData() {
		$queries = array();
		$type_default = array('total' => 0, 'time' => 0, 'percentage' => 0, 'time_percentage' => 0);
		$types = array('select' => $type_default, 'update' => $type_default, 'insert' => $type_default, 'delete' => $type_default);
		$queryTotals = array('all' => 0, 'count' => 0, 'time' => 0, 'duplicates' => 0, 'types' => $types);

		foreach($this->output['logs']['queries']['messages'] as $entries) {
			if (count($entries) > 1) {
				$queryTotals['duplicates'] += 1;
			}

			$queryTotals['count'] += 1;
			foreach ($entries as $i => $log) {
				if (isset($log['end_time'])) {
					$query = array('sql' => $log['sql'],
						'explain' => $log['explain'],
						'time' => ($log['end_time'] - $log['start_time']),
						'duplicate' => $i > 0 ? true : false);

					// Lets figure out the type of query for our counts
					$trimmed = trim($log['sql']);
					$type = strtolower(substr($trimmed, 0, strpos($trimmed, ' ')));

					if (in_array($type, $this->_queryTypes) && isset($queryTotals['types'][$type])) {
						$queryTotals['types'][$type]['total'] += 1;
						$queryTotals['types'][$type]['time'] += $query['time'];
					}

					// Need to get total times and a readable format of our query time
					$queryTotals['time'] += $query['time'];
					$queryTotals['all'] += 1;
					$query['time'] = $this->getReadableTime($query['time']);

					// If an explain callback is setup try to get the explain data
					if (isset($this->_queryTypes[$type]) && isset($this->config['query_explain_callback']) && !empty($this->config['query_explain_callback'])) {
						$query['explain'] = $this->_attemptToExplainQuery($query['sql']);
					}

					// If a query profiler callback is setup get the profiler data
					if (isset($this->config['query_profiler_callback']) && !empty($this->config['query_profiler_callback'])) {
						$query['profile'] = $this->_attemptToProfileQuery($query['sql']);
					}

					$queries[] = $query;
				}
			}
		}

		// Go through the type totals and calculate percentages
		foreach ($queryTotals['types'] as $type => $stats) {
			$total_perc = !$stats['total'] ? 0 : round(($stats['total'] / $queryTotals['count']) * 100, 2);
			$time_perc = !$stats['time'] ? 0 : round(($stats['time'] / $queryTotals['time']) * 100, 2);

			$queryTotals['types'][$type]['percentage'] = $total_perc;
			$queryTotals['types'][$type]['time_percentage'] = $time_perc;
			$queryTotals['types'][$type]['time'] = $this->getReadableTime($queryTotals['types'][$type]['time']);
		}

		$queryTotals['time'] = $this->getReadableTime($queryTotals['time']);
		$this->output['queries'] = $queries;
		$this->output['queryTotals'] = $queryTotals;
	}

	/**
	 * Calculates the execution time from the start of profiling to *now* and
	 * collects the congirued maximum execution time.
	 */
	public function gatherSpeedData() {
		$speedTotals = array();
		$speedTotals['total'] = $this->getReadableTime((microtime(true) - $this->startTime)*1000);
		$speedTotals['allowed'] = ini_get('max_execution_time');
		$this->output['speedTotals'] = $speedTotals;
	}

	/**
	 * Converts a number of bytes to a more readable format
	 * @param int $size The number of bytes
	 * @param string $retstring The format of the return string
	 * @return string
	 */
	public function getReadableFileSize($size, $retString = null) {
		$sizes = array('bytes', 'kB', 'MB', 'GB', 'TB');

		if ($retString === null) {
			$retString = '%01.2f %s';
		}

		$lastSizeString = end($sizes);

		foreach ($sizes as $sizeString) {
			if ($size < 1024) {
				break;
			}

			if ($sizeString != $lastSizeString) {
				$size /= 1024;
			}
		}

		if ($sizeString == $sizes[0]) {
			$retString = '%01d %s';
		}

		return sprintf($retString, $size, $sizeString);
	}

	/**
	 * Converts a small time format (fractions of a millisecond) to a more readable format
	 * @param float $time
	 * @return int
	 */
	public function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');

		if ($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}

		if ($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}

		$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
		return $ret;
	}

	/**
	 * Collects data from the console and performs various calculations on it before
	 * displaying the console on screen.
	 */
	public function display() {
		$this->gatherConsoleData();
		$this->gatherFileData();
		$this->gatherMemoryData();
		$this->gatherQueryData();
		$this->gatherSpeedData();

		Profiler_Display::display($this->output, $this->config);
	}

	/**
	 * Used with a callback to allow integration into DAL's to explain an executed query.
	 * 
	 * @param string $sql The query that is being explained
	 * @return array
	 */
	protected function _attemptToExplainQuery($sql) {
		try {
			$sql = 'EXPLAIN ' . $sql;
			return call_user_func_array($this->config['query_explain_callback'], $sql);
		} catch (Exception $e) {
			return array();
		}
	}

	/**
	 * Used with a callback to allow integration into DAL's to profiler an execute query.
	 *
	 * @param string $sql The query being profiled
	 * @return array
	 */
	protected function _attemptToProfileQuery($sql) {
		try {
			return call_user_func_array($this->config['query_profiler_callback'], $sql);
		} catch (Exception $e) {
			return array();
		}
	}
}