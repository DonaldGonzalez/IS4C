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

class LPBalanceTask extends FannieTask {

/* HELP

   This script updates custdata balances based on
   activity today
*/
   
	public $nice_name = 'Push Balances To Lanes';
	public $help_info = 'This script updates custdata balances based on
   activity today';

	function run(){
		global $FANNIE_TRANS_DB,$FANNIE_LANES;
		set_time_limit(0);
		ini_set('memory_limit','256M');

		$sql = FannieDB::get($FANNIE_TRANS_DB);

		// get balances that changed today
		$data = array();
		$fetchQ = "SELECT CardNo,balance FROM memChargeBalance WHERE mark=1";
		$fetchR = $sql->query($fetchQ);
		while($fetchW = $sql->fetch_row($fetchR))
			$data[$fetchW['CardNo']] = $fetchW['balance'];

		$errors = False;
		// connect to each lane and update balances
		foreach($FANNIE_LANES as $lane){
			$db = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);

			if ($db === False){
				echo $this->cron_msg("Can't connect to lane: ".$lane['host']);
				$errors = True;
				continue;
			}

			foreach($data as $cn => $bal){
				$upQ = sprintf("UPDATE custdata SET Balance=%.2f WHERE CardNo=%d",
						$bal,$cn);
				$db->query($upQ);
			}
		}

		if ($errors) {
			echo $this->cron_msg("There was an error pushing balances to the lanes");
			flush();
		}
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new LPBalanceTask();	
	$obj->run();
}

?>
