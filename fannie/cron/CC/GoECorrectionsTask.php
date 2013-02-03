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

if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include_once($FANNIE_ROOT.'src/xmlData.php');
include_once($FANNIE_ROOT.'src/fetchLib.php');

class GoECorrectionsTask extends FannieTask {

/* HELP

	Void GoE transactions from the previous
	hour that had communication errors

*/

	public $nice_name = 'GoEMerchant Corrections';
	public $help_info = 'Void GoE transactions from the previous
	hour that had communication errors';

	function run(){
		global $FANNIE_TRANS_DB;
		set_time_limit(0);

		$sql = FannieDB::get($FANNIE_TRANS_DB);

		$stack = getFailedTrans(date("Y-m-d"),date("G")-1);

		$void_ids = array();
		foreach($stack as $refNum){
			$vref = doquery(date("mdy"),$refNum);
			if ($vref != False)
				$void_ids[] = $vref;
		}

		if (count($void_ids) > 0){
			dovoid($void_ids);
		}
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new GoECorrectionsTask();	
	$obj->run();
}

?>
