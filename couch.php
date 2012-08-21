<?php
include_once('config.php');
include_once('common/functions.php');

$cmd = post_var('cmd');

if(!empty($cmd)) {
	if($cmd == 'query_view') {
		$opts = array();
		
		$ddoc = post_var('ddoc_id');
		$view = post_var('view');	
		
		if(empty($ddoc) || empty($view)) {
			exit();
		}
		
		$url = '';
		
		$ksel = post_var('ksel');
		if($ksel == 'key') { 
			$kval = stripslashes(post_var('key'));
			if(!empty($kval)) {
				$opts['key'] = json_decode($kval);
				$url .= (!empty($url)?'&':'').'key='.$kval;
			}
		} 
		else if($ksel == 'keys') { 
			$kval = '['.stripslashes(post_var('keys')).']';
			if(!empty($kval)) {
				$opts['keys'] = json_decode($kval);
				$url .= (!empty($url)?'&':'').'keys='.$kval;
			}
		}
		else if($ksel == 'sekeys') {
			$kval = stripslashes(post_var('startkey'));
			if(!empty($kval)) {
				$opts['startkey'] = json_decode($kval);
				$url .= (!empty($url)?'&':'').'startkey='.$kval;
			}
			
			$kval = stripslashes(post_var('endkey'));
			if(!empty($kval)) {
				$opts['endkey'] = json_decode($kval);
				$url .= (!empty($url)?'&':'').'endkey='.$kval;
			}
		}
		
		$desc = intval(post_var('descending'));
		$opts['descending'] = !empty($desc);
		if($opts['descending'])
			$url .= (!empty($url)?'&':'').'descending=true';
		
		$incl_docs = intval(post_var('include_docs'));
		$opts['include_docs'] = !empty($incl_docs);
		if($opts['include_docs'])
			$url .= (!empty($url)?'&':'').'include_docs=true';
			
		$limit = post_var('limit');
		if(strlen($limit) > 0 && is_numeric($limit)) {
			$opts['limit'] = intval($limit);
			
			if($opts['limit'] > 0)
				$url .= (!empty($url)?'&':'').'limit='.$opts['limit'];
		}
		
		$skip = post_var('skip');
		if(strlen($skip) > 0 && is_numeric($skip)) {
			$opts['skip'] = intval($skip);
			
			if($opts['skip'] > 0)
				$url .= (!empty($url)?'&':'').'skip='.$opts['skip'];
		}
		
		$reduce = post_var('reduce');
		if(strlen($reduce) > 0) {
			$opts['reduce'] = (bool) intval($reduce);
			$url .= (!empty($url)?'&':'').'reduce='.($opts['reduce']===true ? 'true' : 'false');
		
			if($opts['reduce']) {
				$group = intval(post_var('group'));
				if(!empty($group)) {
					$opts['group'] = true;
					$url .= (!empty($url)?'&':'').'group=true';
				}
				
				$group_level = post_var('group_level');
				if(strlen($group_level) > 0 && is_numeric($group_level) && intval($group_level) > 0) {
					$opts['group_level'] = intval($group_level);
					$url .= (!empty($url)?'&':'').'group_level='.$opts['group_level'];
				}
			}
		}

		/* echo '<pre>'.print_r($ddoc, true).'</pre>';
		echo '<pre>'.print_r($view, true).'</pre>';
		echo '<pre>'.print_r($opts, true).'</pre>'; */
		
		list($pref, $postf) = explode('/', $ddoc);
		
		$start_time = millis_time();
		
		try {
			$res = $cdb->setQueryParameters($opts)->getView($postf, $view);
		}
		catch(Exception $e) {
			echo '<div class="qr_item">'.$e->getCode().': '.$e->getMessage().'</div>';
			exit();
		}
	
		$time_diff = millis_time()-$start_time;
		
		$url_parts = parse_url($cdb->getDatabaseUri());
		if($url_parts === FALSE) 
			$url = 'http://localhost:5984/beta4/'.$ddoc.'/_view/'.$view.'?'.$url;
		else {
			$url2 = empty($url_parts['scheme'])?'http://':$url_parts['scheme'].'://';
			
			if(!empty($url_parts['user']) || !empty($url_parts['pass']))
				$url2 .= 'user:pass@';
				
			$url2 .= $url_parts['host'].':'.$url_parts['port'].$url_parts['path'];

			$url = $url2.'/'.$ddoc.'/_view/'.$view.'?'.$url;
		}
		
		echo '<div class="qr_item">REST URL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$url.'</div>';
		echo '<div class="qr_item">QUERY TIME:&nbsp;&nbsp;~'.intval($time_diff/1000).' sec ('.$time_diff.' ms)</div>';
		
		$res_format = post_var('result_format');
		if($res_format == 'raw') {
			echo '<div class="qr_item">QUERY RESULT:</div>';
			echo '<div class="qr_item">'.json_encode_and_format($res).'</div>';
		}
		else if($res_format == 'tree') {
			echo '<div class="qr_item">TOTAL ROWS:&nbsp;&nbsp;&nbsp;'.$res->total_rows.'</div>';
			echo '<div class="qr_item">OFFSET:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$res->offset.'</div>';
			echo '<div class="qr_item">RESULT ROWS:&nbsp;&nbsp;'.count($res->rows).'</div>';
			echo '<div class="qr_item">QUERY RESULT:</div>';
			$cnt = 0;
			foreach($res->rows as $row) {
				echo '<div class="qr_item">';
					echo '<div class="qr_tree_item clickable">'.json_encode($row->key).'</div>';
					echo '<div class="qr_tree_content"'.(++$cnt>1?' style="display: none;"':'').'>'.json_encode_and_format($row).'</div>';
				echo '</div>';
			}
		}
	}
	else if($cmd == 'ddoc_views') {
		$ddoc = post_var('ddoc_id');	
		if(empty($ddoc)) {
			echo json_encode(array('views'=>array()));
			exit();
		}
		
		try {
			$ddoc = $cdb->getDoc($ddoc);
			
			$views = array();
			if(!empty($ddoc->views)) {
				$views = get_object_vars($ddoc->views);
				$views = array_keys($views);
			}
			
			echo json_encode(array('views'=>$views));
		} 
		catch(Exception $e) {
			echo json_encode(array('error'=>true));
		}
	}
	else if($cmd == 'ddoc_view_info') {
		$ddoc_id = post_var('ddoc_id');	

		$view = post_var('view');	
		if(empty($ddoc_id) || empty($view)) {
			echo json_encode(array('error'=>true));
			exit();
		}
		
		try {	
			list($pref, $postf) = explode('/', $ddoc_id);
			
			$res = $cdb->setQueryParameters(array('endkey'=>new stdClass(), 'limit'=>300, 'reduce'=>false, 'include_docs'=>false))
						->getView($postf, $view);
				
			$ddoc = $cdb->getDoc($ddoc_id);
			$map_func = @$ddoc->views->{$view}->map;
				
			$info = array();
			$info['has_reduce']	= !empty($ddoc->views->{$view}->reduce);

			if(!empty($res->rows[0])) {
				$info['key_formats'] = couch_get_view_result_key_formats($res);
			}
			else { //get generic key format from map function
				$info['key_formats'] = couch_get_map_func_key_formats($map_func);
			}
			
			echo json_encode(array('info'=>$info));
		} 
		catch(Exception $e) {
			echo json_encode(array('error'=>true));
			exit();
		}
	}

	
	exit();
}

