<?php

namespace TgLog;

/**
 * Handles various logging requirements such as:
 * <ul>
 * <li>registering messages in session to be available in next HTTP call.</li>
 * <li>logging messages to error_log</li>
 * <li>collecting log messages so they can be retrieved later for user display.</li>
 * </ul>
 */
class Log {
    
    const NONE    = 'none';
    const DEBUG   = 'debug';
    const INFO    = 'info';
    const WARN    = 'warn';
    const ERROR   = 'error';
    const SUCCESS = 'success';
    
    /** The available log priorities */
    protected static $logPriorities;
    /** The log level to issue in error_log */
	protected static $defaultLogLevel;
	/** The name of the application to prefix log entries */
	protected static $defaultAppName;
	
	/** The single instance of the log */
	public    static $instance;
	
	/** All log messages that were issued */
	public    $messages;
	/** The log level of the instance */
	protected $logLevel;
	/** The app name to prefix lof entries with */
	protected $appName;
	
	/**
	 * Returns the single instance.
	 * @return Log - single Log instance 
	 */
    public static function instance() {
		if (self::$defaultLogLevel == null) {
			self::$defaultLogLevel = self::INFO;
		}
		if (self::$instance == null) {
			self::$instance = new Log(self::$defaultLogLevel, self::$defaultAppName);
		}
		return self::$instance;
	}
	
	/**
	 * Returns the log level of this instance.
	 * @return string the current log level
	 */
	public function getLogLevel() {
	    return $this->logLevel;
	}
	
	/**
	 * Sets the log level of this instance.
	 * @param string $level - the new log level
	 */
	public function setLogLevel($level) {
	    $this->logLevel = $level;
	}
	
	/**
	 * Returns the prefix for log entries.
	 * @return string the current prefix
	 */
	public function getAppName() {
	    return $this->appName;
	}
	
	/**
	 * Sets the prefix that log entries shall be given.
	 * @param string $appName - the new prefix
	 */
	public function setAppName($appName) {
	    $this->appName = $appName;
	}
	
	/**
	 * Public constructor.
	 * @param string $logLevel - the log level for this instance (optional, default is Log::INFO)
	 * @param string $appName  - the prefix for the log entry (optional)
	 * <p>Usually you don't need to instantiate it yourself but use the single instance.</p> 
	 */
    public function __construct($logLevel = self::INFO, $appName = NULL) {
		$this->messages = array();
		$this->logLevel = $logLevel;
		$this->appName  = $appName != NULL ? $appName : self::$defaultAppName;
	}

	/**
	 * Logs the given message and, optionally, object with given severity.
	 * @param string $sev - the severity
	 * @param string $s - the log message
	 * @param mixed $o - an object to be logged along with the message.
	 */
	protected function log($sev, $s, $o = null) {
		if (!is_string($s)) $s = json_encode($s, JSON_PRETTY_PRINT);
		if ($o != null) {
			if ($o instanceof \Throwable) {
				$s .= ': '.get_class($o).' "'.$o->getCode().' - '.$o->getMessage()."\" at\n".$o->getTraceAsString();
			} else {
				$s .= ': '.json_encode($o, JSON_PRETTY_PRINT);
			}
		}
		$this->messages[$sev][] = $s;
		$prefix = $this->getAppName() != NULL ? '['.$this->getAppName().']' : '';
		if ($this->isLogLevelIncluded($sev)) error_log($prefix.'['.strtoupper($sev).'] '.$s);
	}

	/**
	 * Logs the stacktrace, optionally excluding a certain file in the trace.
	 * @param string $sev - severity to be logged
	 * @param string $excludeFile - do not include this file in stacktrace.
	 */
	public function logStackTrace($sev, $excludeFile = NULL) {
		$trace = $this->getStackTrace($excludeFile);
		$this->log($sev, 'Stacktrace:');
		foreach ($trace AS $line) {
			$this->log($sev, '   '.$line);
		}
	}

	/**
	 * Returns the stacktrace, optionally excluding a certain file in the trace.
	 * @param string $excludeFile - do not include this file in stacktrace.
	 * @return array list of trace messages for each call in stack
	 */
	public function getStackTrace($excludeFile = NULL) {
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$arr = array();
		foreach ($backtrace AS $step) {
			if (($step['file'] != __FILE__) && (($excludeFile == NULL) || ($step['file'] != $excludeFile))) {
				if (isset($step['type'])) {
					$arr[] = 'at '.$step['file'].' (line '.$step['line'].'): '.$step['class'].$step['type'].$step['function'].'()';
				} else {
					$arr[] = 'at '.$step['file'].'(line '.$step['line'].'): '.$step['function'].'()';
				}
			}
		}
		return $arr;
	}

	/**
	 * Debug message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public static function debug($s, $o = null) {
		self::instance()->logDebug($s, $o);
	}

	/**
	 * Return whether debug level will be logged.
	 * @return TRUE when log level enabled.
	 */
	public static function isDebug() {
		return self::instance()->isLogLevelIncluded(self::DEBUG);
	}

