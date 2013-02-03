<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

   nightly.equity.php

   Copies equity transaction information
   for the previous day from dlog_15 into
   stockpurchases.

   Should be run after dtransaction rotation
   and after midnight.

*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class EquityUpdateTask extends FannieTask {
	
	public $nice_name = 'Update Equity';
   	public $help_info = 'Copies equity transaction information
   for the previous day from dlog_15 into
   stockpurchases.

   Should be run after dtransaction rotation
   and after midnight.';

	function run(){
		global $FANNIE_TRANS_DB, $FANNIE_EQUITY_DEPARTMENTS;
		set_time_limit(0);

		$ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
		$depts = array_pop($depts);
		$dlist = "(";
		foreach ($depts as $d){
			$dlist .= $d.",";	
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";

		$sql = FannieDB::get($FANNIE_TRANS_DB);

		$query = "INSERT INTO stockpurchases
			SELECT card_no,
			CASE WHEN department IN $dlist THEN total ELSE 0 END as stockPayments,
			tdate,trans_num,department
			FROM dlog_15 WHERE "
			.$sql->datediff($sql->now(),'tdate')." = 1
			AND department IN $dlist";	
		$sql->query($query);
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new EquityUpdateTask();	
	$obj->run();
}

?>
