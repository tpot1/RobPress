<?php

/** Prepare timestamp for MySQL insertion */
function mydate($timestamp=0) {
	if(empty($timestamp)) { $timestamp = time(); }
	if(!is_numeric($timestamp)) { $timestamp = strtotime($timestamp); }
	return date("Y-m-d H:i:s",$timestamp);
}

/** Prepare timestamp for nice display */
function nicedate($timestamp=0) {
	if(empty($timestamp)) { $timestamp = time(); }
	if(!is_numeric($timestamp)) { $timestamp = strtotime($timestamp); }
	return date("l jS \of F Y H:i:s",$timestamp);
}

/** HTML escape content */
function h($text) {
	return htmlspecialchars($text);
}

function randomCode($length){

	$chars = str_split('abcdefghijklmnopqrstuvwxyz'		
                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                 .'0123456789!@#$%^&*()');			

	$rand = array_rand($chars, $length);		//also comprised of a random string of characters, since the unique part can be easily worked out
	$code = "";
	foreach ($rand as $key) {
		$code = $code . $chars[$key];
	}
	return uniqid($code, true);
}

/** Declare constants */
if (isset($_SERVER['BASE'])) { define('BASE',$_SERVER['BASE']); } else { define('BASE','/'); }

?>
