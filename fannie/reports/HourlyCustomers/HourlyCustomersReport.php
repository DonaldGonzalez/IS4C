<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class HourlyCustomersReport extends FannieReportPage 
{

    protected $header = "Customers per Hour";
    protected $title = "Fannie : Customers per Hour";

    protected $content_function = 'both_content';
    protected $report_headers = array('Hour', 'Transactions');

	public function preprocess()
    {
        if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls') {
            $this->report_format = 'xls';
            $this->has_menus(False);
        } elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv') {
            $this->report_format = 'csv';
            $this->has_menus(False);
		} else  {
			$this->add_script("../../src/CalendarControl.js");
        }

		return true;
	}

    public function form_content()
    {
        ob_start();
        ?>
<form method=get action=<?php echo $_SERVER["PHP_SELF"]; ?> >
Get transactions per hour for what date (YYYY-MM-DD)?<br />
<input type=text onfocus="showCalendarControl(this);" name=date />&nbsp;
<input type=submit value=Generate />
</form>
        <?php
        return ob_get_clean();
    }

    public function report_description_content()
    {
        $date = FormLib::get_form_value('date', date('Y-m-d'));

        return array("Report for $date");
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date = FormLib::get_form_value('date', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($date);

        $hour = $dbc->hour('tdate');
        $q = $dbc->prepare_statement("select $hour as hour,
            count(distinct trans_num)
            from $dlog where
            tdate BETWEEN ? AND ?
            group by $hour
            order by $hour");
        $r = $dbc->exec_statement($q,array($date.' 00:00:00',$date.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_array($r)){
            $hour = $row[0];
            if ($hour > 12) {
                $hour -= 12;
            }
            $record = array();
            $record[] = $hour . ($row[0] < 12 ? ':00 am' : ':00 pm');
            $record[] = $row[1];
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::go();

?>