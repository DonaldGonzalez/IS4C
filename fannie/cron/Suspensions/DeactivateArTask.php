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

class DeactivateArTask extends FannieTask {

/* HELP

   This script de-activates members with store-charge account (ar)
    in arrears, i.e.
   AR_EOM_Summary.twoMonthBalance <= newBalanceToday_cust.balance

   When/how-often can/should it be run? Daily?

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * 18Oct12 EL Keep this comment block from appearing in the Help popup.
 *             Reformat SQL statements.
 * 17Jun12 EL Fix Help to make it appropriate to this program.
 *             Was a copy of reactivate.equity.php.
*/
   
	public $nice_name = 'Deactivate For AR';
	public $help_info = 'This script de-activates members with store-charge account (ar)
    in arrears, i.e.
   AR_EOM_Summary.twoMonthBalance <= newBalanceToday_cust.balance

   When/how-often can/should it be run? Daily?';

	function run(){
		global $FANNIE_OP_DB,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS;
		set_time_limit(0);
		ini_set('memory_limit','256M');

		$TRANS = $FANNIE_TRANS_DB . ($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

		$susQ = "INSERT INTO suspensions
			select m.card_no,'I',c.memType,c.Type,'',
			".$sql->now().",m.ads_OK,c.Discount,
			c.memDiscountLimit,1
			from meminfo as m left join
			custdata as c on c.CardNo=m.card_no and c.personNum=1
			left join {$TRANS}ar_live_balance as n on m.card_no=n.card_no
			left join {$TRANS}AR_EOM_Summary AS a ON a.cardno=m.card_no
			where a.twoMonthBalance <= n.balance
			AND a.lastMonthPayments < a.twoMonthBalance
			and c.type='PC' and n.balance > 0
			and c.memtype in (1,3)
			and NOT EXISTS(SELECT NULL FROM suspensions as s
			WHERE s.cardno=m.card_no)";
		$sql->query($susQ);

		$histQ = "INSERT INTO suspension_history
			    select 'automatic',".$sql->now().",'',
			    m.card_no,1
			    from meminfo as m left join
			    custdata as c on c.CardNo=m.card_no and c.personNum=1
			    left join {$TRANS}ar_live_balance as n on m.card_no=n.card_no
			    left join {$TRANS}AR_EOM_Summary AS a ON a.cardno=m.card_no
			    where a.twoMonthBalance <= n.balance
			    AND a.lastMonthPayments < a.twoMonthBalance
			    and c.type='PC' and n.balance > 0
			    and c.memtype in (1,3)
			    and NOT EXISTS(SELECT NULL FROM suspensions as s
			    WHERE s.cardno=m.card_no)";
		$sql->query($histQ);

		$custQ = "UPDATE custdata AS c
			LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
			SET c.type='INACT',memType=0,c.Discount=0,memDiscountLimit=0
			WHERE c.type='PC' AND s.cardno is not null";
		$sql->query($custQ);

		$memQ = "UPDATE meminfo AS m
				LEFT JOIN suspensions AS s ON m.card_no=s.cardno
		    SET ads_OK=0
		    WHERE s.cardno is not null";
		$sql->query($memQ);
	}
}
if (php_sapi_name() === 'cli'){
	$obj = new DeactivateArTask();	
	$obj->run();
}

?>