	/**
	 * Debug message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public function logDebug($s, $o = null) {
		$this->log(self::DEBUG, $s, $o);
	}

	/**
	 * Info message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public static function info($s, $o = null) {
		self::instance()->logInfo($s, $o);
	}

	/**
	 * Return whether info level will be logged.
	 * @return TRUE when log level enabled.
	 */
	public static function isInfo() {
		return self::instance()->isLogLevelIncluded(self::INFO);
	}

	/**
	 * Info message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public function logInfo($s, $o = null) {
		$this->log(self::INFO, $s, $o);
	}

	/**
	 * Warning message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public static function warn($s, $o = null) {
		self::instance()->logWarn($s, $o);
	}

	/**
	 * Return whether warn level will be logged.
	 * @return TRUE when log level enabled.
	 */
	public static function isWarn() {
		return self::instance()->isLogLevelIncluded(self::WARN);
	}

	/**
	 * Warning message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public function logWarn($s, $o = null) {
		$this->log(self::WARN, $s, $o);
	}

	/**
	 * Error message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public static function error($s, $o = null) {
		self::instance()->logError($s, $o);
	}

	/**
	 * Return whether error level will be logged.
	 * @return TRUE when log level enabled.
	 */
	public static function isError() {
		return self::instance()->isLogLevelIncluded(self::ERROR);
	}

	/**
	 * Error message into log.
	 * @param string $s - the message
	 * @param mixed  $o - an oject to be dumped along with message. An Exception object will cause a stacktrace dump (optional).
	 */
	public function logError($s, $o = null) {
		$this->log(self::ERROR, $s, $o);
	}

	/**
	 * Print stacktrace into debug log, optionally excluding a certain file from trace.
	 * @param string $excludeFile - the file to be excluded (optional).
	 */
	public static function debugStackTrace($excludeFile = NULL) {
		self::instance()->logStackTrace(self::DEBUG, $excludeFile);
	}

	/**
	 * Print stacktrace into info log, optionally excluding a certain file from trace.
	 * @param string $excludeFile - the file to be excluded (optional).
	 */
	public static function infoStackTrace($excludeFile = NULL) {
		self::instance()->logStackTrace(self::INFO, $excludeFile);
	}

	/**
	 * Print stacktrace into warning log, optionally excluding a certain file from trace.
	 * @param string $excludeFile - the file to be excluded (optional).
	 */
	public static function warnStackTrace($excludeFile = NULL) {
		self::instance()->logStackTrace(self::WARN, $excludeFile);
	}

	/**
	 * Print stacktrace into error log, optionally excluding a certain file from trace.
	 * @param string $excludeFile - the file to be excluded (optional).
	 */
	public static function errorStackTrace($excludeFile = NULL) {
		self::instance()->logStackTrace(self::ERROR, $excludeFile);
	}

	/**
	 * Set the global log level.
	 * @param $sev - shall be debug, info, warn, error or none.
	 */
	public static function setDefaultLogLevel($sev) {
		self::$defaultLogLevel = $sev;
	}
	
	/**
	 * Set the global log entry prefix.
	 * @param $appName - default prefix for log entries.
	 */
	public static function setDefaultAppName($appName) {
		self::$defaultAppName = $appName;
	}
	
	/**
	 * Register a message for session-wide using.
	 * <p>The message will sent to the log and also stored in session for further usage.</p>
	 * @param Message $message - the message to be registered.
	 */
    public static function register(Message $message) {
		$_SESSION['messages'][] = $message;
		switch ($message->getType()) {
		case 'error':   self::error($message->getMessage()); break;
		case 'warning': self::warn($message->getMessage()); break;
		case 'info':    self::info($message->getMessage()); break;
		case 'debug':   self::debug($message->getMessage()); break;
		}
	}

	/**
	 * Clean all session-wide registsred messages, e.g. after display to user.
	 */
	public static function clean() {
		$_SESSION['messages'] = array();
	}

	/**
	 * Get all session wide messages that were registered, e.g. for user display.
	 * @return array list of messages registered.
	 */
	public static function get() {
		return isset($_SESSION['messages']) ? $_SESSION['messages'] : array();
	}

	/**
	 * Internal function to check whether a loglevel is supposed to be logged.
	 * @param string $sev - the log level to check
	 * @return TRUE when the current loglevel allows logging.
	 */
	protected function isLogLevelIncluded($sev) {
		if (self::$logPriorities == null) {
			self::$logPriorities = array(self::NONE, self::DEBUG, self::INFO, self::WARN, self::ERROR);
		}
		$logIndex = array_search($this->logLevel, self::$logPriorities);
		$sevIndex = array_search($sev, self::$logPriorities);
		return $logIndex <= $sevIndex;
	}

	
}
