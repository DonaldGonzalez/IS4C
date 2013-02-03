<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class ReactivateEquityTask extends FannieTask {
/* HELP

   This script activates members with equity paid in full
*/
	public $nice_name = 'Deactivate For Equity';
	public $help_info = 'This script activates members with equity paid in full';

	function run(){
		global $FANNIE_OP_DB,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS;

		set_time_limit(0);
		ini_set('memory_limit','256M');

		$sql = FannieDB::get($FANNIE_OP_DB);

		$TRANS = $FANNIE_TRANS_DB . ($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

		$meminfoQ = "UPDATE meminfo AS m LEFT JOIN
			    custdata AS c ON m.card_no=c.CardNo
			    LEFT JOIN {$TRANS}newBalanceStockToday_test AS s
			    ON c.cardno=s.memnum LEFT JOIN suspensions AS p
			    ON c.cardno=p.cardno 
			    SET m.ads_OK=p.mailflag
			    WHERE c.Type = 'INACT' and p.reasoncode IN (2,4,6)
			    AND s.payments >= 100";
		$sql->query($meminfoQ);

		$custQ = "UPDATE custdata AS c LEFT JOIN {$TRANS}newBalanceStockToday_test AS s
			    ON c.CardNo=s.memnum LEFT JOIN suspensions AS p
			    ON c.CardNo=p.cardno
			    SET c.Discount=p.discount,c.memDiscountLimit=p.chargelimit,
			    c.memType=p.memtype1,c.Type=p.memtype2,chargeOk=1
			    WHERE c.Type = 'INACT' and p.reasoncode IN (2,4,6)
			    AND s.payments >= 100";
		$sql->query($custQ);

		$histQ = "insert into suspension_history
			    select 'automatic',".$sql->now().",
			    'Account reactivated',c.CardNo,0 from
			    suspensions as s left join
			    custdata as c on s.cardno=c.CardNo
			    and c.personNum=1
			    where c.Type not in ('INACT','INACT2') and s.type='I'";
		$sql->query($histQ);

		$clearQ = "select c.CardNo from
			    suspensions as s left join
			    custdata as c on s.cardno=c.CardNo
			    where c.Type not in ('INACT','INACT2') and s.type='I'
			    AND c.personNum=1";
		$clearR = $sql->query($clearQ);
		$cns = "(";
		while($clearW = $sql->fetch_row($clearR)){
			$cns .= $clearW[0].",";
		}
		$cns = rtrim($cns,",").")";

		$delQ = "DELETE FROM suspensions WHERE cardno IN $cns";
		$delR = $sql->query($delQ);
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new ReactivateEquityTask();	
	$obj->run();
}


?>
