<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  Base class for scheduled tasks
*/
class FannieTask {

	public $nice_name = 'Generic Task';
	public $help_info = 'n/a';
	public $scheduling_info = 'n/a';
	public $related_tasks = array();

	/**
	  The task. Override this.
	*/
	public function run(){

	}

	/**
	  Format a message for logging
	  @param $str the message
	  @return formatted string
	*/
	protected function cron_msg($str){
		return date('r').': '.$_SERVER['SCRIPT_FILENAME'].': '.$str."\n";
	}
}

?>
