<?php

/**
* api
*
* @author     Santy Chumbe <chumbe@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/


require_once('apiRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.bayesComponent.php');

class api extends bayesComponent {

    // Module name
    protected $module = 'api';


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->nb = new bayesUser(); // Bayes
        $this->r = new apiRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

    }


    // --------------------------------------------------------------------
    // Public api functions
    // Append _api to the end of the function name if you want it to be
    // accessible from the controller
    // --------------------------------------------------------------------


    /**
    * Display default welcome screen, maybe describe the API here?
    */
    function welcome_api() {

        $this->tpl->display('welcome.tpl');

    }



    /**
    * /api/users
    *
    * @param array $params
    */
    function users_api($params) {

        /*
        GET request to /api/users            : List all users
        GET request to /api/users/1          : List info for user with ID of 1
        POST request to /api/users           : Create a new user, [ nickname, email, password ]
        TODO? PUT request to /api/users/1    : Update user with ID of 1
        TODO? DELETE request to /api/users/1 : Delete user with ID of 1
        */

        // --------------------------------------------------------------------
        // Set up some variables
        // --------------------------------------------------------------------

        $method = $this->getRequestMethod();
        $results = null;

        // --------------------------------------------------------------------
        // Decide what to do
        // --------------------------------------------------------------------

        // GET
        if ('GET' == $method ) {

            if (empty($params[0])) {
                // Get all users
                $results = $this->user->get();
            }
            else {
                // Get a single user
                $results = $this->user->getByID($params[0], true);
                unset($results['password']);
            }
        }
        // POST
        elseif ('POST' == $method) {

            /* TODO: validate $_POST */

            $info = array(
                'nickname' => $_POST['nickname'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                );

            $id = $this->user->save(null, $info);

            // Results
            $results = array(
                'users_id' => $id,
                );

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 200, 'Insert Message here', 'text/html');
        }


    }


    // --------------------------------------------------------------------
    // Private functions,
    // --------------------------------------------------------------------


    /**
    * Get request method (GET, POST, PUT, DELETE)
    *
    */
    private function getRequestMethod() {

        $request_method = strtoupper($_SERVER['REQUEST_METHOD']);
        return $request_method;

    }


    /**
    * Send response
    *
    * @param string $body Content body
    * @param int $status Status code
    * @param string $message Status message
    * @param string $content_type Examples: text/html, application/xml, application/json
    */
	private function sendResponse($body, $status = 200, $message = null, $content_type = 'application/xml') {

	    if (!$message) $message = $this->getDefaultMessage($status);
		$status_header = 'HTTP/1.1 ' . $status . ' ' . $message;

		if ($content_type == 'application/xml') {

		    // Reformat the XML into something pretty
		    $dom = new DOMDocument('1.0');
		    $dom->preserveWhiteSpace = false;
		    $dom->loadXML($body);
		    $dom->formatOutput = true;

		    // Modify the response node
		    $modify = $dom->getElementsByTagName('response');
		    if ($modify->item(0)) {

		        $code = $dom->createAttribute("code");
		        $codeValue = $dom->createTextNode($status);
		        $code->appendChild($codeValue);

		        $msg = $dom->createAttribute("message");
		        $msgValue = $dom->createTextNode($message);
		        $msg->appendChild($msgValue);

		        $modify->item(0)->appendChild($code);
		        $modify->item(0)->appendChild($msg);

		    }

		    $body = $dom->saveXML();

		}

		header($status_header); // set the status
		header('Content-type: ' . $content_type); // set the content type

		$this->r->text['body'] = $body;
		$this->tpl->display('response.tpl');

	}


    /**
    * Default status messages
    *
    * @param int $status Status code
    */
	private function getDefaultMessage($status) {

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
		    505 => 'HTTP Version Not Supported',
		    );

		return (isset($codes[$status])) ? $codes[$status] : '';

	}


	/**
	* Convert an Array to XML
	*
	* @param array $data
	* @param string $rootNodeName - what you want the root node to be - defaultsto data.
	* @param SimpleXMLElement $xml - should only be used recursively, is a reference
	* @return string XML
	*/
	private static function array2Xml($data, $rootNodeName = 'response', &$xml = null) {

	    // Force an array
	    if (!is_array($data)) $data = array();

		if (is_null($xml)) {
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value) {

		    // no numeric keys in our xml please!
			if (is_numeric($key)) {
				// make string key...
				$key = "key_". (string) $key;
			}

			// delete any char not allowed in XML element names
			$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = $xml->addChild($key);
				// recrusive call.
				self::array2Xml($value, $rootNodeName, $node);
			}
			else {

				// add single node.
				$value = htmlentities($value);
				$xml->addChild($key,$value);
			}

		}

		return $xml->asXML();

	}


}


?>