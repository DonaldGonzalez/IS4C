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

   nightly.clipboard.php

   This script truncates the table batchCutPaste. This table
   acts as a clipboard so users can cut/paste items from
   on sales batch to another. The table must be truncated
   periodically or old data will linger indefinitely.	

   It also clears stale, deleted shelftags from the
   shelftags table. Entries hang around with a negative
   id for recovery in the case of mistakes.
*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class ClipboardTask extends FannieTask {

	public $nice_name = 'Clear Clipboards';
	public $help_info = 'This script truncates the table batchCutPaste. This table
   acts as a clipboard so users can cut/paste items from
   on sales batch to another. The table must be truncated
   periodically or old data will linger indefinitely.	

   It also clears stale, deleted shelftags from the
   shelftags table. Entries hang around with a negative
   id for recovery in the case of mistakes.';

	function run(){
		global $FANNIE_OP_DB;
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_OP_DB);

		$chk = $sql->query("TRUNCATE TABLE batchCutPaste");
		if ($chk === false)
			echo $this->cron_msg("Error clearing batch clipboard");
		else
			echo $this->cron_msg("Cleared batch clipboard");

		$chk2 = $sql->query("DELETE FROM shelftags WHERE id < 0");
		if ($chk2 === false)
			echo $this->cron_msg("Error clearing deleted sheltags");
		else
			echo $this->cron_msg("Cleared deleted shelftags");
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new ClipboardTask();	
	$obj->run();
}

?>
