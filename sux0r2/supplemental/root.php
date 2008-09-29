<?php

require_once(dirname(__FILE__)  . '/../config.php'); // Configuration
require_once(dirname(__FILE__)  . '/../initialize.php'); // Init

// ---------------------------------------------------------------------------
// Do the dirty
// ---------------------------------------------------------------------------

$u = new suxUser();
$errors = array();
$rooted = false;

if (isset($_POST) && count($_POST)) {

    // Nickname
    if (empty($_POST['nickname'])) $errors[] = 'nickname cannot be empty';
    else {
        if (!preg_match('/^(\w|\-)+$/', $_POST['nickname'])) $errors[] = 'nickname has invalid characters';
        if (mb_strtolower($_POST['nickname']) == 'nobody') $errors[] = 'nickname cannot be reserved word nobody';
        $tmp = $u->getUserByNickname($_POST['nickname']);
        if ($tmp !== false ) $errors[] = 'duplicate nickname found';
    }

    // Email
    if (empty($_POST['email'])) $errors[] = 'email cannot be empty';
    else {
        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'invalid email';
        $tmp = $u->getUserByEmail($_POST['email']);
        if ($tmp !== false ) $errors[] = 'duplicate email found';
    }

    // Password
    if (empty($_POST['password'])) $errors[] = 'password cannot be empty';
    else {
        if (mb_strlen($_POST['password'], 'UTF-8') < 6) $errors[] = 'minimum 6 characters for password';
        else {
            if ($_POST['password'] != $_POST['password_verify']) $errors[] = 'passwords do not match';
        }
    }

    // Salt
    if (empty($_POST['salt'])) $errors[] = 'salt cannot be empty';
    else {
        if ($_POST['salt'] != $GLOBALS['CONFIG']['SALT']) $errors[] = 'salt does not match config.php';
    }


    if (!count($errors)) {

        $clean['nickname'] = $_POST['nickname'];
        $clean['email'] = $_POST['email'];
        $clean['password'] = $_POST['password'];

        $uid = $u->saveUser($clean);
        $u->root($uid);
        $rooted = true;

    }


}

// ---------------------------------------------------------------------------
// Inline, horrible inline
// ---------------------------------------------------------------------------

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title><?php echo $GLOBALS['CONFIG']['TITLE']; ?> - Create a Root User</title>
    <style type="text/css">
    body  { font-family: Arial, Helvetica, sans-serif; background-color: #ffffff; width: 60% }
    a:link, a:visited, a:active { font-weight: bold; color: #000000; text-decoration: underline; }
    a:hover { color: #ffffff; background: #000000; }
    .error { color: red; }
    .success { color: green; }
    .warning { font-weight: bold; background-color: yellow; }
    </style>
</head>
<body>

<h1><?php echo $GLOBALS['CONFIG']['TITLE']; ?> - Create a Root User</h1>

<?php
if (count($errors)) foreach($errors as $err) {
    echo "<span class='error'>$err </span><br />";
}

if ($rooted) {
    echo "<span class='success'>root user '{$clean['nickname']}' successfully created!</span>";
    unset($_POST);
}
?>

<p>
<strong>IMPORTANT:</strong> Make sure <em class="warning">$CONFIG['REALM']</em>
is correctly set in <em class="warning">config.php</em> before
continuing. This variable is used to encrypt passwords and isn't something you
can casually change without having to reset every user/password in your database.
</p>

<p>
<strong>CRUCIAL:</strong> Delete this script <em>(<?php echo $_SERVER['PHP_SELF']; ?>)</em>
from the server when you are finished.
</p>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" name="default" method="post" accept-charset="utf-8">

<p>
nickname:
<input type="text" name="nickname" value="<? echo @$_POST['nickname']; ?>"  />
</p>

<p>
email:
<input type="text" name="email" value="<? echo @$_POST['email']; ?>" />
</p>

<p>
password:
<input type="password" name="password" value="" />
</p>

<p>
verify password:
<input type="password" name="password_verify" value="" />
</p>

<p>
salt:
<input type="password" name="salt" value=""  />
<small>(<em class="warning">$CONFIG['SALT']</em>, located in <em class="warning">config.php</em>)</small>
</p>

<input type="submit" class="button" value="Submit" />

</form>

</body>
</html>