$design_docs = array();
try {
	$res = $cdb->setQueryParameters(array('startkey'=>'_design/', 'endkey'=>'_design/'.unichr(0xFFF0)))
				->getAllDocs();
				
	foreach($res->rows as $row) {
		if(strpos($row->id, '_design/lucene') === 0) continue ;
		
		$design_docs[] = $row->id;
	}
} 
catch(Exception $e) {
	die('Failed retrieving all design documents');
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>CouchDB Query Helper</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
<style type="text/css">
body { font-family: Courier New; font-size: 14px; }
input, select, textarea { border: 1px solid #CCC; font-family: Courier New; margin-bottom: 3px; font-size: 12px; padding: 5px 3px; }
button { background-color: #E9E9E9; border: 1px solid #CCC; font-family: Courier New;  }
button:hover { background-color: #F0F0F0; }

.clickable { cursor: pointer; }
.float_left { float: left; }
.float_right { float: right; }
.clear { clear: both; }

.ksel_tab textarea { width: 650px; height: 15px; }
#query_result .qr_item { font-family: Courier New; font-size: 12px; margin-bottom: 5px; }
#query_result .qr_tree_item { font-weight: bold; }
#query_result .qr_tree_item:hover { background-color: #F9F9F9; }
#query_result .qr_tree_content { padding-left: 20px; }
#limit_input, #skip_input { width: 40px; }
#group_lvl_input { width: 40px; }

.ddoc_view_key { display: inline-block; padding: 3px 5px; margin-bottom: 3px; font-size: 12px; background-color: #F0F0F0; font-weight: bold; cursor: pointer; }
</style>
<script type="text/javascript">
$(function() {

	$('.ksel_rad').change(function() {
		var type = $(this).val();
		
		$('.ksel_tab').hide();
		$('#ksel_tab_'+type).show();
	});
	
	$('#ddoc_sel').change(function() {
		$('#ddocview_sel').html('<option value="">Loading...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>');
		
		var ddoc = $(this).val();
		$.ajax({
			url: 'couch.php',
			type: 'POST',
			dataType: 'json',
			data: {'cmd': 'ddoc_views', 'ddoc_id': ddoc},
			success: function(resp) {
				if(!resp || resp.error) {
					$('#ddocview_sel').html('<option value="">-- Not Found --</option>');
				}
				
				$('#ddocview_sel').html('<option value="">-- Choose One --</option>');
				for(var i in resp.views) {
					$('#ddocview_sel').append('<option value="'+resp.views[i]+'">'+resp.views[i]+'</option>');
				}
			},
			error: function() {
				$('#ddocview_sel').html('<option value="">-- Not Found --</option>');
			}
		});
		
		$('#ddoc_view_info').html('Select design doc &amp; view...');
		$('#reduce_controls').data('has_reduce', false).hide();
	});
	
	$('#ddocview_sel').change(function() {
		var view = $(this).val();
		
		if(view.length == 0) {
			$('#ddoc_view_info').html('Select design doc &amp; view...');
			$('#reduce_controls').data('has_reduce', false).hide();
		} else {
			$('#ddoc_view_info').html('Loading...');
			
			var params = {'cmd': 'ddoc_view_info'};
			
			params.ddoc_id = $('#ddoc_sel').val();
			params.view = $('#ddocview_sel').val();

			$.ajax({
				url: 'couch.php',
				type: 'POST',
				dataType: 'json',
				data: params,
				success: function(resp) {
					if(!resp || resp.error) {
						$('#ddoc_view_info').html('Error getting view info!');	
					}
					else {
						var dvi = $('#ddoc_view_info');
						
						var ct = '<div>Key format(s): ';
						if(resp.info.key_formats && resp.info.key_formats.length > 0) {
							for(var i in resp.info.key_formats) {
								ct += '<div class="ddoc_view_key" val="'+resp.info.key_formats[i]+'">'+resp.info.key_formats[i]+'</div>&nbsp;';
							}
						}
						else {
							ct += 'need at least one relevant document to determine key format';
						}
						ct += '</div>';
						
						dvi.html(ct).find('.ddoc_view_key').click(function() {
							var val = $('.ksel_rad:checked').val();
							if(val == 'key') {
								$('#ksel_input_key').val($(this).attr('val'));
							} else if(val == 'keys') {
								$('#ksel_input_keys').val($(this).attr('val'));
							} else if(val == 'sekeys') {
								$('#ksel_input_startkey').val($(this).attr('val'));
								$('#ksel_input_endkey').val($(this).attr('val'));
							}
						});
						
						if(resp.info.has_reduce) {
							$('#reduce_controls').data('has_reduce', true).show();
						} else {
							$('#reduce_controls').data('has_reduce', false).hide();
						}
					}	
				},
				error: function() {
					$('#ddoc_view_info').html('Error getting view info!');	
				}
			});
		}
	});
	
	$('#btn_query').click(function(evt) {
		evt.preventDefault();
		
		$('#query_loading').show();
		
		var params = {'cmd': 'query_view'};
		
		params.ddoc_id = $('#ddoc_sel').val();
		params.view = $('#ddocview_sel').val();
		
		params.ksel = $('.ksel_rad:checked').val();
		if(params.ksel == 'sekeys') {
			params.startkey = $('#ksel_input_startkey').val();
			params.endkey = $('#ksel_input_endkey').val();
		} 
		else if(params.ksel == 'keys') {
			params.keys = $('#ksel_input_keys').val();
		}
		else if(params.ksel == 'key') {
			params.key = $('#ksel_input_key').val();
		}
		
		params.include_docs = $('#incdoc_input').is(':checked') ? 1 : 0;
		params.descending = $('#desc_input').is(':checked') ? 1 : 0;
		params.limit = $('#limit_input').val();
		params.skip = $('#skip_input').val();
		
		params.result_format = 'raw';
		if($('#qr_fmt_tree').is(':checked')) {
			params.result_format = 'tree';
		}
		
		if($('#reduce_controls').data('has_reduce')) {
			params.reduce = $('#reduce_input').is(':checked') ? 1 : 0;
			if(params.reduce == 1) {
				params.group = $('#group_input').is(':checked') ? 1 : 0;
				params.group_level = $('#group_lvl_input').val();
			}
		}
		
		$.ajax({
			url: 'couch.php',
			type: 'POST',
			dataType: 'html',
			data: params,
			success: function(resp) {
				if(params.result_format == 'tree') {
					resp = $(resp);
					$('.qr_tree_item', resp).click(function() {
						var ct = $(this).siblings('.qr_tree_content');
						if(ct.is(':visible')) ct.hide();
						else ct.show();
						ct = null;
					});
				}
				$('#query_result').html(resp);
				$('#query_loading').hide();
			},
			error: function() {
				$('#query_result').html('');
				$('#query_loading').hide();
			}
		});
	});
	
	$('#btn_qtb_font_larger, #btn_qtb_font_smaller').click(function(evt) {
		evt.preventDefault();
		
		if($('#query_result > .qr_item').size() == 0) return ;
		
		var inc = $(this).attr('id') == 'btn_qtb_font_smaller' ? -1 : 1;
		
		var fontsize = $('#query_result > .qr_item').css('font-size');
		fontsize = parseInt(fontsize)+inc;
		$('#query_result > .qr_item').css('font-size', fontsize+'px');
	});
	
	$('#reduce_input').change(function() {
		if(!$(this).is(':checked')) {
			$('#group_input, #group_lvl_input').attr('disabled', 'disabled');
		} else {
			$('#group_input, #group_lvl_input').removeAttr('disabled', 'disabled');
		}
	});
	
	$('form')[0].reset();
	$('#group_input, #group_lvl_input').attr('disabled', 'disabled'); //some bug in FF 
});
</script>
</head>
<body>
	<form>
		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="ddoc_sel"><b>Design Doc</b></label>
			<select id="ddoc_sel">
				<option value="">-- Choose One --</option>
			<?php 
				foreach($design_docs as $ddoc) {
					list($pref, $postf) = explode('/', $ddoc);
					echo '<option value="'.$ddoc.'">'.$postf.'</option>';
				}
			?>
			</select>
		</div>
		
		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="ddocview_sel"><b>View</b></label>
			<select id="ddocview_sel">
				<option value="">-- Not Found --</option>
			</select>
		</div>
		
		<div class="clear"></div>
		
		<div style="margin-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="vertical-align: top;">
						<div style="margin: 0 30px 0 0;">
							<input id="ksel_key" class="ksel_rad" name="ksel" type="radio" value="key" checked="checked" />
							<label for="ksel_key"><b>Key</b></label>
							
							<input id="ksel_keys" class="ksel_rad" name="ksel" type="radio" value="keys" />
							<label for="ksel_keys"><b>Keys</b></label>
							
							<input id="ksel_sekeys" class="ksel_rad" name="ksel" type="radio" value="sekeys" />
							<label for="ksel_sekeys"><b>Start/End Keys</b></label>
							
							<div style="padding-top: 10px;">
								<div id="ksel_tab_key" class="ksel_tab">
									<textarea id="ksel_input_key"></textarea>
								</div>
								<div id="ksel_tab_keys" class="ksel_tab" style="display: none;">
									[<textarea id="ksel_input_keys"></textarea>]
								</div>
								<div id="ksel_tab_sekeys" class="ksel_tab" style="display: none;">
									<table border="0">
										<tr><td><label for="ksel_input_startkey">Start&nbsp;</label></td><td><textarea id="ksel_input_startkey"></textarea></td></tr>
										<tr><td><label for="ksel_input_endkey">End&nbsp;&nbsp;&nbsp;</label></td><td><textarea id="ksel_input_endkey"></textarea></td></tr>
									</table>
								</div>
							</div>
						</div>
					</td>
					<td style="width: 30px;">&nbsp;</td>
					<td style="vertical-align: top;">
						<div><b>Info</b></div>
						<div id="ddoc_view_info">Select design doc &amp; view...</div>
					</td>
				</tr>
			</table>
		</div>
		
		<div style="margin: 0 0 20px 0;">
			<div class="float_left" style="margin: 0 20px 0 0;">
				<label for="incdoc_input"><b>Include Docs?&nbsp;</b></label><input id="incdoc_input" type="checkbox" value="1" />
			</div>
			
			<div class="float_left" style="margin: 0 20px 0 0;">
				<label for="desc_input"><b>Descending?&nbsp;</b></label><input id="desc_input" type="checkbox" value="1" />
			</div>
			
			<div class="float_left" style="margin: 0 20px 0 0;">
				<label for="skip_input"><b>Skip&nbsp;</b></label><input id="skip_input" type="text" value="" />
			</div>
			
			<div class="float_left" style="margin: 0 20px 0 0;">
				<label for="limit_input"><b>Limit&nbsp;</b></label><input id="limit_input" type="text" value="" />
			</div>
		
			<div id="reduce_controls" class="float_left" style="display: none; margin: 0 0 0 20px;">
				<div class="float_left" style="margin: 0 20px 0 0;">
					<label for="reduce_input"><b>Reduce?&nbsp;</b></label><input id="reduce_input" type="checkbox" value="1" />
				</div>
				
				<div class="float_left" style="margin: 0 20px 0 0;">
					<label for="group_input"><b>Group?&nbsp;</b></label><input id="group_input" type="checkbox" value="1" disabled="disabled" />
				</div>
				
				<div class="float_left" style="margin: 0 0 0 0;">
					<label for="group_lvl_input"><b>Group Level&nbsp;</b></label><input id="group_lvl_input" type="text" value="" disabled="disabled" />
				</div>
				
				<div class="clear"></div>
			</div>
			
			<div class="clear"></div>
		</div>
		
		<div style="margin: 0 30px 20px 0;">
			<div class="float_left">
				<button id="btn_query">Execute</button>
			</div>
			<div id="query_loading" class="float_left" style="display: none; margin-left: 10px;">
				<img src="common/loading.gif" alt="" />
			</div>
			<div id="query_toolbar" class="float_right">
				<div class="float_right">
					<button id="btn_qtb_font_larger">A+</button>
					<button id="btn_qtb_font_smaller">A-</button>
				</div>
				<div class="float_right">
					<div style="margin: 0 20px 0 0;">
						<input id="qr_fmt_tree" class="qr_fmt" name="qr_fmt" type="radio" value="tree" checked="checked" />
						<label for="qr_fmt_tree"><b>Tree</b></label>
						
						<input id="qr_fmt_raw" class="qr_fmt" name="qr_fmt" type="radio" value="raw" />
						<label for="qr_fmt_raw"><b>Raw</b></label>
					</div>
					<div class="clear"></div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</form>
	
	<div id="query_result" style="margin: 0 0 20px 0;"></div>
</body>
</html>
