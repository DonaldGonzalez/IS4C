<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class PUArchiveTask extends FannieTask {

/* HELP

   This script dumps prodUpdate into an archive
   table and truncates it. Keeping prodUpdate
   small makes scanning it for interesting changes
   a faster process.

   This should be called *after* any other compress 
   scripts.
*/
   
	public $nice_name = 'Archive Product Update Logs';
	public $help_info = 'This script dumps prodUpdate into an archive
   table and truncates it. Keeping prodUpdate
   small makes scanning it for interesting changes
   a faster process.

   This should be called *after* any other compress 
   scripts.';

	function run(){
		global $FANNIE_OP_DB;
		set_time_limit(0);
		ini_set('memory_limit','256M');

		$sql = FannieDB::get($FANNIE_OP_DB);

		$worked = $sql->query("INSERT INTO prodUpdateArchive SELECT * FROM prodUpdate");
		if ($worked){
			$sql->query("TRUNCATE TABLE prodUpdate");
		}
		else {
			echo $this->cron_msg("There was an archiving error on prodUpdate");
			flush();
		}
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new PUArchiveTask();	
	$obj->run();
}

?>
