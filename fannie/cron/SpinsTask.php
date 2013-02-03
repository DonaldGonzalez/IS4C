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

   weekly.spins.php

   The idea here is to send weekly sales data
   to SPINS. This script isn't yet in active
   use and may contain bugs. 

   SPINS data is sent via FTP; credentials must
   be specified manually in the script.
*/


if (!isset($FANNIE_ROOT))
	include_once(dirname(__FILE__).'/../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieTask.php');
include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include_once($FANNIE_ROOT.'src/tmp_dir.php');

class SpinsTask extends FannieTask {

	public $nice_name = 'SPINS Upload';
   	public $help_info = 'The idea here is to send weekly sales data
   to SPINS. This script isn\'t yet in active
   use and may contain bugs. 

   SPINS data is sent via FTP; credentials must
   be specified manually in the script.';

	function run(){
		global $FANNIE_TRANS_DB, $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
		set_time_limit(0);

		$SPINS_SERVER = "ftp.spins.com";
		$SPINS_USER = "whole_food_duluth";
		$SPINS_PW = "wfcc\$54*";

		$tstamp = time();
		$week = date("W",$tstamp);
		$week--;
		if ($week == 0) $week = 52;

		if (isset($argv[1]) && is_numeric($argv[1]))
			$week = $argv[1];

		while(date("W",$tstamp) != $week or date("w",$tstamp) != 6){
			$tstamp = mktime(0,0,0,date("n",$tstamp),
				date("j",$tstamp)-1,date("Y",$tstamp));
		}

		$lastDay = date("M d, Y",$tstamp)." 11:59PM";

		$sql = FannieDB::get($FANNIE_OP_DB);

		$dataQ = "SELECT d.upc as upc, p.description as description,
			sum(CASE WHEN d.quantity <> d.ItemQtty AND d.ItemQtty <> 0 THEN d.quantity*d.ItemQtty ELSE d.quantity END) as quantity,
			sum(d.total) as dollars,
			'$lastDay' as lastDay
			FROM dlog_90_view as d inner join 
			{$FANNIE_OP_DB}.dbo.products as p
			on d.upc=p.upc
			WHERE p.scale = 0
			AND d.upc > '0000000999999'
			AND datepart(ww,tdate) = $week
			group by d.upc, p.description";
		// mysql handles week # differently by default
		if ($FANNIE_SERVER_DBMS == "MYSQL"){
			$dataQ = "SELECT d.upc as upc, p.description as description,
				sum(CASE WHEN d.quantity <> d.ItemQtty AND d.ItemQtty <> 0 THEN d.quantity*d.ItemQtty ELSE d.quantity END) as quantity,
				sum(d.total) as dollars,
				'$lastDay' as lastDay
				FROM dlog_90_view as d inner join 
				{$FANNIE_OP_DB}.products as p
				on d.upc=p.upc
				WHERE p.scale = 0
				AND d.upc > '0000000999999'
				AND week(tdate) = ".($week)."
				group by d.upc, p.description";
		}

		/* SPINS numbering is non-standard in 2012
		   so week is offset by one in the filename
		   this may change back next year
		*/
		$filename = "spins_wk".str_pad($week+1,2,"0",STR_PAD_LEFT).".csv";
		$outfile = sys_get_temp_dir()."/".$filename;
		$fp = fopen($outfile,"w");

		$dataR = $sql->query($dataQ);
		while($row = $sql->fetch_row($dataR)){
			for($i=0;$i<4; $i++){
				fwrite($fp,"\"".$row[$i]."\",");
			}
			fwrite($fp,"\"".$row[4]."\"\n");
		}
		fclose($fp);

		$conn_id = ftp_connect($SPINS_SERVER);
		$login_id = ftp_login($conn_id, $SPINS_USER, $SPINS_PW);

		if (!$conn_id or !$login_id){
			echo $this->cron_msg("FTP connect failed!");
		}

		ftp_chdir($conn_id,"data");
		ftp_pasv($conn_id,True);

		$upload = ftp_put($conn_id, $filename, $outfile, FTP_ASCII);

		if (!$upload){
			echo $this->cron_msg("FTP upload failed");
		}

		ftp_close($conn_id);
	}
}

if (php_sapi_name() === 'cli'){
	$obj = new SpinsTask();	
	$obj->run();
}

?>
