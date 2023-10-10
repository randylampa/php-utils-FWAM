<?php

namespace TgUtils;

use TgLog\Log;
use TgLog\Error;

/** Holds information about an HTTP request */
class Request {

    const DEFAULT_REQUEST_URI = 'https://www.example.com/';
    
	/** the default instance from globals */
	protected static $request;

	/**
	 * Returns the singleton request.
	 * @return Request the request object
	 */
	public static function getRequest() {
		if (!self::$request) {
			self::$request = new Request();
		}
		return self::$request;
	}

	/** The protocol (http or https) */
	public $protocol;
	/** The HTTP method */
	public $method;
	/** All headers from the request as array */
	public $headers;
	/** The host as the user requested it (can differ from $httpHost in reverse proxy setups) */
	public $host;
	/** The URI to the root of the server, combination of protocol and host. */
	public $rootUri;
	/** The HTTP host - the host mentioned in Host: header */
	public $httpHost;
	/** The URI which includes the parameters */
	public $uri;
	/** The path of the request. Does not include parameters */
	public $path;
	/** The path of the original request (requested at proxy). Does not include parameters */
	public $originalPath;
	/** The original request (requested at proxy). Includes parameters */
	public $originalUri;
	/** The path split in its elements */
	public $pathElements;
	/** The parameters as a string */
	public $params;
	/** The path parameters (GET params) */
	public $getParams;
	/** The (context) document root */
	public $documentRoot;
	/** The web root as seen by the user, usually '/' or an alias or mapped path from a proxy */
	public $webRoot;
	/** The web root as defined by the local web server */
	public $localWebRoot;
	/** The web root URI as it can be requested by the user */
	public $webRootUri;
	/** The epoch time in seconds when the request was created */
	public $startTime;
	/** Usually the document root */
	public $appRoot;
	/** relative path from docroot to the app root, usually empty */
	public $relativeAppPath;

	/** The body of the request (intentionally not public) */
	protected $body;
	/** The post params of the request */
	protected $postParams;

	/** DEPRECATED: The language code for this request (by default: en) */
	public $langCode;

	/** Constructor */
	public function __construct() {
		// Sequence matters!
		$this->method       = $_SERVER['REQUEST_METHOD'];
		$this->headers      = $this->initHeaders();
		$this->protocol     = $this->initProtocol();
		$this->httpHost     = $_SERVER['HTTP_HOST'];
		$this->host         = $this->initHost();
		$this->rootUri      = $this->initRootUri();
        if (isset($_SERVER['REQUEST_URI'])) {
	        $this->uri      = $_SERVER['REQUEST_URI'];
        } else {
	        $this->uri      = Request::DEFAULT_REQUEST_URI;
        }
	    $uri_parts          = explode('?', $this->uri, 2);
		$this->path         = $uri_parts[0];
		$this->pathElements = $this->initPathElements();
		$this->params       = count($uri_parts) > 1 ? $uri_parts[1] : '';
		$this->getParams    = $this->initGetParams();
		$this->postParams   = NULL;
		$this->body         = NULL;
		$this->documentRoot = $this->initDocumentRoot();
		$this->webRoot      = $this->initWebRoot(TRUE);
		$this->originalPath = $this->initOriginalPath();
		$this->originalUri  = $this->initOriginalUri();
		$this->localWebRoot = $this->initWebRoot(FALSE);
		$this->webRootUri   = $this->initWebRootUri();
		$this->appRoot      = $this->documentRoot;
		$this->relativeAppPath = '';

		$this->startTime    = time();

		// Will be deprecated
		$this->langCode     = 'en';
	}

	/** 
	 * Different products return different keys in headers. We make all keys lowercase here.
	 */
	protected function initHeaders() {
		$headers = getallheaders();
		$rc = array();
		foreach ($headers AS $key => $value) {
			$rc[strtolower($key)] = $value;
		}
		return $rc;
	}

