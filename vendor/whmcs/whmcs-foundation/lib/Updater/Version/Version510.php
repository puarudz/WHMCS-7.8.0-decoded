<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version510 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        mysql_query("UPDATE tblpaymentgateways SET value='CC' WHERE gateway='worldpayfuturepay' AND setting='type'");
        $result = mysql_query("SELECT id FROM tblcustomfields WHERE type='client' AND fieldname='FuturePay ID'");
        $data = mysql_fetch_array($result);
        $futurepayfid = $data[0];
        if ($futurepayfid) {
            $result = mysql_query("SELECT relid,value FROM tblcustomfieldsvalues WHERE fieldid=" . $futurepayfid);
            while ($data = mysql_fetch_array($result)) {
                list($userid, $fpid) = $data;
                mysql_query("UPDATE tblclients SET gatewayid='" . $fpid . "' WHERE id=" . $userid . " AND gatewayid=''");
                mysql_query("DELETE FROM tblcustomfieldsvalues WHERE fieldid=" . $futurepayfid . " AND relid=" . $userid);
            }
            mysql_query("DELETE FROM tblcustomfields WHERE id=" . $futurepayfid);
        }
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
        return $this;
    }
}

?>