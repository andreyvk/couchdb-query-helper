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
		
		$q = post_var('q');
		$url .= (!empty($url)?'&':'').'q='.$q;
		
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
		
		$sort = post_var('sort');
		if(strlen($sort) > 0) {
			$opts['sort'] = stripslashes($sort);
			$url .= (!empty($url)?'&':'').'sort='.$opts['sort'];
		}
		
		/* echo '<pre>'.print_r($ddoc, true).'</pre>';
		echo '<pre>'.print_r($view, true).'</pre>';
		echo '<pre>'.print_r($opts, true).'</pre>'; */
		
		list($pref, $postf) = explode('/', $ddoc);
		
		$start_time = millis_time();
		
		try {
			$res = $cdb->setLuceneQueryParameters($opts)->getLuceneView($postf, $view, $q);
		}
		catch(Exception $e) {
			echo '<pre>'.$e->getCode().': '.$e->getMessage().'</pre>';
			exit();
		}
		
		$time_diff = millis_time()-$start_time;
		
		$url_parts = parse_url($cdb->getDatabaseUri());
		if($url_parts === FALSE) 
			$url = 'http://localhost:5984/beta4/_fti/'.$ddoc.'/'.$view.'?'.$url;
		else {
			$url2 = empty($url_parts['scheme'])?'http://':$url_parts['scheme'].'://';
			
			if(!empty($url_parts['user']) || !empty($url_parts['pass']))
				$url2 .= 'user:pass@';
				
			$url2 .= $url_parts['host'].':'.$url_parts['port'].$url_parts['path'].'/_fti';

			$url = $url2.'/'.$ddoc.'/'.$view.'?'.$url;
		}
		
		if(!isset($opts['limit'])) {
			echo '<div class="qr_item">NOTE:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Default limit is imposed (see <i>couchdb-lucene.ini</i>)</div>';
		}
		
		echo '<div class="qr_item">REST URL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$url.'</div>';
		echo '<div class="qr_item">QUERY TIME:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;~'.intval($time_diff/1000).' sec ('.$time_diff.' ms)</div>';
		
		$res_format = post_var('result_format');
		if($res_format == 'raw') {
			echo '<div class="qr_item">QUERY RESULT:</div>';
			echo '<div class="qr_item">'.json_encode_and_format($res).'</div>';
		}
		else if($res_format == 'tree') {
			echo '<div class="qr_item">TOTAL ROWS:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$res->total_rows.'</div>';
			echo '<div class="qr_item">ETAG:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$res->etag.'</div>';
			echo '<div class="qr_item">SEARCH DURATION:&nbsp;'.$res->search_duration.'</div>';
			echo '<div class="qr_item">FETCH DURATION:&nbsp;&nbsp;'.$res->fetch_duration.'</div>';
			echo '<div class="qr_item">RESULT ROWS:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.count($res->rows).'</div>';
			echo '<div class="qr_item">QUERY RESULT:</div>';
			$cnt = 0;
			foreach($res->rows as $row) {
				$key = new stdClass();
				$key->score = $row->score;
				$key->id = $row->id;
				
				echo '<div class="qr_item">';
					echo '<div class="qr_tree_item clickable">'.json_encode($key).'</div>';
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
			if(!empty($ddoc->fulltext)) {
				$views = get_object_vars($ddoc->fulltext);
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
			$res = $cdb->setLuceneQueryParameters(array())->getLuceneView($postf, $view);

			$ddoc = $cdb->getDoc($ddoc_id);
			$view_doc = @$ddoc->fulltext->{$view}->index;
			
			if(isset($res->fields)) {
				foreach($res->fields as &$field) {
					$type = lucene_get_field_type_from_doc($view_doc, $field);
					if(!empty($type)) $field = $field.'<'.$type.'>';
				}
			}
			
			echo json_encode(array('info'=>$res));
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
		if(strpos($row->id, '_design/lucene') !== 0) continue ;
		
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
<title>Lucene Query Helper</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
<style type="text/css">
body { font-family: Courier New; font-size: 14px; }
input, select, textarea{ border: 1px solid #CCC; font-family: Courier New; margin-bottom: 3px; font-size: 12px; padding: 5px 3px; }
button { background-color: #E9E9E9; border: 1px solid #CCC; font-family: Courier New;  }
button:hover { background-color: #F0F0F0; }

.clickable { cursor: pointer; }
.float_left { float: left; }
.float_right { float: right; }
.clear { clear: both; }

.ddoc_view_field { display: inline-block; padding: 3px 5px; margin-bottom: 3px; font-size: 12px; background-color: #F0F0F0; font-weight: bold; cursor: pointer; }
#q_input { width: 650px; height: 80px; }
#query_result .qr_item { font-family: Courier New; font-size: 12px; margin-bottom: 5px; }
#query_result .qr_tree_item { font-weight: bold; }
#query_result .qr_tree_item:hover { background-color: #F9F9F9; }
#query_result .qr_tree_content { padding-left: 20px; }
#limit_input, #skip_input { width: 40px; }
</style>
<script type="text/javascript">
$(function() {
	$('form')[0].reset();
	
	$('.ksel_rad').change(function() {
		var type = $(this).val();
		
		$('.ksel_tab').hide();
		$('#ksel_tab_'+type).show();
	});
	
	$('#ddoc_sel').change(function() {
		$('#ddocview_sel').html('<option value="">Loading...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>');
		
		var ddoc = $(this).val();
		$.ajax({
			url: 'lucene.php',
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
		$('#sort_field_sel').html('<option value="">-- by none--</option>');
	});
	
	$('#ddocview_sel').change(function() {	
		$('#sort_field_sel').html('<option value="">-- by none--</option>');
		
		var view = $(this).val();
	
		if(view.length == 0) {
			$('#ddoc_view_info').html('Select design doc &amp; view...');
		} else {
			$('#ddoc_view_info').html('Loading...');
			
			var params = {'cmd': 'ddoc_view_info'};
			
			params.ddoc_id = $('#ddoc_sel').val();
			params.view = $('#ddocview_sel').val();

			$.ajax({
				url: 'lucene.php',
				type: 'POST',
				dataType: 'json',
				data: params,
				success: function(resp) {
					if(!resp || resp.error) {
						$('#ddoc_view_info').html('Error getting view info!');	
					}
					else {
						var dvi = $('#ddoc_view_info');
						var sfl = $('#sort_field_sel');
						
						var ct = '<div>Fields: ';
						if(resp.info.fields && resp.info.fields.length > 0) {
							for(var i in resp.info.fields) {
								sfl.append('<option value="'+resp.info.fields[i]+'">'+escapeHtml(resp.info.fields[i])+'</option>');
							
								ct += '<span class="ddoc_view_field" val="'+resp.info.fields[i]+'">'+escapeHtml(resp.info.fields[i])+'</span>&nbsp;';
							}
						}
						else {
							ct += 'need at least one relevant document to determine fields';
						}
						ct += '</div>';
						
						ct += '<div>Docs #: '+resp.info.doc_count+'</div>';
						
						dvi.html(ct).find('.ddoc_view_field').click(function() {
							var val = $('#q_input').val();
							if($.trim(val).length > 0) val += ' ';
							val += $(this).attr('val')+':';
							$('#q_input').val(val).focus();
						});
						
						dvi = null;
						sfl = null;
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
		
		params.q = $('#q_input').val();
		
		params.include_docs = $('#incdoc_input').is(':checked') ? 1 : 0;
		params.limit = $('#limit_input').val();
		params.skip = $('#skip_input').val();
		params.sort = ($('#sort_field_sel').val().length > 0) ? $('#sort_ord_sel').val()+$('#sort_field_sel').val() : '';
		
		params.result_format = 'raw';
		if($('#qr_fmt_tree').is(':checked')) {
			params.result_format = 'tree';
		}
		
		$.ajax({
			url: 'lucene.php',
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
		
		if($('#query_result > pre').size() == 0) return ;
		
		var inc = $(this).attr('id') == 'btn_qtb_font_smaller' ? -1 : 1;
		
		var fontsize = $('#query_result > pre').css('font-size');
		fontsize = parseInt(fontsize)+inc;
		$('#query_result > pre').css('font-size', fontsize+'px');
	});
});

function escapeHtml(unsafe) {
  return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

</script>
</head>
<body>
	<form>
		<div id="ddoc" class="float_left" style="margin: 0 30px 20px 0;">
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
		
		<div id="ddocview" class="float_left" style="margin: 0 30px 20px 0;">
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
						<div><b>Query</b></div>
						<textarea id="q_input"></textarea>
					</td>
					<td style="width: 30px;">&nbsp;</td>
					<td style="vertical-align: top;">
						<div><b>Info</b></div>
						<div id="ddoc_view_info">Select design doc &amp; view...</div>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="incdoc_input"><b>Include Docs?&nbsp;</b></label><input id="incdoc_input" type="checkbox" value="1" />
		</div>
		
		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="skip_input"><b>Skip&nbsp;</b></label><input id="skip_input" type="text" value="" />
		</div>
		
		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="limit_input"><b>Limit&nbsp;</b></label><input id="limit_input" type="text" value="" />
		</div>

		<div class="float_left" style="margin: 0 30px 20px 0;">
			<label for="sort_ord_sel"><b>Sort&nbsp;</b></label>
			<select id="sort_ord_sel"><option value="/">ASC</option><option value="\">DESC</option></select>
			<select id="sort_field_sel"><option value="">-- by none--</option></select>
		</div>

		
		<div class="clear"></div>
		
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