	/**
	 * Returns the server hostname that was requested.
	 * <p>The host is extracted from HTTP_X_FORWARDED_HOST or when not set
	 *    taken by the function getHttpHost(). Forwarded hosts return multiple
	 *    hosts eventually (e.g. when using reverse proxies). The last such
	 *    host is returned then.</p>
	 * @return string the Host requested by the user.
	 */
	protected function initHost() {
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			$last  = trim($forwarded[count($forwarded)-1]);
			$first = trim($forwarded[0]);
			if ($first != $this->httpHost) return $first;
			if ($last  != $this->httpHost) return $last;
		}
		return $this->httpHost;
	}

	/**
	 * Returns the protocol (http, https) being used by the user.
	 * <p>The protocol can be switched at reverse proxies, that's
	 *    why the HTTP_X_FORWARDED_PROTO variable is checked.
	 *    Otherwise it will be the REQUEST_SCHEME.</p>
	 * @return string the protocol as used by the user.
	 */
	protected function initProtocol() {
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			return $_SERVER['HTTP_X_FORWARDED_PROTO'];
		}
		return $_SERVER['REQUEST_SCHEME'];
	}

	/**
	 * Returns the base for the URI - protocol and host.
	 */
	protected function initRootUri() {
		return $this->protocol.'://'.$this->host;
	}

	/**
	 * Returns all path elements with .html stripped of if detected.
	 * <p>E.g. /my/path/index.html will return three elements: my, path and index.</p>
	 * @return array the path elements.
	 */
	protected function initPathElements() {
		$path = substr($this->path, 1);
		if (substr($path, strlen($path)-5) == '.html') $path  = substr($path, 0, strlen($path)-5);
		if (substr($path, strlen($path)-1) == '/') $path  = substr($path, 0, strlen($path)-1);
		$elems = explode('/', $path);
		if ((count($elems) == 1) && ($elems[0] == '')) $elems = array();
		return $elems;
	}

	/**
	 * Returns whether a specific key was given as parameter.
	 * @return TRUE when parameter was set.
	 */
	public function hasGetParam($key) {
		$params = $this->getParams;
		return isset($params[$key]);
	}

	/**
	 * Returns the GET parameter value from the request.
	 * @param string $key     - the parameter name
	 * @param mixed  $default - the default value to return when parameter does not exist (optional, default is NULL).
	 * @param object $filter  - a filter to sanitize the value.
	 * @return mixed the parameter value or its default.
	 */
	public function getGetParam($key, $default = NULL, $filter = NULL) {
		$params = $this->getParams;
		if ($filter == NULL) $filter = NoHtmlStringFilter::$INSTANCE;
		return isset($params[$key]) ? $filter->filter($params[$key]) : $default;
	}

	/**
	 * Returns the parameters as an array.
	 * @return array array of parameters.
	 */
	protected function initGetParams() {
		return self::parseQueryString($this->params);
	}

	/**
	 * Returns whether a specific key was given as parameter in POST request.
	 * @return TRUE when parameter was set.
	 */
	public function hasPostParam($key) {
		$params = $this->getPostParams();
		return isset($params[$key]);
	}

	/**
	 * Returns the POST parameter value from the request.
	 * @param string $key     - the parameter name
	 * @param mixed  $default - the default value to return when parameter does not exist (optional, default is NULL).
	 * @param object $filter  - a filter to sanitize the value.
	 * @return mixed the parameter value or its default.
	 */
	public function getPostParam($key, $default = NULL, $filter = NULL) {
		$params = $this->getPostParams();
		if ($filter == NULL) $filter = NoHtmlStringFilter::$INSTANCE;
		return isset($params[$key]) ? $filter->filter($params[$key]) : $default;
	}

	/**
	 * Returns an array of all POST parameters.
	 * @return array post parameters
	 */
	public function getPostParams() {
		if ($this->postParams == NULL) {
			$this->postParams = array();
			// Check that we have content-length
			$len = $this->getHeader('Content-Length');
			if ($len) {
				$len = intval($len);
				// Check that we have  a valid content-length
				if ($len>0) {
					$this->postParams = $_POST;
				} else {
					Log::register(new Error('POST content invalid'));
				}
			}
		}
		return $this->postParams;
	}

	/**
	 * Returns the body of the request (POST and PUT requests only).
	 * @return string the request body.
	 */
	public function getBody() {
		if (in_array($this->method, array('POST', 'PUT'))) {
			if ($this->body == NULL) {
				$this->body = file_get_contents('php://input');
			}
		}
		return $this->body;
	}

	/**
	 * Parses the query string.
	 * @param $s - the query parameter string
	 * @return array the query parameter values.
	 */
	public static function parseQueryString($s) {
		$rc = array();
		parse_str($s, $rc);
		return $rc;
	}

	/**
	 * Returns a header value.
	 * @param string $key - the header key
	 * @return string the value of the header.
	 */
	public function getHeader($key) {
		if (isset($this->headers[strtolower($key)])) return $this->headers[strtolower($key)];
		return NULL;
	}

	/**
	 * Returns a GET or POST parameter when given.
	 * <p>The method will search GET and POST parameters for the given key and return the
	 *    first it finds.</p>
	 * @param string $key - the parameter name.
	 * @param mixed $default - the default value when not found (optional, default is NULL)
	 * @param boolean $getPrecedes - TRUE when GET parameter shall be returned even when POST parameter is given. (optional, default is TRUE).
	 * @return string the parameter value or its default.
	 */
	public function getParam($key, $default = NULL, $getPrecedes = true) {
		$rc = $getPrecedes ? $this->getGetParam($key) : $this->getPostParam($key);
		if ($rc == NULL) $rc = $getPrecedes ? $this->getPostParam($key) : $this->getGetParam($key);
		if ($rc == NULL) $rc = $default;
		return $rc;
	}

	/**
	 * Returns the time since the request started.
	 * @return int elapsed time in seconds.
	 */
	public function getElapsedTime() {
		return time() - $this->startTime;
	}

	/**
	 * Returns the document root - this is the real path name of the web root.
	 * @return string the document root or context document root if available.
	 */
	protected function initDocumentRoot() {
	    if (isset($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	        return $_SERVER['CONTEXT_DOCUMENT_ROOT'];
	    }
	    return $_SERVER['DOCUMENT_ROOT'];
	}

	/**
	 * Returns the original path as request by the end user.
	 * The path might be different from $this->path as
	 * a webroot mapping might be involved.
	 */
	protected function initOriginalPath() {
		$rc = $this->path;
		$rootDef = isset($_SERVER['HTTP_X_FORWARDED_ROOT']) ? $_SERVER['HTTP_X_FORWARDED_ROOT'] : '';
		if ($rootDef) {
			$arr = explode(',', $rootDef);
			if (strpos($rc, $arr[0]) === 0) {
				$rc = $arr[1].substr($rc, strlen($arr[0]));
			}
		}
		return $rc;
	}

	/**
	 * Returns the original URI as request by the end user.
	 * The path might be different from $this->path as
	 * a webroot mapping might be involved.
	 */
	protected function initOriginalUri() {
		$rc = $this->originalPath;
		if ($this->params) {
			$rc .= '?'.$this->params;
		}
		return $rc;
	}

	/**
	 * Returns the web root - that is the web path where the current
	 * script is rooted and usually the base path for an application.
	 * <p>$_SERVER['PHP_SELF'] or $_SERVER['SCRIPT_NAME']</p> will
	 *    be misleading as they would not tell the real document root.</p>
	 * @return string the presumed web root.
	 */
	protected function initWebRoot($considerForwarding = TRUE) {
		if ($considerForwarding) {
			$rootDef = isset($_SERVER['HTTP_X_FORWARDED_ROOT']) ? $_SERVER['HTTP_X_FORWARDED_ROOT'] : '';
			if ($rootDef) {
				$arr = explode(',', $rootDef);
				$rc = $arr[1];
				if ((strlen($rc) > 0) && (substr($rc, -1) == '/')) $rc = substr($rc, 0, strlen($rc)-1);
				return $rc;
			}
		}
		$docRoot = $this->documentRoot;
		$fileDir = dirname($_SERVER['SCRIPT_FILENAME']);
		$webRoot = substr($fileDir, strlen($docRoot));
		if (isset($_SERVER['CONTEXT'])) {
		    $webRoot = $_SERVER['CONTEXT'].$webRoot;
		}
		if ((strlen($webRoot) > 0) && (substr($webRoot, -1) == '/')) $webRoot = substr($rc, 0, strlen($webRoot)-1);
		return $webRoot;
	}

	/**
	 * Returns the full URL of the web root.
	 * @return string the URL to the root dir.
	 */
	protected function initWebRootUri() {
		$protocol = $this->protocol;
		$host     = $this->host;
		return $protocol.'://'.$host.$this->webRoot;
	}

	/**
	 * Initializes the appRoot and relativeAppPath according to the root of the app.
	 * The appRoot can differ from document root as it can be installed in a subdir.
	 * @param string $appRoot - the application root directory (absolute path)
	 */
	public function setAppRoot($appRoot) {	
		$appRootLen = strlen($appRoot);
		$docRootLen = strlen($this->documentRoot);
		if (($docRootLen < $appRootLen) && (strpos($appRoot, $this->documentRoot) === 0)) {
			$this->relativeAppPath = substr($appRoot, $docRootLen);
		} else {
			$this->relativeAppPath = '';
		}
	}
}

