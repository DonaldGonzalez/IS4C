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

class CouponsReport extends FannieReportPage {

    protected $report_headers = array('UPC', 'Qty', '$ Total');

	public function preprocess()
    {
		$this->report_cache = 'none';
		$this->title = "Fannie : Coupons Report";
		$this->header = "Coupons Report";

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(False);
		
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls') {
				$this->report_format = 'xls';
			} elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv') {
				$this->report_format = 'csv';
            }
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return true;
	}

    public function calculate_footers($data)
    {
        $sum = 0;
        $sum2 = 0;
        foreach($data as $row) {
            $sum += $row[1];
            $sum2 += $row[2];
        }

        return array('Total', sprintf('%.2f', $sum), sprintf('%.2f',$sum2));
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $d1 = FormLib::get('date1', date('Y-m-d'));
        $d2 = FormLib::get('date2', date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($d1,$d2);

        $query = $dbc->prepare_statement("SELECT 
            CASE WHEN upc='0' THEN 'NOT SCANNED' ELSE upc END as upc, 
            sum(CASE WHEN upc='0' THEN 1 ELSE quantity END) as qty,
            sum(-total) as ttl FROM $dlog
            WHERE trans_subtype='CP'
            AND tdate BETWEEN ? AND ?
            GROUP BY upc
            ORDER BY upc");
        $result = $dbc->exec_statement($query, array($d1.' 00:00:00', $d2.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_row($result)){
            $data[] = array(
                        $row['upc'],
                        sprintf('%.2f', $row['qty']),
                        sprintf('%.2f', $row['ttl'])
                        );
        }

        return $data;
    }

    public function form_content()
    {
        $lastMonday = "";
        $lastSunday = "";

        $ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
        while($lastMonday == "" || $lastSunday == "") {
            if (date("w",$ts) == 1 && $lastSunday != "") {
                $lastMonday = date("Y-m-d",$ts);
            } elseif(date("w",$ts) == 0) {
                $lastSunday = date("Y-m-d",$ts);
            }
            $ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));	
        }

        ob_start();
        ?>
<form action=CouponsReport.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text id="date1" name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
<td rowspan="3">
<?php echo FormLib::date_range_picker(); ?>
</td>
</tr><tr>
<th>End Date</th>
<td><input type=text id="date2" name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel value="xls" /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
        <?php
        return ob_get_clean();
    }

}

FannieDispatch::go();

?>
