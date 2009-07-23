<?php

/**
* api
*
* @author     Santy Chumbe <chumbe@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

/*

Example of a REST API, loosely inpired from:
http://www.gen-x-design.com/archives/create-a-rest-api-with-php/
http://restpatterns.org/HTTP_Status_Codes

GET request to /api/users : List all users
GET request to /api/users/1 : List info for user with ID of 1
POST request to /api/users/nickname/email/password : Create a new user
PUT request to /api/users/1 : Update user with ID of 1
DELETE request to /api/users/1 : Delete user with ID of 1

*/

require_once('rest.php');
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


    /**
    * Display default welcome screen
    */
    function welcome() {

        $this->tpl->display('welcome.tpl');

    }


    function users_restApi($params) {

        // --------------------------------------------------------------------
        // Instatiate REST
        // --------------------------------------------------------------------

        $data = RestUtils::processRequest();
        $method = $data->getMethod();
        $results = null;

        // --------------------------------------------------------------------
        // Decide what to do
        // --------------------------------------------------------------------

        // GET
        if ('get' == $method ) {

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
        elseif ('post' == $method) {

            $info = array(
                'nickname' => $data->getData()->nickname,
                'email' => $data->getData()->email,
                'password' => $data->getData()->password,
                );

            $id = $this->user->save(null, $info);

        }


        // --------------------------------------------------------------------
        // Send output
        // --------------------------------------------------------------------

        // Test
        // RestUtils::sendResponse(401);
        // exit;

        // JSON
        if ($data->getHttpAccept() == 'json') {

            RestUtils::sendResponse(200, json_encode($results), 'application/json');

        }
        // XML
        else {

            RestUtils::sendResponse(200, ArrayToXML::toXml($results), 'application/xml');

        }


    }



}


?>