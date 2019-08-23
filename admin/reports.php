<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Reports");
$aInt->title = "Reports";
$aInt->sidebar = "reports";
$aInt->icon = "reports";
$aInt->requiredFiles(array("reportfunctions"));
$aInt->helplink = "Reports";
$report = $whmcs->get_req_var("report");
$displaygraph = $whmcs->get_req_var("displaygraph");
$print = $whmcs->get_req_var("print");
$currencyid = $whmcs->get_req_var("currencyid");
$month = $whmcs->get_req_var("month");
$year = $whmcs->get_req_var("year");
$adminRoleData = WHMCS\Database\Capsule::table("tbladminroles")->join("tbladmins", "tbladmins.roleid", "=", "tbladminroles.id")->where("tbladmins.id", "=", WHMCS\Session::get("adminid"))->first(array("tbladminroles.*"));
$text_reports = getReportsList();
$deniedReports = array_filter(explode(",", $adminRoleData->reports));
if ($report && in_array($report, $deniedReports)) {
    logAdminActivity("Access Denied to " . $text_reports[$report] . " Report");
    $aInt->content = AdminLang::trans("reports.accessDenied", array(":report" => $text_reports[$report]));
    $aInt->display();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($displaygraph) {
    $displaygraph = preg_replace("/[^0-9a-z-_]/i", "", $displaygraph);
    $graphfile = "../modules/reports/" . $displaygraph . ".php";
    if (file_exists($graphfile)) {
        require $graphfile;
        exit;
    }
    exit("Graph File Not Found");
}
if ($print) {
    echo "<html>\n<head>\n<title>WHMCS - Printer Friendly Report</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=";
    echo $CONFIG["Charset"];
    echo "\">\n";
    echo DI::make("asset")->cssInclude("bootstrap.min.css") . DI::make("asset")->cssInclude("fontawesome-all.min.css");
    echo "<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>\n</head>\n<body>\n<div class=\"container-fluid\">\n<p><img src=\"";
    echo $CONFIG["LogoURL"];
    echo "\"></p>\n";
} else {
    $aInt->assign("denied_reports", $deniedReports);
    $aInt->assign("text_reports", $text_reports);
    $aInt->assign("graph_reports", array());
    ob_start();
}
if ($report) {
    $optionalText = AdminLang::trans("global.optional");
    $dateRangeText = AdminLang::trans("fields.daterange");
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
    $requeststr = "?" . http_build_query($_REQUEST);
    $chart = new WHMCS\Chart();
    $gateways = new WHMCS\Gateways();
    $data = $reportdata = $chartsdata = $args = array();
    if ($month == "1") {
        $prevMonthLink = "month=12&year=" . ($year - 1);
        $prevMonthText = "&laquo; December " . ($year - 1);
    } else {
        $prevMonthLink = "month=" . ($month - 1) . "&year=" . $year;
        $prevMonthText = "&laquo; " . $months[$month - 1] . " " . $year;
    }
    if ($year . str_pad($month, 2, "0", STR_PAD_LEFT) < date("Ym")) {
        if ($month == "12") {
            $nextMonthLink = "month=1&year=" . ($year + 1);
            $nextMonthText = "January " . ($year + 1) . " &raquo;";
        } else {
            $nextMonthLink = "month=" . ($month + 1) . "&year=" . $year;
            $nextMonthText = $months[$month + 1] . " " . $year . " &raquo;";
        }
    } else {
        $nextMonthLink = $nextMonthText = "";
    }
    $prevYearLink = "year=" . ($year - 1);
    $prevYearText = "&laquo; " . ($year - 1);
    if ($year + 1 <= date("Y")) {
        $nextYearLink = "year=" . ($year + 1);
        $nextYearText = $year + 1 . " &raquo;";
    } else {
        $nextYearLink = $nextYearText = "";
    }
    $moduleType = $whmcs->get_req_var("moduletype");
    $moduleName = $whmcs->get_req_var("modulename");
    $subDirectory = $whmcs->get_req_var("subdir");
    $reportPath = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR;
    if ($moduleType == "addons") {
        if (!isValidforPath($moduleName) || $subDirectory && !isValidforPath($subDirectory)) {
            redir();
        }
        $reportPath .= "addons" . DIRECTORY_SEPARATOR . $moduleName;
        if ($subDirectory) {
            $reportPath .= DIRECTORY_SEPARATOR . $subDirectory;
        }
    } else {
        $reportPath .= "reports";
    }
    $reportPath .= DIRECTORY_SEPARATOR . preg_replace("/[^0-9a-z-_]/i", "", $report) . ".php";
    if (file_exists($reportPath)) {
        require $reportPath;
    } else {
        redir();
    }
    if (!is_array($reportdata)) {
        exit("\$reportdata must be returned as an array");
    }
    run_hook("ReportViewPreOutput", array("report" => $report, "moduleType" => $moduleType, "moduleName" => $moduleName));
    $requestString = http_build_query($_REQUEST);
    if (!$print) {
        if ($whmcs->get_req_var("report") != "pdf_batch") {
            echo "<div class=\"pull-right btn-group btn-group-sm\">\n                    <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-expanded=\"false\">\n                    <i class=\"fas fa-cogs\"></i> " . $aInt->lang("reports", "tools") . " <span class=\"caret\"></span>\n                    </button>\n                    <ul class=\"dropdown-menu\" role=\"menu\">\n                    <li><a href=\"csvdownload.php?" . $requestString . "\"><i class=\"fas fa-download\"></i> " . $aInt->lang("reports", "exportcsv") . "</a></li>\n                    <li><a href=\"" . $whmcs->getPhpSelf() . "?" . $requestString . "&print=true\"><i class=\"fas fa-print\"></i> " . $aInt->lang("reports", "printableversion") . "</a></li>\n                    </ul>\n                  </div>";
        }
    } else {
        echo "<div class=\"pull-right hidden-print\"><button class=\"btn btn-primary\" onclick=\"window.print()\"><i class=\"fas fa-print\"></i> Print</button></div>";
    }
    if (array_key_exists("title", $reportdata)) {
        echo "<h2>" . $reportdata["title"] . "</h2>";
    }
    if (array_key_exists("description", $reportdata)) {
        echo $print ? addPrintInputToForm("<p>" . $reportdata["description"] . "</p>") : "<p>" . $reportdata["description"] . "</p>";
    }
    if (array_key_exists("currencyselections", $reportdata)) {
        $requestArray = $_REQUEST;
        if (array_key_exists("currencyid", $requestArray)) {
            unset($requestArray["currencyid"]);
        }
        $requestString = http_build_query($requestArray);
        $currencieslist = "";
        foreach ($currencies as $listid => $listname) {
            if ($currencyid == $listid) {
                $currencieslist .= "<b>";
            } else {
                $currencieslist .= "<a href=\"reports.php?" . $requestString . "&currencyid=" . $listid . "\">";
            }
            $currencieslist .= $listname . "</b></a> | ";
        }
        echo "<p align=\"center\">Choose Currency: " . substr($currencieslist, 0, -3) . "</p>";
    }
    if (array_key_exists("headertext", $reportdata)) {
        echo $print ? addPrintInputToForm($reportdata["headertext"] . "<br /><br />") : $reportdata["headertext"] . "<br /><br />";
    }
    if (array_key_exists("tableheadings", $reportdata) && is_array($reportdata["tableheadings"])) {
        echo "<table width=100% class=\"table table-condensed" . ($print ? " table-bordered" : "") . "\">";
        echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\">";
        foreach ($reportdata["tableheadings"] as $heading) {
            echo "<td>" . $heading . "</td>";
        }
        if (array_key_exists("drilldown", $reportdata) && is_array($reportdata["drilldown"])) {
            echo "<td>Drill Down</td>";
        }
        echo "</tr>";
        if (array_key_exists("tablesubheadings", $reportdata) && is_array($reportdata["tablesubheadings"])) {
            echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\">";
            foreach ($reportdata["tablesubheadings"] as $heading) {
                echo "<td>" . $heading . "</td>";
            }
            if (is_array($reportdata["drilldown"])) {
                echo "<td>Drill Down</td>";
            }
            echo "</tr>";
        }
        $columncount = count($reportdata["tableheadings"]);
        if (array_key_exists("drilldown", $reportdata) && is_array($reportdata["drilldown"])) {
            $columncount++;
        }
        if (array_key_exists("tablevalues", $reportdata) && is_array($reportdata["tablevalues"])) {
            foreach ($reportdata["tablevalues"] as $num => $values) {
                if (isset($values[0]) && $values[0] == "HEADER") {
                    echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\">";
                    foreach ($values as $k => $value) {
                        if (0 < $k) {
                            echo "<td>" . $value . "</td>";
                        }
                    }
                    echo "</tr>";
                } else {
                    $rowbgcolor = "#ffffff";
                    if (isset($values[0]) && strlen($values[0]) == 7 && substr($values[0], 0, 1) == "#") {
                        $rowbgcolor = $values[0];
                        unset($values[0]);
                    }
                    echo "<tr bgcolor=\"" . $rowbgcolor . "\" style=\"text-align:center;\">";
                    foreach ($values as $value) {
                        if (substr($value, 0, 2) == "**") {
                            echo "<td bgcolor=\"#efefef\" colspan=\"" . $columncount . "\" align=\"left\">&nbsp;" . substr($value, 2) . "</td>";
                        } else {
                            if (substr($value, 0, 2) == "*+") {
                                echo "<td colspan=\"" . $columncount . "\" align=\"center\">" . substr($value, 2) . "</td>";
                            } else {
                                echo "<td>" . $value . "</td>";
                            }
                        }
                    }
                    if (array_key_exists("drilldown", $reportdata) && is_array($reportdata["drilldown"][$num]["tableheadings"])) {
                        echo "<td><a href=\"reports.php#\" onclick=\"\$('#drilldown" . $num . "').fadeToggle();return false\">Drill Down</a></td>";
                    }
                    echo "</tr>";
                    if (array_key_exists("drilldown", $reportdata) && is_array($reportdata["drilldown"][$num]["tableheadings"])) {
                        echo "<tr bgcolor=\"#FFFFCC\" id=\"drilldown" . $num . "\" style=\"display:none;\"><td colspan=\"" . $columncount . "\" style=\"padding:20px;\">";
                        echo "<table width=100% ";
                        if ($print == "true") {
                            echo "border=1 cellspacing=0";
                        } else {
                            echo "cellspacing=1";
                        }
                        echo " bgcolor=\"#cccccc\"><tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\">";
                        foreach ($reportdata["drilldown"][$num]["tableheadings"] as $value) {
                            echo "<td>" . $value . "</td>";
                        }
                        if (!isset($reportdata["drilldown"][$num]["tablevalues"])) {
                            echo "<tr bgcolor=\"#ffffff\"><td align=\"center\" colspan=\"" . $columncount . "\">No Records Found</td></tr>";
                        } else {
                            foreach ($reportdata["drilldown"][$num]["tablevalues"] as $num => $values) {
                                echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center;\">";
                                foreach ($values as $value) {
                                    echo "<td>" . $value . "</td>";
                                }
                                echo "</tr>";
                            }
                        }
                        echo "</tr></table></td></tr>";
                    }
                }
            }
        } else {
            echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center;\"><td colspan=\"" . $columncount . "\">" . $aInt->lang("reports", "nodata") . "</td></tr>";
        }
        echo "</table>";
    }
    if (array_key_exists("monthspagination", $reportdata) && $reportdata["monthspagination"]) {
        $requestArrayForYears = $_REQUEST;
        if (array_key_exists("month", $requestArrayForYears)) {
            unset($requestArray["month"]);
        }
        if (array_key_exists("year", $requestArrayForYears)) {
            unset($requestArray["year"]);
        }
        $requestString = http_build_query($requestArrayForYears);
        echo "<br /><table width=\"90%\" align=\"center\"><tr><td>" . "<a href=\"reports.php?" . $requestString . "&" . $prevMonthLink . "\">" . $prevMonthText . "</a></td><td align=\"right\">" . "<a href=\"reports.php?" . $requestString . "&" . $nextMonthLink . "\">" . $nextMonthText . "</a></td></tr></table>";
    }
    if (array_key_exists("yearspagination", $reportdata) && $reportdata["yearspagination"]) {
        $requestArrayForMonths = $_REQUEST;
        if (array_key_exists("year", $requestArrayForMonths)) {
            unset($requestArrayForMonths["year"]);
        }
        $requestString = http_build_query($requestArrayForMonths);
        echo "<br /><table width=\"90%\" align=\"center\"><tr><td>" . "<a href=\"reports.php?" . $requestString . "&" . $prevYearLink . "\">" . $prevYearText . "</a></td><td align=\"right\">" . "<a href=\"reports.php?" . $requestString . "&" . $nextYearLink . "\">" . $nextYearText . "</a></td></tr></table>";
    }
    if (is_array($data) && array_key_exists("footertext", $data)) {
        echo $print ? addPrintInputToForm("<p>" . $data["footertext"] . "</p>") : "<p>" . $data["footertext"] . "</p>";
    }
    if (array_key_exists("footertext", $reportdata)) {
        echo $print ? addPrintInputToForm($reportdata["footertext"]) : $reportdata["footertext"];
    }
    run_hook("ReportViewPostOutput", array("report" => $report, "moduleType" => $moduleType, "moduleName" => $moduleName));
} else {
    echo "<p>" . $aInt->lang("reports", "description") . "</p>";
    $reports = array("General" => array("daily_performance", "disk_usage_summary", "monthly_orders", "product_suspensions", "promotions_usage", "ssl_certificate_monitoring", ""), "Billing" => array("aging_invoices", "credits_reviewer", "direct_debit_processing", "sales_tax_liability", "vat_moss", ""), "Income" => array("annual_income_report", "income_forecast", "income_by_product", "monthly_transactions", "sales_tax_liability", "server_revenue_forecasts", ""), "Clients" => array("new_customers", "client_sources", "client_statement", "clients_by_country", "top_10_clients_by_income", "affiliates_overview", "domain_renewal_emails", "customer_retention_time", ""), "Support" => array("support_ticket_replies", "ticket_feedback_scores", "ticket_feedback_comments", "ticket_ratings_reviewer", "ticket_tags", ""), "Exports" => array("client", "clients", "domains", "invoices", "services", "transactions", "pdf_batch", "", "", ""));
    echo "<div class=\"reports-index\">";
    foreach ($reports as $type => $reports_array) {
        echo "<h2>" . $type . "</h2>";
        $reps = array();
        $btnclass = "btn-default";
        if ($type == "General") {
            $btnclass = "btn-info";
        }
        if ($type == "Exports") {
            $btnclass = "btn-inverse";
        }
        foreach ($reports_array as $report_name) {
            $disabled = "";
            if (in_array($report_name, $deniedReports)) {
                $disabled = " disabled=\"disabled\"";
            }
            if (isset($text_reports[$report_name])) {
                $reps[] = "<input type=\"button\" value=\"" . $text_reports[$report_name] . "\" class=\"btn " . $btnclass . "\" onclick=\"window.location='reports.php?report=" . $report_name . "'\" " . $disabled . "/>";
                unset($text_reports[$report_name]);
            }
        }
        echo "<div>" . implode(" ", $reps) . "</div>";
    }
    if (count($text_reports)) {
        echo "<h2>Other</h2>";
        $reps = array();
        foreach ($text_reports as $report_name => $discard) {
            $disabled = "";
            if (in_array($report_name, $deniedReports)) {
                $disabled = " disabled=\"disabled\"";
            }
            if (isset($text_reports[$report_name])) {
                $reps[] = "<input type=\"button\" value=\"" . $text_reports[$report_name] . "\" class=\"btn btn-default\" onclick=\"window.location='reports.php?report=" . $report_name . "'\" " . $disabled . "/>";
            }
        }
        echo "<div>" . implode(" ", $reps) . "</div>";
    }
    echo "</div>";
}
if ($report) {
    echo "<p class=\"text-right text-muted\">" . $aInt->lang("reports", "generatedon") . " " . fromMySQLDate(date("Y-m-d H:i:s"), "time") . "</p>\n<p align=\"center\">";
    if ($print == "true") {
        echo "<a href=\"javascript:window.close()\">" . $aInt->lang("reports", "closewindow") . "</a>";
    }
    echo "</p>";
}
if ($print) {
    echo "\n</div>\n</body>\n</html>";
} else {
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->display();
}

?>