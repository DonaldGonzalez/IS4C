<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include_once($FANNIE_ROOT.'src/JsonLib.php');

class RestrictCouponPage extends FannieRESTfulPage {

	protected $header = 'Coupon Restrictions';
	protected $title = 'Coupon Restrictions';

	function get_view(){
		global $FANNIE_OP_DB, $FANNIE_URL;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$ret = '<form onsubmit="save();return false;">
			<table><tr><td>
			<b>UPC</b></td><td><input type="text" id="upc" />
			</td></tr><tr><td>
			<b>Limit</b></td><td><input type="text" size="3" value="0" id="limit" />
			</td></tr><tr><td>
			Reason</td><td><input type="text" id="reason" />
			</td></tr></table>
			<input type="submit" value="Save" />
			</form>
			<hr/>';

		$model = new DisableCouponModel($dbc);
		$ret .= '<table cellpadding="4" cellspacing="0" border="1">';
		foreach($model->find('upc') as $obj){
			$ret .= sprintf('<tr><td><a href="" onclick="loadcoupon(\'%s\');return false;">%s</a></td>
					<td>%d</td><td>%s</td>
					<td><a href="" onclick="deletecoupon(\'%s\');return false;"><img 
					src="%ssrc/img/buttons/trash.png" /></a></td></tr>',
					$obj->upc(), $obj->upc(), $obj->threshold(),
					$obj->reason(), $obj->upc(), $FANNIE_URL
			);
		}
		$ret .= '</table>';
		return $ret;
	}

	function get_id_handler(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$upc = str_pad($this->id, 13, '0', STR_PAD_LEFT);
		$model = new DisableCouponModel($dbc);
		$model->upc($upc);
		$model->load();

		$ret = array(
		'limit' => $model->threshold(),
		'reason' => $model->reason()
		);
		echo JsonLib::array_to_json($ret);
		return False;
	}

	function post_id_handler(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$upc = str_pad($this->id, 13, '0', STR_PAD_LEFT);
		$limit = FormLib::get_form_value('limit',0);
		$reason = FormLib::get_form_value('reason','');

		$model = new DisableCouponModel($dbc);
		$model->upc($upc);
		$model->threshold($limit);
		$model->reason($reason);
		$model->save();

		echo 'Done';
		return False;
	}

	function delete_id_handler(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$upc = str_pad($this->id, 13, '0', STR_PAD_LEFT);
		$model = new DisableCouponModel($dbc);
		$model->upc($upc);
		$model->delete();

		echo 'Done';
		return False;
	}

	function javascript_content(){
		ob_start();
		?>
function loadcoupon(upc){
	$.ajax({
	url: 'RestrictCouponPage.php?id='+upc,
	type: 'get',
	dataType: 'json',
	success: function(data){
		$('#upc').val(upc);
		if (data.limit)
			$('#limit').val(data.limit);
		if (data.reason)
			$('#reason').val(data.reason);
	}
	});
}
function save(){
	var dstr = 'id='+$('#upc').val();
	dstr += '&limit='+$('#limit').val();
	dstr += '&reason='+$('#reason').val();
	$.ajax({
	url: 'RestrictCouponPage.php',
	type: 'post',
	data: dstr,
	success: function(){
		location='RestrictCouponPage.php';
	}
	});
}
function deletecoupon(upc){
	if (confirm('Remove restrictions for '+upc+'?')){
		$.ajax({
		url: 'RestrictCouponPage.php?id='+upc,
		type: 'delete',
		success: function(){
			location='RestrictCouponPage.php';
		}
		});
	}
}
		<?php
		return ob_get_clean();
	}
}

FannieDispatch::go();

?>