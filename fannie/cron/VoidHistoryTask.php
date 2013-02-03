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

   nightly.voidhistory.php

   Updates voidTransHistory to include voided
   transactions from the previous day.

   Should be run after midnight & after
   dtransactions is rotated

*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class VoidHistoryTask extends FannieTask {

	public $nice_name = 'Log Voids';
   	public $help_info = 'Updates voidTransHistory to include voided
   transactions from the previous day.

   Should be run after midnight & after
   dtransactions is rotated';

	function run(){
		global $FANNIE_TRANS_DB;
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_TRANS_DB);

		$query = "INSERT INTO voidTransHistory
			SELECT datetime,description,
			".$sql->concat(
				$sql->convert('emp_no','char'),"'-'",
				$sql->convert('register_no','char'),"'-'",
				$sql->convert('trans_no','char'),'')
			.",
			0
			FROM transarchive WHERE trans_subtype='CM'
			AND ".$sql->datediff('datetime',$sql->now())." = -1
			AND description LIKE 'VOIDING TRANSACTION %-%-%'
			AND register_no <> 99 AND emp_no <> 9999 AND trans_status <> 'X'";
		$success = $sql->query($query);

		if ($success)
			echo $this->cron_msg("Voids logged");
		else
			echo $this->cron_msg("Error logging voids");
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new VoidHistoryTask();	
	$obj->run();
}
?>
