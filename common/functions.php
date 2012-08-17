<?php
function unichr($u) {
	return mb_convert_encoding('&#' . $u . ';', 'UTF-8', 'HTML-ENTITIES');
}

function millis_time() {
	return intval(microtime(true)*1000);
}

function post_var($key, $return_non_null=true, $trim_if_string=true) {
	$val = isset($_POST[$key]) ? $_POST[$key] : false;
	
	if($val === false)
		return $return_non_null ? '' : null; 
	
	if(!is_array($val) ) 
		return $trim_if_string ? trim($val) : $val; 
	else 
		return $val;
}

function get_var($key, $return_non_null=true, $trim_if_string=true) {
	$val = isset($_GET[$key]) ? $_GET[$key] : false;
	
	if($val === false)
		return $return_non_null ? '' : null; 
	
	if(!is_array($val) ) 
		return $trim_if_string ? trim($val) : $val; 
	else 
		return $val;
}

function req_var($key, $return_non_null=true, $trim_if_string=true) {
	if(isset($_POST[$key]) ) 
		return post_var($key, $return_non_null, $trim_if_string);
	else 
		return get_var($key, $return_non_null, $trim_if_string); 
}