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
								if(is_numeric($params[0])) {
                   $results = $this->user->getByID($params[0], true);
                } else {
								   $results = $this->user->getByNickname($params[0], $full_profile = false);
								}
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

				//print_r($results);
				//exit;
				$resourceType = 'users';
				
        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($resourceType,$results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 302, 'Insert Message here', 'text/html');
        }


    }


    function feeds_api($params) {

        /*
        GET request to /api/feeds            : List all feeds
        GET request to /api/feeds/user/1     : List feeds for user with ID of 1
        TODO? POST request to /api/feeds/user/1    : Create a new feed for user with ID of 1
        TODO? PUT request to /api/feeds/feed/5    : Update feed with ID of 5
        TODO? DELETE request to /api/feeds/feed/5 : Delete feed with ID of 5
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
						include_once(dirname(__FILE__) . '/../feeds/feeds.php');
				    $feeds = new feeds();
            if (empty($params[0])) {
                // Get feeds for all users
                if (filter_var($action, FILTER_VALIDATE_INT) && $action > 0) $results = $feeds->listing($action);
                else $results = $feeds->listing();
            }
            else {
                // Get feeds for a single user
								$results = $feeds->user($params[0]);
            }
        }
        // POST
        elseif ('POST' == $method) {

            /* TODO: validate $_POST */

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

				print_r($results);
				exit;

				$resourceType = 'feeds';
				
        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($resourceType,$results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 302, 'Insert Message here', 'text/html');
        }


    }		
		
		

    function items_api($params) {

        /*
        GET request to /api/items            : List all items
        GET request to /api/items/?user=1    : List items for user with ID of 1
        */


        // --------------------------------------------------------------------
        // Set up some variables
        // --------------------------------------------------------------------

        $method = $this->getRequestMethod();
        $results = null;

        // --------------------------------------------------------------------
        // Decide what to do
        // --------------------------------------------------------------------

        if ('GET' == $method ) {
						   include_once(dirname(__FILE__) . '/../items/items.php');
				       $items = new items();
							 //$results = $items->user($params[1]);
									 
							 $username = $keywords = $threshold = $maxHits = $feed_id = $search = $vec_id = $cat_id = $start = '';
							 if(array_key_exists('user',$_GET)) $username=$_GET['user'];
							 else if(array_key_exists('userNickName',$_GET)) $username=$_GET['userNickName'];
									 
							 if($username!='') {

									 $URLparams = $this->getURLparameters($username);
	    						 foreach($URLparams as $key => $val) $$key = $val;
									 //exit;
									 $results = $items->userAPI($username); 

									 $filters='';
									 $arrayCats = array();
									 $URLparams = array();
									 $categories = '';
									 if($vec_id>0 || $cat_id>0) {
											$user_id = $items->user->getByNickname($username);
									    if($user_id > 0) {
											   $arrayCats = $items->getUserCategories($user_id, $cat_id);
											   foreach($arrayCats as $ar_cat) {
													 $filters .= 'Vector ID: '.$ar_cat['vec_id'].' ('.$ar_cat['vec_name'].'), Category ID: '.$ar_cat['cat_id'].' ('.$ar_cat['cat_name'].'); ';
												   $categories .= '<category domain="'.$ar_cat['vec_name'].'">'.$ar_cat['cat_name']."</category>\n";
												 }
											}
									 }

									 if($threshold>0) $filters .= ' Threshold: '.$threshold.'; ';									 
									 if(trim($feed_id)!='') $filters .= ' Feed: '.$feed_id.'; ';
									 if(trim($search)!='') $filters .= ' search: '.$search.'; ';
									 if($maxHits>0) $filters .= ' maxHits: '.$maxHits.' results; ';

									 $chDescrip = 'Use Case: Return the RSS Items for a User. User Nickname: '.$username.'. Summary of applied filters: '.substr($filters,0,-2);
									 
                   $rss = new suxRSS();
    							 $rss->outputRSS(ucfirst($username)."'s RSS Items$channelStr", 'http://icbl.macs.hw.ac.uk/sux0r206/user/profile/'.$username, $chDescrip, $categories);
    							 $selfURL = 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/'.$_GET['c'].$urlParam;
									 foreach($results as $arr) {

											$relevance = '';
											if(isset($arr['score'])) $relevance = $arr['score'];
											$thisFeed = $rss->getFeedByID($arr['rss_feeds_id']);
											$rss->addOutputItem($arr['title'], $arr['url'], $arr['body_html'],$arr['published_on'],$thisFeed['title'],$relevance, $thisFeed['url']);	
									 }

									 $rssOutput = $rss->saveXML();
									 $rssOutput = str_replace('<category>category</category>',$categories."    <atom:link href=\"$selfURL\" rel=\"self\" type=\"application/rss+xml\" />",$rssOutput);
									 $rssOutput = str_replace('<rss version="2.0">','<rss version="2.0" xmlns:api="http://icbl.macs.hw.ac.uk/sux0rAPI/api/xmlns" xmlns:atom="http://www.w3.org/2005/Atom">',$rssOutput);
									 header('Content-type: application/rss+xml');
									 echo $rssOutput;
									 exit;

								} else {
								   header('HTTP/1.1 400 Bad Request'); // set the status
									 header("Status: 404 Bad Reques");
									 header('Content-type: application/xml'); // set the content type
									 echo "<?xml version=\"1.0\"?>\n<response code=\"400\" message=\"Bad Request\">Required data (userNickname) has not been provided</response>";
									 exit;
                }

        }
        // POST
        elseif ('POST' == $method) {

            /* TODO: validate $_POST */

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

				print_r($results);
				exit;

				$resourceType = 'feeds';
				
        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($resourceType,$results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 302, 'Insert Message here', 'text/html');
        }


    }		
		
				
		

    function categories_api($params) {

        /*
        GET request to /api/categories            : List all categories
        GET request to /api/categories/?user=1    : List categories for user with ID of 1
        */

        // --------------------------------------------------------------------
        // Set up some variables
        // --------------------------------------------------------------------

        $method = $this->getRequestMethod();
        $results = null;

        // --------------------------------------------------------------------
        // Decide what to do
        // --------------------------------------------------------------------

        if ('GET' == $method ) {
						   include_once(dirname(__FILE__) . '/../items/items.php');
				       $items = new items();
							 //$results = $items->user($params[1]);
									 
							 $username = $vecName = $threshold = $maxHits = $feed_id = $search = $vec_id = $cat_id = $start = $rootElement = '';
							 if(array_key_exists('user',$_GET)) $username=$_GET['user'];
							 else if(array_key_exists('userNickName',$_GET)) $username=$_GET['userNickName'];
							 $rss = new suxRSS();
							 $rootElement = 'api:responseError';
							 $headerHead='HTTP/1.1 400 Bad Request';
							 $headerStatus = "Status: 404 Bad Reques";
							 if($username!='') {
                   $user_id = $items->user->getByNickname($username);
									 $URLparams = $this->getURLparameters($username);
	    						 foreach($URLparams as $key => $val) $$key = $val;
									 $filters='';
									 $arrayCats = array();
									 $URLparams = array();
									 $categories = '';
									 if($user_id > 0) {
									    $arrayCats = $items->getUserCategories($user_id, $cat_id);
											if($vec_id>0) $rootElement = 'api:categories';
									 		$rss->outputRSSX($username, $rootElement);
											if($vec_id>0) {
									       $array = array();
												 foreach($arrayCats as $ar_cat) {
													  if($ar_cat['vec_id']==$vec_id) {
															 $vecName = $ar_cat['vec_name'];
															 $array[$ar_cat['cat_id']] = $ar_cat['cat_name'];
														}
									       }
												 if(sizeof($array)>0) {
												    $headerHead='HTTP/1.1 200 OK';
												    $headerStatus = "Status: 200 OK";
												    $rss->addOutputItemX('api:vector', '','', '', '', '', 'api:vectorID', 'api:vectorName', $vec_id, $vecName);
												 		foreach($array as $k => $v) {
													     $rss->addOutputItemX('api:category', '','api:categoryID', 'api:categoryName', $k, $v);
												    }
												 } else {
                            $rss->addOutputItemX('api:responseErrorCode', '406');
											      $rss->addOutputItemX('api:responseErrorMessage', 'Vector ID '.$vec_id.' doesn\'t belong to this user. Use below linkData to fetch a list of vectors for '.$username); 
												    $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and names of vectors created by '.$username, 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/vectors/?user='.$username);
												 }
											} else {
                         $rss->addOutputItemX('api:responseErrorCode', '406');
											   $rss->addOutputItemX('api:responseErrorMessage', 'Vector identification has not been provided. Use below linkData to fetch a list of vectors for '.$username); 
									       $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and names of vectors created by '.$username, 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/vectors/?user='.$username);

											}
									 } else {
									    $rss->outputRSSX('', $rootElement);
									    $rss->addOutputItemX('api:responseErrorCode', '400');
											$rss->addOutputItemX('api:responseErrorMessage', 'Required data (userNickname) has not been provided'); 
									    $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and nickNames of registered users', 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/users/');
									 }

								} else {

									 $rss->outputRSSX('', $rootElement);
									 $rss->addOutputItemX('api:responseErrorCode', '400');
									 $rss->addOutputItemX('api:responseErrorMessage', 'Required data (userNickname) has not been provided'); 
									 $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and nickNames of registered users', 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/users/');
                }
								header($headerHead);
								header($headerStatus);
								header('Content-type: application/xml'); 
								$rssOutput = $rss->saveXML();
								echo $rssOutput;
								exit;

        }
        // POST
        elseif ('POST' == $method) {

            /* TODO: validate $_POST */

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

				print_r($results);
				exit;

				$resourceType = 'feeds';
				
        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($resourceType,$results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 302, 'Insert Message here', 'text/html');
        }


    }		
		
			
		
		
				
		

    function vectors_api($params) {

        /*
        GET request to /api/vectors            : List all vectors
        GET request to /api/vectors/?user=1    : List vectors for user with ID of 1
        */

        // --------------------------------------------------------------------
        // Set up some variables
        // --------------------------------------------------------------------

        $method = $this->getRequestMethod();
        $results = null;

        // --------------------------------------------------------------------
        // Decide what to do
        // --------------------------------------------------------------------

        if ('GET' == $method ) {
						   include_once(dirname(__FILE__) . '/../items/items.php');
				       $items = new items();
 
							 $username = $vecName = $threshold = $maxHits = $feed_id = $search = $vec_id = $cat_id = $start = $rootElement = '';
							 if(array_key_exists('user',$_GET)) $username=$_GET['user'];
							 else if(array_key_exists('userNickName',$_GET)) $username=$_GET['userNickName'];
							 $rss = new suxRSS();
							 $rootElement = 'api:responseError';
							 $headerHead='HTTP/1.1 400 Bad Request';
							 $headerStatus = "Status: 404 Bad Reques";
							 if($username!='') {
                   $user_id = $items->user->getByNickname($username);
									 $URLparams = $this->getURLparameters($username);
	    						 foreach($URLparams as $key => $val) $$key = $val;
									 $filters='';
									 $arrayCats = array();
									 $URLparams = array();
									 $categories = '';
									 if($user_id > 0) {
									    $arrayCats = $items->getUserCategories($user_id, $cat_id);
											if(sizeof($arrayCats)>0) $rootElement = 'api:vectors';
									 		$rss->outputRSSX($username, $rootElement);
											if(isset($arrayCats) && sizeof($arrayCats)>0) {
											    $headerHead='HTTP/1.1 200 OK';
												  $headerStatus = "Status: 200 OK";
									        $array = array();
													foreach($arrayCats as $ar_cat) {
														  $array[$ar_cat['vec_id']] = $ar_cat['vec_name'];
									        }
													$array = array_unique($array);
													foreach($array as $k => $v) {
														   $rss->addOutputItemX('api:vector', '','', '', '', '', 'api:vectorID', 'api:vectorName', $k, $v);
									        }
											} else {
                            $rss->addOutputItemX('api:responseErrorCode', '406');
											      $rss->addOutputItemX('api:responseErrorMessage', 'Vector ID '.$vec_id.' doesn\'t belong to this user. Use below linkData to fetch a list of vectors for '.$username); 
												    $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and names of vectors created by '.$username, 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/vectors/?user='.$username);
											}
									 } else {
									    $rss->outputRSSX('', $rootElement);
									    $rss->addOutputItemX('api:responseErrorCode', '400');
											$rss->addOutputItemX('api:responseErrorMessage', 'Required data (userNickname) has not been provided'); 
									    $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and nickNames of registered users', 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/users/');
									 }


								} else {

									 $rss->outputRSSX('', $rootElement);
									 $rss->addOutputItemX('api:responseErrorCode', '400');
									 $rss->addOutputItemX('api:responseErrorMessage', 'Required data (userNickname) has not been provided'); 
									 $rss->addOutputItemX('api:linkData', '','', '', '', '', 'api:linkDataDescription', 'api:linkDataURL', 'URL for fetching the IDs and nickNames of registered users', 'http://icbl.macs.hw.ac.uk/sux0rAPI/icbl/api/users/');
                }
								header($headerHead);
								header($headerStatus);
								header('Content-type: application/xml'); 
								$rssOutput = $rss->saveXML();
								echo $rssOutput;
								exit;

        }
        // POST
        elseif ('POST' == $method) {

            /* TODO: validate $_POST */

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

				print_r($results);
				exit;

				$resourceType = 'feeds';
				
        if (is_array($results)) {
            // Array
            $this->sendResponse(self::array2Xml($resourceType,$results), 200);
        }
        else {
            // TODO?
            $this->sendResponse($results, 302, 'Insert Message here', 'text/html');
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



    private function getURLparameters($username) {

        $arrayLabels = array('vec_id'=>'Vector ID: ','cat_id'=>'Category ID: ', 'threshold'=>'Threshold: ', 'feed_id'=>'Feed: ', 'search'=> 'Keywords: ', 'maxHits'=>'maxHits: ','keywords'=> 'Keywords: ',);

				$pathURL = explode("&",$_SERVER['REQUEST_URI']);
				//print_r($pathURL);
				array_shift($pathURL);
				$urlParam = '?user='.$username;
				$channelStr = '';
				$URLparams = array();
				foreach($pathURL as $val) {
				  $arr = explode('=',$val);
					if(sizeof($arr)==2) {
					   $key = str_replace('amp;','',$arr[0]);
				     $channelStr .= $arrayLabels[$key].$arr[1].', ';
						 $urlParam .= '&amp;'.$key.'='.$arr[1];
						 $URLparams[$key] = $arr[1];
					}
				}
				if(isset($URLparams['keywords'])) $URLparams['search'] = $URLparams['keywords'];
				if($channelStr!='') $channelStr = substr($channelStr,0,-2);
				$URLparams['channelStr'] = $channelStr;
				$URLparams['urlParam'] = $urlParam;		
				//print_r($URLparams);
        return $URLparams;

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
		//$status_header = 'HTTP/1.1 400 ' . $message;
		
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
		    200 => 'OK. Standard response for successful HTTP request',
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
	private static function array2Xml($resourceType, $data, $rootNodeName = 'response', &$xml = null) {

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
				$key = $resourceType;// "key_". (string) $key;
			}

			// delete any char not allowed in XML element names
			$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = $xml->addChild($key);
				// recrusive call.
				self::array2Xml('', $value, $rootNodeName, $node);
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