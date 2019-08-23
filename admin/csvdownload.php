<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Mass Data Export");
header("Pragma: public");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
header("Cache-Control: private", false);
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: binary");
$report = $whmcs->get_req_var("report");
$type = $whmcs->get_req_var("type");
$print = $whmcs->get_req_var("print");
$currencyid = $whmcs->get_req_var("currencyid");
$month = $whmcs->get_req_var("month");
$year = $whmcs->get_req_var("year");
if ($report) {
    require "../includes/reportfunctions.php";
    $chart = new WHMCSChart();
    $currencies = array();
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $code = $data["code"];
        $currencies[$id] = $code;
        if (!$currencyid && $data["default"]) {
            $currencyid = $id;
        }
        if ($data["default"]) {
            $defaultcurrencyid = $id;
        }
    }
    $currency = getCurrency("", $currencyid);
    $months = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
    $month = (int) $month;
    $year = (int) $year;
    if (!$month) {
        $month = date("m");
    }
    if (!$year) {
        $year = date("Y");
    }
    $currentmonth = $months[(int) $month];
    $currentyear = $year;
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    $gateways = new WHMCS\Gateways();
    $data = $reportdata = $chartsdata = $args = array();
    $report = preg_replace("/[^0-9a-z-_]/i", "", $report);
    $moduleType = $whmcs->get_req_var("moduletype");
    $moduleName = $whmcs->get_req_var("modulename");
    $subDirectory = $whmcs->get_req_var("subdir");
    $reportPath = ROOTDIR . DIRECTORY_SEPARATOR . "modules";
    if ($moduleType == "addons") {
        if (!isValidforPath($moduleName) || $subDirectory && !isValidforPath($subDirectory)) {
            redir();
        }
        $reportPath .= DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $moduleName;
        if ($subDirectory) {
            $reportPath .= DIRECTORY_SEPARATOR . $subDirectory;
        }
    } else {
        $reportPath .= DIRECTORY_SEPARATOR . "reports";
    }
    $reportfile = $reportPath . DIRECTORY_SEPARATOR . $report . ".php";
    if (file_exists($reportfile)) {
        require $reportfile;
        $rows = $trow = array();
        foreach ($reportdata["tableheadings"] as $heading) {
            $trow[] = $heading;
        }
        $rows[] = $trow;
        if ($reportdata["tablevalues"]) {
            foreach ($reportdata["tablevalues"] as $values) {
                $trow = array();
                foreach ($values as $value) {
                    if (substr($value, 0, 2) == "**") {
                        $trow[] = csv_clean(substr($value, 2));
                    } else {
                        $trow[] = csv_clean($value);
                    }
                }
                $rows[] = $trow;
            }
        }
        header("Content-disposition: attachment; filename=" . $report . "_export_" . date("Ymd") . ".csv");
        echo strip_tags($reportdata["title"]) . "\n";
        foreach ($rows as $row) {
            echo implode(",", $row) . "\n";
        }
    } else {
        exit("Report File Not Found");
    }
} else {
    if ($type == "pdfbatch") {
        require ROOTDIR . "/includes/clientfunctions.php";
        require ROOTDIR . "/includes/invoicefunctions.php";
        $filterby = App::getFromRequest("filterby");
        $range = App::getFromRequest("range");
        $result = select_query("tblpaymentgateways", "gateway,value", array("setting" => "name"), "order", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $gatewaysarray[$data["gateway"]] = $data["value"];
        }
        $invoice = new WHMCS\Invoice();
        $invoice->pdfCreate($aInt->lang("reports", "pdfbatch") . " " . date("Y-m-d"));
        $orderby = "id";
        if ($sortorder == "Invoice Number") {
            $orderby = "invoicenum";
        } else {
            if ($sortorder == "Date Paid") {
                $orderby = "datepaid";
            } else {
                if ($sortorder == "Due Date") {
                    $orderby = "duedate";
                } else {
                    if ($sortorder == "Client ID") {
                        $orderby = "userid";
                    } else {
                        if ($sortorder == "Client Name") {
                            $orderby = "tblclients`.`firstname` ASC,`tblclients`.`lastname";
                        }
                    }
                }
            }
        }
        $clientWhere = is_numeric($userid) && 0 < $userid ? " AND tblinvoices.userid=" . (int) $userid : "";
        $filterby = "datepaid";
        if ($filterby == "Date Created") {
            $filterby = "date";
        } else {
            if ($filterby == "Due Date") {
                $filterby = "duedate";
            }
        }
        $dateRange = WHMCS\Carbon::parseDateRangeValue($range);
        $datefrom = $dateRange["from"]->toDateTimeString();
        $dateto = $dateRange["to"]->toDateTimeString();
        $statuses_in_clause = db_build_in_array($statuses);
        $paymentmethods_in_clause = db_build_in_array($paymentmethods);
        $batchpdf_where_clause = "tblinvoices." . $filterby . " >= '" . $datefrom . "' AND tblinvoices." . $filterby . "<='" . $dateto . "' AND tblinvoices.status IN (" . $statuses_in_clause . ")" . " AND tblinvoices.paymentmethod IN (" . $paymentmethods_in_clause . ")" . $clientWhere;
        $batchpdfresult = select_query("tblinvoices", "tblinvoices.id", $batchpdf_where_clause, $orderby, "ASC", "", "tblclients ON tblclients.id=tblinvoices.userid");
        $numrows = mysql_num_rows($batchpdfresult);
        if (!$numrows) {
            redir("report=pdf_batch&noresults=1", "reports.php");
        } else {
            header("Content-Disposition: attachment; filename=\"" . $aInt->lang("reports", "pdfbatch") . " " . date("Y-m-d") . ".pdf\"");
        }
        while ($data = mysql_fetch_array($batchpdfresult)) {
            $invoice->pdfInvoicePage($data["id"]);
        }
        $pdfdata = $invoice->pdfOutput();
        echo $pdfdata;
    }
}
function csv_clean($var)
{
    $var = WHMCS\Input\Sanitize::decode($var);
    $var = strip_tags($var);
    $var = str_replace("\"", "\"\"", $var);
    if (strstr($var, ",")) {
        $var = "\"" . $var . "\"";
    }
    return $var;
}
function csv_output($query)
{
    global $fields;
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        foreach ($fields as $field) {
            echo csv_clean($data[$field]) . ",";
        }
        echo "\n";
    }
}

?>