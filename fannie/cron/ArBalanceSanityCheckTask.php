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

   arbalance.sanitycheck.php

   Sync up custdata balance with live table

*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class ArBalanceSanityCheckTask extends FannieTask {
	
	public $nice_name = 'Re-sync Custdata Balances';
	public $help_info = 'Sync up custdata balance with live table';

	function run(){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS;	
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_TRANS_DB);

		$query = "UPDATE {$FANNIE_OP_DB}.custdata AS c LEFT JOIN 
			ar_live_balance AS n ON c.CardNo=n.card_no
			SET c.Balance = n.balance";
		if ($FANNIE_SERVER_DBMS == "MSSQL"){
			$query = "UPDATE {$FANNIE_OP_DB}.dbo.custdata SET Balance = n.balance
				FROM {$FANNIE_OP_DB}.dbo.custdata AS c LEFT JOIN
				ar_live_balance AS n ON c.CardNo=n.card_no";
		}

		$sql->query($query);
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new ArBalanceSanityCheckTask();	
	$obj->run();
}

?>
