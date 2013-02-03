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

   notices.sales.php

   Send off emails on new sale days
   Based on table AdSaleDates

   Email address(es) configured 
   within script
*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class SaleNoticesTask extends FannieTask {

	public $nice_name = 'Email Sale Notices';
   	public $help_info = 'Send off emails on new sale days
   Based on table AdSaleDates

   Email address(es) configured 
   within script';

	function run(){
		global $FANNIE_OP_DB;
		set_time_limit(0);

		$TO = "andy@wholefoods.coop";
		$FROM = "sale-notices@wholefoods.coop";

		$dbc = FannieDB::get($FANNIE_OP_DB);

		$q = "SELECT sale_name,start_date,end_date FROM
			AdSaleDates WHERE "
			.$dbc->datediff($dbc->now(),"start_date")
			." = 0";
		$r = $dbc->query($q);

		if ($dbc->num_rows($r) == 0)
			exit; // no new sales

		$SUBJECT = "New Sale Today";
		$msg = "New sale today!\n";
		while($w = $dbc->fetch_row($r)){
			$msg .= $w['sale_name']."\n";
			$msg .= "Starts: ".$w['start_date']."\n";
			$msg .= "Ends: ".$w['end_date']."\n";
			$msg .= "\n";
		}
		mail($TO,$SUBJECT,$msg,$FROM."\r\n");
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new SalesNoticeTask();	
	$obj->run();
}

?>
