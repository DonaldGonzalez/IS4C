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

/* HELP

   nightly.tablecache.php

   Something of a catch-all, this script is used generically
   to load data into lookup tables. Generally this means copying
   data from relatively slow views into tables so subesquent
   queries against that data will be faster.

   This currently affects cashier performance reporting and
   batch movement reporting.
*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class TableCacheTask extends FannieTask {

	public $nice_name = 'Table Cache Updates';
   	public $help_info = 'Something of a catch-all, this script is used generically
   to load data into lookup tables. Generally this means copying
   data from relatively slow views into tables so subesquent
   queries against that data will be faster.

   This currently affects cashier performance reporting and
   batch movement reporting.';

	function run(){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_OP_DB);

		$chk = $sql->query("TRUNCATE TABLE batchMergeTable");
		if ($chk === False)
			echo $this->cron_msg("Could not truncate batchMergeTable");
		$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeProd");
		if ($chk === False)
			echo $this->cron_msg("Could not load data from batchMergeProd");
		$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeLC");
		if ($chk === False)
			echo $this->cron_msg("Could not load data from batchMergeLC");

		$sql->query("use $FANNIE_TRANS_DB");
		$chk = $sql->query("TRUNCATE TABLE CashPerformDay_cache");
		if ($chk === False)
			echo $this->cron_msg("Could not truncate CashPerformDay_cache");
		$chk = $sql->query("INSERT INTO CashPerformDay_cache SELECT * FROM CashPerformDay");
		if ($chk === False)
			echo $this->cron_msg("Could not load data for CashPerformDay_cache");

		echo $this->cron_msg("Success");
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new TableCacheTask();	
	$obj->run();
}

?>
