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

function lucene_get_field_type_from_doc($view_doc, $field) {
	if(empty($view_doc)) return '';
	
	$matches = array();
	
	if(!preg_match("/field[ ]*:[ ]*('|\"|\\\"|\\\')".$field."('|\"|\\\"|\\\')/", $view_doc, $matches)) {
		return '';
	} 

	$inx = strpos($view_doc, $matches[0]);

	for(;$inx >= 0 && substr($view_doc, $inx, 1) != '{';$inx--) {}

	$start = $inx;

	$len = strlen($view_doc);
	for(;$inx < $len && substr($view_doc, $inx, 1) != '}';$inx++) {}

	$end = $inx;

	$view_doc = substr($view_doc, $start, $end-$start+1);

	$type = '';
	if(preg_match("/type[ ]*:[ ]*('|\"|\\\"|\\\')([a-zA-Z]*)('|\"|\\\"|\\\')/", $view_doc, $matches)) {
		$type = $matches[2];
	} 
	
	return $type;
}

function couch_get_view_result_key_formats($result) {
	$formats = array();
	foreach($result->rows as $row) {
		$format = couch_get_view_result_key_format($row->key);
		$formats[$format] = true;
	}
	return array_keys($formats);
}

function couch_get_view_result_key_format($key) {
	if(!is_array($key)) {
		return strtolower(gettype($key));
	}
	else {
		$format = '';
		foreach($key as $ki) {
			$format .= (!empty($format)?', ':'').couch_get_view_result_key_format($ki);
		}
	}
	
	return '['.$format.']';
}

function couch_get_map_func_key_formats($map_func) {
	return array();
}

function json_encode_and_format($item) {
	return _json_encode_and_format($item, 0);
}

function _json_encode_and_format($item, $level) {
	$margin = 'margin-left: 20px;';
	
	if(is_object($item)) {
		$vars = get_object_vars($item);
		$str = '';
		foreach($vars as $k=>$v) {
			$str .= !empty($str)?',<br />':'';
			$str .= '"<span>'.$k.'</span>": ';
			$str .= _json_encode_and_format($v, $level+1);
		}
		$str .= !empty($str)?'<br />':'';
		return '{<div style="'.$margin.'">'.$str.'</div>}';
	}
	else if(is_array($item)) {
		$str = '';
		foreach($item as $k=>$v) {
			$str .= !empty($str)?', ':'';
			$str .= _json_encode_and_format($v, $level+1);
		}
		return '[<div style="'.$margin.'">'.$str.'</div>]';
	}
	else if(is_string($item)) {
		return '<span style="color: darkmagenta;">"'.$item.'"</span>';
	}
	else if(is_bool($item)) {
		return '<span style="color: midnightblue;">'.($item?'true':'false').'</span>';
	}
	else if(is_numeric($item)) {
		return '<span style="color: green;">'.$item.'</span>';
	}
	else if(is_null($item)) {
		return '<span style="color: dimgray;">null</span>';
	}
}