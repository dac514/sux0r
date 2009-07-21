<?php

/**
* controller
*
* @author     Santy Chumbe <chumbe@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = null) {
    switch($action)  {
       case 'userregistration' :

        // --------------------------------------------------------------------
        // Register new User from API
        // --------------------------------------------------------------------

				include_once('modules/user/userEdit.php');
        if(!empty($params[0]) && $params[0] == 'requestToken') {

				    // Request anti-spoofing token
						
						$reg = new userEdit();
						$reg->apiFormBuild($_POST, $_GET);
				
				} else if(!empty($params[0]) && $params[0] == 'requestRegistration') {
				    
				    // Regular registration

						$reg = new userEdit();
						if ($reg->apiFormValidate($params)) {
						    $params[3] = str_replace('__','.',str_replace('___','@',$params[3]));
                $someuser = array('nickname' => $params[2], 'email' => $params[3], 'password' => $params[4]);
								print_r($someuser);
								echo "<br>sessionToke: ".$_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token'];
								//$user = new suxUser();
	 							//$user->save(null, $someuser);
            } else {
						    echo "<p>Tokens didn't match</p>\n";
						    print_r($params);
								echo "<br>sessionToke: ".$_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token'];
								//$reg = new userEdit();
								//$reg->apiFormBuild($_POST, $_GET);
						}
				
				} else {

						echo "action: $action<br>\n";
						print_r($params);

        }
				exit;
        break;		
		
		
       default:
        include_once('modules/api/api.php');
        $monjours = new monjours();
        $monjours->display();
    }
}
?>