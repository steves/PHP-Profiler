<?php
/**
 * Port of PHP Quick Profiler by Ryan Campbell
 * Original URL: http://particletree.com/features/php-quick-profiler
 */
class Profiler_Console {
	/**
	 * Holds the logs used when the console is displayed.
	 * @var array
	 */
	private static $debugger_logs = array('console' => array(),
										  'logCount' => 0,
										  'memoryCount' => 0,
										  'errorCount' => 0,
										  'speedCount' => 0);

	/**
	 * Logs a variable to the console
	 * @param mixed $data The data to log to the console
	 * @return void
	 */
	public static function log($data) {
		if (!isset(self::$debugger_logs['console']))
			self::$debugger_logs['console'] = array();
		if (!isset(self::$debugger_logs['logCount']))
			self::$debugger_logs['logCount'] = 0;

		self::$debugger_logs['console'][] = array('data' => $data, 'type' => 'log');
		self::$debugger_logs['logCount'] += 1;
	}

	/**
	 * Logs the memory usage of the provided variable, or entire script
	 * @param object $object Optional variable to log the memory usage of
	 * @param string $name Optional name used to group variables and scripts together
	 * @return void
	 */
	public static function logMemory($object = false, $name = 'PHP') {
		$memory = $object ? strlen(serialize($object)) : memory_get_usage();
		$log_item = array('data' => $memory,
						  'type' => 'memory',
						  'name' => $name,
						  'dataType' => gettype($object));

		self::$debugger_logs['console'][] = $log_item;
		self::$debugger_logs['memoryCount'] += 1;
	}

	/**
	 * Logs an exception or error
	 * @param Exception $exception
	 * @param string $message
	 * @return void
	 */
	public static function logError($exception, $message) {
		$log_item = array('data' => $message,
						  'type' => 'error',
						  'file' => $exception->getFile(),
						  'line' => $exception->getLine());

		self::$debugger_logs['console'][] = $log_item;
		self::$debugger_logs['errorCount'] += 1;
	}

	/**
	 * Starts a timer, a second call to this method will end the timer and cause the
	 * time to be recorded and displayed in the console.
	 * @param string $name
	 * @return void
	 */
	public static function logSpeed($name = 'Point in Time') {
		$log_item = array('data' => microtime(true),
						  'type' => 'speed',
						  'name' => $name);

		self::$debugger_logs['console'][] = $log_item;
		self::$debugger_logs['speedCount'] += 1;
	}

	/**
	 * Records how long a query took to run when the same query is passed in twice.
	 * @param string $sql
	 * @return void
	 */
	public static function logQuery($sql, $explain = null) {
		// We use a hash of the query for two reasons. One is because for large queries the
		// hash will be considerably smaller in memory. The second is to make a dump of the
		// logs more easily readable.
		$hash = md5($sql);

		// If this query is in the log we need to see if an end time has been set. If no
		// end time has been set then we assume this call is closing a previous one.
		if (isset(self::$debugger_logs['console'][$hash])) {
			$query = array_pop(self::$debugger_logs['console'][$hash]);
			if (!$query['end_time']) {
				$query['end_time'] = microtime(true);
				$query['explain'] = $explain;

				self::$debugger_logs['console'][$hash][] = $query;
			} else {
				self::$debugger_logs['console'][$hash][] = $query;
			}

			return;
		}

		$log_item = array('start_time' => microtime(true),
						  'end_time' => false,
						  'explain' => false,
						  'type' => 'query',
						  'sql' => $sql);

		self::$debugger_logs['console'][$hash][] = $log_item;
	}

	/**
	 * Returns all log data
	 * @return array
	 */
	public static function getLogs() {
		return self::$debugger_logs;
	}
}