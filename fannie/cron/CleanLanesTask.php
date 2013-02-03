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

   lanes.clean.php

   Empty out old entries in localtrans_today

*/

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');

class CleanLanesTask extends FannieTask {

	public $nice_name = 'Trim Lane Transaction Tables';
	public $help_info = 'Clear lane "current day" transaction table
		and trim lane archive transaction table to the last
		30 days';
	public $scheduling_info = 'Must be after midnight. Daily recommended.';

	function run(){
		global $FANNIE_LANES;
		set_time_limit(0);

		foreach($FANNIE_LANES as $ln){
			$sql = new SQLManager($ln['host'],$ln['type'],$ln['trans'],$ln['user'],$ln['pw']);
			if ($sql === False){
				echo $this->cron_msg("Could not clear lane: ".$ln['host']);
				continue;
			}

			$cleanQ = "DELETE FROM localtrans_today WHERE ".$sql->datediff($sql->now(),'datetime')." <> 0";
			$cleanR = $sql->query($cleanQ,$ln['trans']);
			$cleanQ = "DELETE FROM localtrans WHERE ".$sql->datediff($sql->now(),'datetime')." > 30";
			$cleanR = $sql->query($cleanQ,$ln['trans']);
		}
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new CleanLanesTask();	
	$obj->run();
}

?>
