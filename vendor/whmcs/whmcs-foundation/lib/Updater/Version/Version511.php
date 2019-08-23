<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version511 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        mysql_query("ALTER TABLE  `tblcalendar` ADD  `start` INT( 10 ) NOT NULL AFTER  `desc` , ADD  `end` INT( 10 ) NOT NULL AFTER  `start`, ADD  `allday` INT( 1 ) NOT NULL AFTER  `end`, ADD  `recurid` INT( 10 ) NOT NULL AFTER  `adminid`");
        $result = mysql_query("SELECT * FROM tblcalendar");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $day = $data["day"];
            $month = $data["month"];
            $year = $data["year"];
            $startt1 = $data["startt1"];
            $startt2 = $data["startt2"];
            $endt1 = $data["endt1"];
            $endt2 = $data["endt2"];
            $start = mktime($startt1, $startt2, 0, $month, $day, $year);
            $end = $endt1 && $endt2 ? mktime($endt1, $endt2, 0, $month, $day, $year) : "0";
            mysql_query("UPDATE tblcalendar SET start='" . $start . "',end='" . $end . "' WHERE id=" . $id);
        }
        mysql_query("ALTER TABLE `tblcalendar` DROP `day`,DROP `month`,DROP `year`,DROP `startt1`,DROP `startt2`,DROP `endt1`,DROP `endt2`");
        mysql_query("ALTER TABLE  `tblpromotions` ADD `lifetimepromo` INT(1) NOT NULL AFTER `uses`");
        mysql_query("ALTER TABLE  `tblquotes` ADD  `datesent` DATE NOT NULL , ADD  `dateaccepted` DATE NOT NULL");
        mysql_query("UPDATE tbladminroles SET widgets = CONCAT(widgets,',calendar')");
        mysql_query("UPDATE tbladmins SET  `homewidgets`='getting_started:true,orders_overview:true,supporttickets_overview:true,my_notes:true,client_activity:true,open_invoices:true,activity_log:true|income_overview:true,system_overview:true,whmcs_news:true,sysinfo:true,admin_activity:true,todo_list:true,network_status:true,income_forecast:true|' WHERE id=1");
        return $this;
    }
}

?>