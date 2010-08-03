<?

/**
 * Exapmle of akCaptcha
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akCaptcha.class.php';

session_start();

if (isset($_GET['gen'])) {
	echo akCaptcha::getInstance();
} elseif (isset($_GET['flush'])) {
	unset($_SESSION[akCaptcha::sessionKey]);
} else {
	$captchaList = &$_SESSION[akCaptcha::sessionKey];
	var_dump($captchaList);
}

?>
<a href="?gen">Only captcha generation</a><br />
<a href="?flush">Delete all captcha from SESSION</a><br />
<a href="">Show session captcha's</a><br />

<img src="?gen" alt="akCaptcha" title="akCaptcha" />