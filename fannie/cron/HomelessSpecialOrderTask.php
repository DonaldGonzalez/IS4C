<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/* HELP
 
   homeless.specialorder.php

   Check for SOs w/o a department
   and spam out email until someone fixes it

*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include_once($FANNIE_ROOT.'src/tmp_dir.php');

class HomelessSpecialOrderTask extends FannieTask {

	public $nice_name = 'Find Lost Special Orders';
	public $help_info = 'Check for special orders that don\'t have
		a department assigned and spam out email notifications
		until somebody fixes it.';

	function run(){
		global $FANNIE_TRANS_DB,$FANNIE_OP_DB,$FANNIE_SERVER_DBMS;
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_TRANS_DB);
		$OP = $FANNIE_OP_DB . ($FANNIE_SERVER_DBMS == "MSSQL" ? 'dbo.' : '.');

		$q = "
		select s.order_id,description,datetime,
		case when c.last_name ='' then b.LastName else c.last_name END as name
		from PendingSpecialOrder
		as s left join SpecialOrderContact as c on s.order_id=c.card_no
		left join {$OP}custdata as b on s.card_no=b.CardNo and s.voided=b.personNum
		where s.order_id in (
		select p.order_id from PendingSpecialOrder as p
		left join SpecialOrderNotes as n
		on p.order_id=n.order_id
		where notes LIKE ''
		group by p.order_id
		having max(department)=0 and max(superID)=0
		and max(trans_id) > 0
		)
		and trans_id > 0
		order by datetime
		";

		$r = $sql->query($q);
		if ($sql->num_rows($r) > 0){
			$msg_body = "Homeless orders detected!\n\n";
			while($w = $sql->fetch_row($r)){
				$msg_body .= $w['datetime'].' - '.(empty($w['name'])?'(no name)':$w['name']).' - '.$w['description']."\n";
				$msg_body .= "http://key".$FANNIE_URL."ordering/view.php?orderID=".$w['order_id']."\n\n";
			}
			$msg_body .= "These messages will be sent daily until orders get departments\n";
			$msg_body .= "or orders are closed\n";

			$to = "buyers";
			$subject = "Incomplete SO(s)";
			mail($to,$subject,$msg_body);
		}
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new HomelessSpecialOrderTask();	
	$obj->run();
}

?>
