<?php

/* RestUtils ---------------------------------------------------------------- */

class RestUtils {

    public static function processRequest() {

		$method = strtolower($_SERVER['REQUEST_METHOD']);
		$return_obj = new RestRequest();
		$data = array(); // we'll store our data here

		if ('get' == $method) {
		    $data = $_GET;
		}
		elseif ('post' == $method) {
		    $data = $_POST;
		}
		elseif ('put' == $method) {
		    parse_str(file_get_contents('php://input'), $put_vars);
		    $data = $put_vars;
		}

		// store the method
		$return_obj->setMethod($method);

		// set the raw data, so we can access it if needed (there may be other pieces to your requests)
		$return_obj->setRequestVars($data);

		if(isset($data['data'])) {
			// translate the JSON to an Object for use however you want
			$return_obj->setData(json_decode($data['data']));
		}

		return $return_obj;

	}


	public static function sendResponse($status = 200, $body = '', $content_type = 'text/html') {

		$status_header = 'HTTP/1.1 ' . $status . ' ' . RestUtils::getStatusCodeMessage($status);
		// set the status
		header($status_header);
		// set the content type
		header('Content-type: ' . $content_type);

		// pages with body are easy
		if($body != '') {
			// send the body
			echo $body;
			exit;
		}
		else {

		    require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
		    require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

		    $tpl = new suxTemplate('api');
		    $r = new suxRenderer('api');
		    $tpl->assign_by_ref('r', $r); // Renderer referenced in template

			// create some body messages
			$message = '';

			// this is purely optional, but makes the pages a little nicer to read
			// for your users.  Since you won't likely send a lot of different status codes,
			// this also shouldn't be too ponderous to maintain
			switch($status)
			{
			case 401:
			    $message = $r->gtext['unauthorized'];
			    break;
			case 404:
			    $message = $r->gtext['404_notfound'];
			    break;
			case 500:
			    $message = $r->gtext['server_error'];
			    break;
			case 501:
			    $message = $r->gtext['not_implemented'];
			    break;
			}

			// servers don't always have a signature turned on (this is an apache directive "ServerSignature On")
			$signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

			// Template variables
			$r->title = $status . ' ' . RestUtils::getStatusCodeMessage($status);
            $r->text['status'] = RestUtils::getStatusCodeMessage($status);
			$r->text['message'] = $message;
			$r->text['signature'] = $signature;

			// Template
			$tpl->display('response.tpl');

		}
	}


	public static function getStatusCodeMessage($status) {
		$codes = Array(
		    100 => 'Continue',
		    101 => 'Switching Protocols',
		    200 => 'OK',
		    201 => 'Created',
		    202 => 'Accepted',
		    203 => 'Non-Authoritative Information',
		    204 => 'No Content',
		    205 => 'Reset Content',
		    206 => 'Partial Content',
		    300 => 'Multiple Choices',
		    301 => 'Moved Permanently',
		    302 => 'Found',
		    303 => 'See Other',
		    304 => 'Not Modified',
		    305 => 'Use Proxy',
		    306 => '(Unused)',
		    307 => 'Temporary Redirect',
		    400 => 'Bad Request',
		    401 => 'Unauthorized',
		    402 => 'Payment Required',
		    403 => 'Forbidden',
		    404 => 'Not Found',
		    405 => 'Method Not Allowed',
		    406 => 'Not Acceptable',
		    407 => 'Proxy Authentication Required',
		    408 => 'Request Timeout',
		    409 => 'Conflict',
		    410 => 'Gone',
		    411 => 'Length Required',
		    412 => 'Precondition Failed',
		    413 => 'Request Entity Too Large',
		    414 => 'Request-URI Too Long',
		    415 => 'Unsupported Media Type',
		    416 => 'Requested Range Not Satisfiable',
		    417 => 'Expectation Failed',
		    500 => 'Internal Server Error',
		    501 => 'Not Implemented',
		    502 => 'Bad Gateway',
		    503 => 'Service Unavailable',
		    504 => 'Gateway Timeout',
		    505 => 'HTTP Version Not Supported'
		    );

		return (isset($codes[$status])) ? $codes[$status] : '';
	}
}


/* RestRequest -------------------------------------------------------------- */

class RestRequest {

	private $request_vars;
	private $data;
	private $http_accept;
	private $method;

	public function __construct() {

		$this->request_vars = array();
		$this->data = '';
		$this->http_accept = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
		$this->method = 'get';
	}

	public function setData($data) {
		$this->data = $data;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function setRequestVars($request_vars) {
		$this->request_vars = $request_vars;
	}

	public function getData() {
		return $this->data;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getHttpAccept() {
		return $this->http_accept;
	}

	public function getRequestVars() {
		return $this->request_vars;
	}
}


/* ArrayToXML --------------------------------------------------------------- */

class ArrayToXML
{
	/**
	* The main function for converting to an XML document.
	* Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	*
	* @param array $data
	* @param string $rootNodeName - what you want the root node to be - defaultsto data.
	* @param SimpleXMLElement $xml - should only be used recursively
	* @return string XML
	*/
	public static function toXml($data, $rootNodeName = 'data', &$xml=null)
	{

	    // Force an array
	    if (!is_array($data)) $data = array();

		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}

		if (is_null($xml))
		{
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				$key = "unknownNode_". (string) $key;
			}

			// delete any char not allowed in XML element names
			$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value))
			{
				$node = $xml->addChild($key);
				// recrusive call.
				ArrayToXML::toXml($value, $rootNodeName, $node);
			}
			else
			{
				// add single node.
				$value = htmlentities($value);
				$xml->addChild($key,$value);
			}

		}
		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}
}

?>
