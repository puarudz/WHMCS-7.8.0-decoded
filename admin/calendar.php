<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$whmcs = App::self();
$displayTypes = $whmcs->get_req_var("displaytypes");
$currentDate = $whmcs->get_req_var("currentDate");
$allowedViews = array("month", "agendaDay", "agendaWeek");
$aInt = new WHMCS\Admin("Calendar");
$aInt->title = $aInt->lang("utilities", "calendar");
$aInt->sidebar = "utilities";
$aInt->icon = "calendar";
if (!function_exists("json_encode")) {
    $aInt->gracefulExit("The JSON PHP extension is required for this page to be able to function. Please add it and then try again.");
}
if ($action == "fetch") {
    check_token("WHMCS.admin.default");
    $ymd = $whmcs->get_req_var("ymd");
    $time = $whmcs->get_req_var("time");
    $view = $whmcs->get_req_var("view");
    if (in_array($view, $allowedViews)) {
        WHMCS\Cookie::set("CalendarView", $view);
    }
    $startValue = fromMySQLDate(substr($ymd, 0, 4) . "-" . substr($ymd, 4, 2) . "-" . substr($ymd, 6, 2)) . " " . $time;
    $endValue = fromMySQLDate(substr($ymd, 0, 4) . "-" . substr($ymd, 4, 2) . "-" . substr($ymd, 6, 2)) . " 23:59:59";
    $fetchOutput = "<p align=\"center\">\n    <b>" . $aInt->lang("calendar", "addnew") . "</b>\n</p>\n<table>\n    <tr>\n        <td colspan=\"2\">\n            " . $aInt->lang("calendar", "title") . "<br />\n            <input type=\"text\" name=\"title\" class=\"form-control\" />\n        </td>\n    </tr>\n    <tr>\n        <td colspan=\"2\">\n            " . $aInt->lang("calendar", "description") . "<br />\n            <input type=\"text\" name=\"desc\" class=\"form-control\" />\n        </td>\n    </tr>\n    <tr>\n        <td width=\"175\">\n            " . $aInt->lang("calendar", "startDateTime") . "<br />\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"start\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"start\"\n                       type=\"text\"\n                       name=\"start\"\n                       value=\"" . $startValue . "\"\n                       class=\"form-control date-picker-single future time\"\n                />\n            </div>\n        </td>\n        <td width=\"175\">\n            " . $aInt->lang("calendar", "endDateTime") . "<br />\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"end\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"end\"\n                       type=\"text\"\n                       name=\"end\"\n                       value=\"\"\n                       class=\"form-control date-picker-single future time\"\n                       data-original-value=\"" . $endValue . "\"\n                       disabled=\"disabled\"\n                />\n            </div>\n            <input type=\"hidden\" name=\"endHidden\" id=\"endHidden\" value=\"" . $endValue . "\" />\n        </td>\n    </tr>\n</table>\n<p>\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" name=\"allday\" id=\"allday\" value=\"1\" checked />\n        " . $aInt->lang("calendar", "allDay") . "\n    </label>\n</p>\n<p>\n    " . $aInt->lang("calendar", "recurEvery") . "\n    <input type=\"text\" class=\"form-control input-inline input-35\" name=\"recurevery\" />\n    <select name=\"recurtype\" class=\"form-control select-inline\">\n        <option value=\"days\">" . $aInt->lang("calendar", "days") . "</option>\n        <option value=\"weeks\">" . $aInt->lang("calendar", "weeks") . "</option>\n        <option value=\"months\">" . $aInt->lang("calendar", "months") . "</option>\n        <option value=\"years\">" . $aInt->lang("calendar", "years") . "</option>\n    </select> for \n        <input type=\"text\" class=\"form-control input-inline input-35\" name=\"recurtimes\" />\n        " . $aInt->lang("calendar", "times") . "*\n</p>\n<p>\n    *" . $aInt->lang("calendar", "zeroUnlimited") . "\n</p>\n<p align=\"center\">\n    <input type=\"submit\" class=\"btn btn-primary\" value=\"" . $aInt->lang("global", "save") . "\" />\n    <input type=\"button\" class=\"btn btn-default\" value=\"" . $aInt->lang("global", "cancel") . "\" onclick=\"jQuery('#caledit').fadeOut()\" />\n</p>";
    echo $fetchOutput;
    exit;
}
if ($action == "refresh") {
    check_token("WHMCS.admin.default");
    WHMCS\Cookie::set("CalendarDisplayTypes", $displayTypes, time() + 86400 * 365);
    WHMCS\Cookie::set("CalendarStartDate", $currentDate);
    redir();
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $id = $whmcs->get_req_var("id");
    $start = $whmcs->get_req_var("start");
    $end = $whmcs->get_req_var("end");
    $allday = $whmcs->get_req_var("allday");
    $desc = $whmcs->get_req_var("desc");
    $title = $whmcs->get_req_var("title");
    $recurevery = $whmcs->get_req_var("recurevery");
    $recurtype = $whmcs->get_req_var("recurtype");
    $start = toMySQLDate($start);
    $start = new DateTime($start, new DateTimeZone("UTC"));
    $start = strtotime($start->format("c"), time());
    $end = toMySQLDate($end);
    if (!$allday && $end) {
        $end = new DateTime($end, new DateTimeZone("UTC"));
        $end = strtotime($end->format("c"), time());
    }
    if ($id) {
        update_query("tblcalendar", array("title" => $title, "desc" => $desc, "start" => $start, "end" => $end, "allday" => $allday), array("id" => $id));
    } else {
        $neweventid = insert_query("tblcalendar", array("title" => $title, "desc" => $desc, "start" => $start, "end" => $end, "allday" => $allday));
        if ($recurevery && $recurtype) {
            if ($recurtimes == 0) {
                $recurtimes = 99;
                $recurtype = "years";
            }
            for ($i = 1; $i <= $recurtimes - 1; $i++) {
                $nexttime = $nexttime ? strtotime("+" . $recurevery . " " . $recurtype, $nexttime) : $start;
                $rstart = strtotime(date("Ymd", strtotime("+" . $recurevery . " " . $recurtype, $nexttime)) . $starttime);
                $rend = $endtime ? strtotime(date("Ymd", strtotime("+" . $recurevery . " " . $recurtype, $nexttime)) . $endtime) : "";
                insert_query("tblcalendar", array("title" => $title, "desc" => $desc, "start" => $rstart, "end" => $rend, "allday" => $allday, "recurid" => $neweventid));
                update_query("tblcalendar", array("recurid" => $neweventid), array("id" => $neweventid));
            }
        }
    }
    redir();
}
if ($action == "update") {
    check_token("WHMCS.admin.default");
    $days = (int) $whmcs->get_req_var("days");
    $minutes = (int) $whmcs->get_req_var("minutes");
    $id = (int) $whmcs->get_req_var("id");
    $type = $whmcs->get_req_var("type");
    if ($type == "move") {
        $start = get_query_val("tblcalendar", "start", array("id" => $id));
        $start = $start + $days * 24 * 60 * 60 + $minutes * 60;
        $end = get_query_val("tblcalendar", "end", array("id" => $id));
        if (0 < $end) {
            $end = $end + $days * 24 * 60 * 60 + $minutes * 60;
        }
        $allday = $allday == "true" ? "1" : "0";
        if ($allday) {
            $end = 0;
        }
        update_query("tblcalendar", array("start" => $start, "allday" => $allday, "end" => $end), array("id" => $id));
    } else {
        if ($type == "resize") {
            $data = get_query_vals("tblcalendar", "start, end", array("id" => $id));
            $start = $data["start"];
            $end = $data["end"];
            if (!$end) {
                $end = $start;
            }
            $end = $end + $days * 24 * 60 * 60 + $minutes * 60;
            update_query("tblcalendar", array("end" => $end), array("id" => $id));
        }
    }
    exit;
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblcalendar", array("id" => $id));
    exit;
}
if ($action == "recurdelete") {
    check_token("WHMCS.admin.default");
    delete_query("tblcalendar", array("recurid" => $recurid));
    redir();
}
$caldisplaytypes = WHMCS\Cookie::get("CalendarDisplayTypes", 1);
if ($caldisplaytypes["events"] == "on") {
    add_hook("CalendarEvents", "-999", "calendar_core_calendar");
}
if ($caldisplaytypes["services"] == "on") {
    add_hook("CalendarEvents", "-998", "calendar_core_products");
}
if ($caldisplaytypes["addons"] == "on") {
    add_hook("CalendarEvents", "-997", "calendar_core_addons");
}
if ($caldisplaytypes["domains"] == "on") {
    add_hook("CalendarEvents", "-996", "calendar_core_domains");
}
if ($caldisplaytypes["todo"] == "on") {
    add_hook("CalendarEvents", "-995", "calendar_core_todoitems");
}
$calStartDate = WHMCS\Cookie::get("CalendarStartDate");
if ($calStartDate) {
    WHMCS\Cookie::delete("CalendarStartDate");
} else {
    $calStartDate = date("Y-m-d");
}
$calView = WHMCS\Cookie::get("CalendarView");
WHMCS\Cookie::delete("CalendarView");
if (!in_array($calView, $allowedViews)) {
    $calView = "month";
}
$calevents = array();
foreach ($hooks["CalendarEvents"] as $calfeed) {
    $calevents[] = $calfeed["hook_function"];
}
if ($_REQUEST["getcalfeed"]) {
    $feed = $_REQUEST["feed"];
    $start = (int) $_REQUEST["start"];
    $end = (int) $_REQUEST["end"];
    if (in_array($feed, $calevents)) {
        $events = call_user_func($feed, array("start" => $start, "end" => $end));
        if (!is_array($events)) {
            $events = array();
        }
        echo json_encode($events);
    }
    exit;
}
if ($_REQUEST["editentry"]) {
    check_token("WHMCS.admin.default");
    $view = $whmcs->get_req_var("view");
    if (in_array($view, $allowedViews)) {
        WHMCS\Cookie::set("CalendarView", $view);
    }
    $data = get_query_vals("tblcalendar", "", array("id" => $id));
    $start = gmstrftime("%Y-%m-%dT%T", $data["start"]);
    $end = "";
    if (0 < $data["end"]) {
        $end = gmstrftime("%Y-%m-%dT%T", $data["end"]);
    }
    $endDisabled = $data["allday"] ? " disabled" : "";
    $allDayChecked = $data["allday"] ? " checked" : "";
    $startValue = fromMySQLDate($start, 1) . ":" . date("s", $start);
    $endValue = $end ? fromMySQLDate($end, 1) . ":" . date("s", $end) : "";
    $hiddenEnd = $end ? $endValue : fromMySQLDate(gmstrftime("%Y-%m-%d", $data["start"])) . " 23:59:59";
    $htmlContent = "<div align=\"center\">\n    <b>" . $aInt->lang("calendar", "editevent") . "</b>\n</div>\n<input type=\"hidden\" name=\"id\" value=\"" . $data["id"] . "\" />\n<table>\n    <tr>\n        <td colspan=\"2\">\n            " . $aInt->lang("calendar", "title") . "<br />\n            <input type=\"text\" name=\"title\" class=\"form-control\" value=\"" . $data["title"] . "\" />\n        </td>\n    </tr>\n    <tr>\n        <td colspan=\"2\">\n            " . $aInt->lang("calendar", "description") . "<br />\n            <input type=\"text\" name=\"desc\" class=\"form-control\" value=\"" . $data["desc"] . "\" />\n        </td>\n    </tr>\n    <tr>\n        <td width=\"175\">\n            " . $aInt->lang("calendar", "startDateTime") . "<br />\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"start\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"start\"\n                       type=\"text\"\n                       name=\"start\"\n                       value=\"" . $startValue . "\"\n                       class=\"form-control date-picker-single future time\"\n                />\n            </div>\n        </td>\n        <td width=\"175\">\n            " . $aInt->lang("calendar", "endDateTime") . "<br />\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"end\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"end\"\n                       type=\"text\"\n                       name=\"end\"\n                       value=\"" . $endValue . "\"\n                       class=\"form-control date-picker-single future time\"\n                       data-original-value=\"" . $endValue . "\"\n                       " . $endDisabled . "\n                />\n            </div>\n            <input type=\"hidden\" name=\"endHidden\" id=\"endHidden\" value=\"" . $hiddenEnd . "\" />\n        </td>\n    </tr>\n</table>\n<p>\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" value=\"1\" id=\"allday\" name=\"allday\"" . $allDayChecked . " /> " . $aInt->lang("calendar", "allDay") . "\n    </label>";
    if ($data["recurid"]) {
        $linkToken = generate_token("link");
        $htmlContent .= "    <label style=\"float:right;margin-right:9%;\">\n        <a href=\"calendar.php?action=recurdelete&recurid=" . $data["recurid"] . $linkToken . "\">\n            " . $aInt->lang("calendar", "deleteRecurringEvent") . "\n        </a>\n    </label>";
    }
    $htmlContent .= "</p><br />\n<div align=\"center\">\n    <input type=\"submit\" value=\"" . $aInt->lang("global", "save") . "\" />\n    <input type=\"button\" value=\"" . $aInt->lang("global", "delete") . "\" onclick=\"deleteEntry('" . $data["id"] . "')\" />\n    <input type=\"button\" value=\"" . $aInt->lang("global", "cancel") . "\" onclick=\"jQuery('#caledit').fadeOut()\" />\n</div>";
    $editContent = array("defaultsdate " => date("Y, n, j", $data["start"]), "defaultedate" => $data["end"] ? date("Y, n, j", $data["end"]) : date("Y, n, j", $data["start"]), "defaultsh" => date("H", $data["start"]), "defaultsm" => date("i", $data["start"]), "defaulteh" => date("H", $data["end"]), "defaultem" => date("i", $data["end"]), "html" => $htmlContent);
    echo json_encode($editContent);
    exit;
}
ob_start();
$calcolors = array();
$calcolors["calendar_core_calendar"] = array("bg" => "3366CC", "text" => "ffffff");
$calcolors["calendar_core_products"] = array("bg" => "FBE983", "text" => "000000");
$calcolors["calendar_core_addons"] = array("bg" => "F83A22", "text" => "ffffff");
$calcolors["calendar_core_domains"] = array("bg" => "B3DC6C", "text" => "000000");
$calcolors["calendar_core_todoitems"] = array("bg" => "CAD5D5", "text" => "000000");
$calcolors[] = array("bg" => "F83A22", "text" => "ffffff");
$calcolors[] = array("bg" => "B3DC6C", "text" => "000000");
$calcolors[] = array("bg" => "cc0000", "text" => "ffffff");
$monthsShortArray = array($aInt->lang("months", "january"), $aInt->lang("months", "february"), $aInt->lang("months", "march"), $aInt->lang("months", "april"), $aInt->lang("months", "may"), $aInt->lang("months", "june"), $aInt->lang("months", "july"), $aInt->lang("months", "august"), $aInt->lang("months", "september"), $aInt->lang("months", "october"), $aInt->lang("months", "november"), $aInt->lang("months", "december"));
$monthsShortList = "";
foreach ($monthsShortArray as $month) {
    $monthsShortList .= "'" . $month . "',";
}
$daysShortArray = array($aInt->lang("days", "sun"), $aInt->lang("days", "mon"), $aInt->lang("days", "tue"), $aInt->lang("days", "wed"), $aInt->lang("days", "thu"), $aInt->lang("days", "fri"), $aInt->lang("days", "sat"));
$daysShortList = "";
foreach ($daysShortArray as $day) {
    $daysShortList .= "'" . $day . "',";
}
$daysArray = array($aInt->lang("days", "sunday"), $aInt->lang("days", "monday"), $aInt->lang("days", "tuesday"), $aInt->lang("days", "wednesday"), $aInt->lang("days", "thursday"), $aInt->lang("days", "friday"), $aInt->lang("days", "saturday"));
$daysList = "";
foreach ($daysArray as $day) {
    $daysList .= "'" . $day . "',";
}
echo WHMCS\View\Asset::cssInclude("fullcalendar.min.css") . WHMCS\View\Asset::jsInclude("fullcalendar.min.js");
echo "\n<script type='text/javascript'>\n\$(document).ready(function() {\n    var dateTimePicker = {\n        singleDatePicker: true,\n        autoUpdateInput: false,\n        ranges: adminJsVars.dateRangePicker.futureTimeSingleRanges,\n        alwaysShowCalendars: true,\n        showDropdowns: true,\n        timePicker: true,\n        drops: 'up',\n        opens: 'center',\n        locale: {\n            format: adminJsVars.dateTimeRangeFormat,\n            customRangeLabel: adminJsVars.dateRangePicker.customRangeLabel,\n            monthNames: adminJsVars.dateRangePicker.months,\n            daysOfWeek: adminJsVars.dateRangePicker.daysOfWeek\n        }\n    };\n\$('#calendar').fullCalendar({\n\n    header: {\n        left: 'prev,today,next',\n        center: 'title',\n        right: 'month,agendaWeek,agendaDay'\n    },\n\n    defaultView: '";
echo $calView;
echo "',\n\n    buttonText: {\n        today: '";
echo $aInt->lang("calendar", "today");
echo "',\n        month: '";
echo $aInt->lang("calendar", "month");
echo "',\n        week: '";
echo $aInt->lang("calendar", "week");
echo "',\n        day: '";
echo $aInt->lang("calendar", "day");
echo "'\n    },\n\n    monthNames: [";
echo $monthsShortList;
echo "],\n    dayNamesShort: [";
echo $daysShortList;
echo "],\n    dayNames: [";
echo $daysList;
echo "],\n\n    defaultDate: '";
echo $calStartDate;
echo "',\n\n    dayClick: function(date, jsEvent, view) {\n        var dateclicked = date.format('YYYYMMDD');\n        var timeclicked = date.format('HH:mm');\n        var xpos = jsEvent.pageX;\n        if (xpos>(\$(window).width()-400)) {\n            xpos = xpos-350;\n        }\n        \$(\"#caledit\").css(\"top\", jsEvent.pageY);\n        \$(\"#caledit\").css(\"left\", xpos);\n        \$(\"#caledit\").html('<img src=\"images/loading.gif\" /> ";
echo $aInt->lang("global", "loading", 1);
echo "');\n        \$(\"#caledit\").load(\"calendar.php?action=fetch&ymd=\"+dateclicked+\"&time=\"+timeclicked+\"&view=\"+view.name+\"&token=";
echo generate_token("plain");
echo "\", function() {\n            \$('#allday').on('click', function() {\n                if (\$('#allday').is(':checked')) {\n                    \$('#end').prop(\"disabled\", true);\n                    \$('#end').val('');\n                } else {\n                    \$('#end').prop(\"disabled\", false);\n                    \$('#end').val(\$('#endHidden').val());\n                    \$('#end').daterangepicker(dateTimePicker)\n                    .on('apply.daterangepicker', function(ev, picker) {\n                        jQuery(this).data('original-value', picker.startDate.format(adminJsVars.dateTimeRangeFormat))\n                            .val(picker.startDate.format(adminJsVars.dateTimeRangeFormat));\n                    }).on('cancel.daterangepicker', function(ev, picker) {\n                        jQuery(this).val(jQuery(this).data('original-value'));\n                    });\n                }\n            });\n            \$('#start').daterangepicker(dateTimePicker)\n            .on('apply.daterangepicker', function(ev, picker) {\n                jQuery(this).data('original-value', picker.startDate.format(adminJsVars.dateTimeRangeFormat))\n                    .val(picker.startDate.format(adminJsVars.dateTimeRangeFormat));\n            }).on('cancel.daterangepicker', function(ev, picker) {\n                jQuery(this).val(jQuery(this).data('original-value'));\n            });\n        });\n        \$(\"#caledit\").fadeIn();\n\n    },\n    eventClick: function(calEvent, jsEvent, view) {\n\n        /**\n         * If the event being clicked has an URL, load the URL instead of the\n         * popup box to add an event.\n         */\n        if (calEvent.url) {\n            return true;\n        }\n\n        var xpos = jsEvent.pageX;\n        if (xpos>(\$(window).width()-400)) {\n            xpos = xpos-350;\n        }\n        \$(\"#caledit\").css(\"top\", jsEvent.pageY);\n        \$(\"#caledit\").css(\"left\", xpos);\n        \$(\"#caledit\").html('<img src=\"images/loading.gif\" /> ";
echo $aInt->lang("global", "loading", 1);
echo "');\n        WHMCS.http.jqClient.post(\"calendar.php\", {\n            editentry: \"1\",\n            id: calEvent.id,\n            view: view.name,\n            token: \"";
echo generate_token("plain");
echo "\"\n        }, function(data) {\n            data = JSON.parse(data);\n\n            \$(\"#caledit\").html(data.html);\n            \$('#start').daterangepicker(dateTimePicker)\n            .on('apply.daterangepicker', function(ev, picker) {\n                jQuery(this).data('original-value', picker.startDate.format(adminJsVars.dateTimeRangeFormat))\n                    .val(picker.startDate.format(adminJsVars.dateTimeRangeFormat));\n            }).on('cancel.daterangepicker', function(ev, picker) {\n                jQuery(this).val(jQuery(this).data('original-value'));\n            });\n            \$('#allday').on('click', function() {\n                if (\$('#allday').is(':checked')) {\n                    \$('#end').prop(\"disabled\", true);\n                    \$('#end').val('');\n                } else {\n                    \$('#end').prop(\"disabled\", false);\n                    \$('#end').val(\$('#endHidden').val());\n                }\n            });\n            \$('#end').daterangepicker(dateTimePicker)\n            .on('apply.daterangepicker', function(ev, picker) {\n                jQuery(this).data('original-value', picker.startDate.format(adminJsVars.dateTimeRangeFormat))\n                    .val(picker.startDate.format(adminJsVars.dateTimeRangeFormat));\n            }).on('cancel.daterangepicker', function(ev, picker) {\n                jQuery(this).val(jQuery(this).data('original-value'));\n            });\n        });\n        \$(\"#caledit\").fadeIn();\n\n    },\n    eventDrop: function(calEvent, calDelta, revertFunc, jsEvent, ui, view) {\n\n        WHMCS.http.jqClient.post(\"calendar.php\", {\n            action: \"update\",\n            id: calEvent.id,\n            type: \"move\",\n            days: calDelta.days(),\n            minutes: (calDelta.hours() * 60) + calDelta.minutes(),\n            allday: calEvent.allDay,\n            token: \"";
echo generate_token("plain");
echo "\"\n        });\n\n    },\n    eventResize: function(calEvent, calDelta, revertFunc, jsEvent, ui, view) {\n\n        WHMCS.http.jqClient.post(\"calendar.php\", {\n            action: \"update\",\n            id: calEvent.id,\n            type: \"resize\",\n            days: calDelta.days(),\n            minutes: (calDelta.hours() * 60) + calDelta.minutes(),\n            token: \"";
echo generate_token("plain");
echo "\"\n        });\n\n    },\n    eventSources: [\n    ";
$i = 0;
foreach ($calevents as $calevent) {
    if (isset($calcolors[$calevent])) {
        $colors = $calcolors[$calevent];
    } else {
        if (!isset($calcolors[$i])) {
            $i = 0;
        }
        $colors = $calcolors[$i];
        $i++;
    }
    echo "{\n                url: 'calendar.php?getcalfeed=1&feed=" . $calevent . "',\n                color: '#" . $colors["bg"] . "',\n                textColor: '#" . $colors["text"] . "'\n            }," . PHP_EOL;
}
echo "    ]\n\n});\n\n\$('input[name^=\"displaytypes\"]').click(function() {\n    var moment = \$('#calendar').fullCalendar('getDate');\n    \$('#currentDate').val(moment.format());\n    var submitForm = \$(\"#calendarcontrols\").find(\"form\");\n    submitForm.submit();\n});\n\n});\n\nfunction deleteEntry(id) {\n    jQuery(\"#calendar\").fullCalendar('removeEvents', id);\n    WHMCS.http.jqClient.post(\"calendar.php\", { action: \"delete\", id: id, token: \"";
echo generate_token("plain");
echo "\" });\n    jQuery(\"#caledit\").fadeOut();\n}\n\n</script>\n<style type=\"text/css\">\n#calendar {\n    margin: 0 auto;\n    width: 90%;\n    max-width: 1200px;\n}\n#caledit {\n    display:none;\n    position:absolute;\n    padding:8px;\n    background-color:#f2f2f2;\n    border:1px solid #ccc;\n    width:368px;\n    min-height:150px;\n    z-index:100;\n    -moz-border-radius: 5px;\n    -webkit-border-radius: 5px;\n    -o-border-radius: 5px;\n    border-radius: 5px;\n}\n#caledit p {\n    margin: 0 0 0 5px;\n}\n#calendarcontrols {\n    float: right;\n    margin: -45px 0 0 0;\n    padding: 5px 15px;\n    background-color: #F2F2F2;\n    border: 1px dashed #CCC;\n    font-size: 11px;\n    -moz-border-radius: 5px;\n    -webkit-border-radius: 5px;\n    -o-border-radius: 5px;\n    border-radius: 5px;\n}\n#calendarcontrols table td {\n    font-size: 11px;\n}\n</style>\n";
$serviceChecked = $caldisplaytypes["services"] == "on" ? " checked" : "";
$addonChecked = $caldisplaytypes["addons"] == "on" ? " checked" : "";
$domainsChecked = $caldisplaytypes["domains"] == "on" ? " checked" : "";
$todoChecked = $caldisplaytypes["todo"] == "on" ? " checked" : "";
$eventsChecked = $caldisplaytypes["events"] == "on" ? " checked" : "";
$serviceTitle = $aInt->lang("services", "title");
$addonTitle = $aInt->lang("addons", "title");
$domainsTitle = $aInt->lang("domains", "title");
$todoTitle = $aInt->lang("calendar", "todoitems");
$eventsTitle = $aInt->lang("calendar", "events");
$calControls = "<div id=\"calendarcontrols\">\n    <form method=\"post\" name=\"refreshform\" action=\"calendar.php?action=refresh\">\n        <input id=\"currentDate\" type=\"hidden\" name=\"currentDate\" value=\"\" />\n        <strong>" . $aInt->lang("calendar", "showHide") . ":</strong>\n\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"displaytypes[services]\"" . $serviceChecked . "/> " . $serviceTitle . "\n        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"displaytypes[addons]\"" . $addonChecked . "/> " . $addonTitle . "\n        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"displaytypes[domains]\"" . $domainsChecked . "/> " . $domainsTitle . "\n        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"displaytypes[todo]\"" . $todoChecked . "/> " . $todoTitle . "\n        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"displaytypes[events]\"" . $eventsChecked . "/> " . $eventsTitle . "\n        </label>\n    </form>\n</div>";
echo $calControls;
echo "<div id=\"calendar\"></div>\n\n<form method=\"post\" action=\"calendar.php?action=save\">\n<div id=\"caledit\"></div>\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();
function calendar_core_calendar($vars)
{
    $events = array();
    if ($vars["start"] == 0) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("tblcalendar", "", "start>=" . $queryStart . " AND end<=" . $queryEnd);
    while ($data = mysql_fetch_assoc($result)) {
        $start = gmstrftime("%Y-%m-%dT%T", $data["start"]);
        if (0 < $data["end"]) {
            $end = gmstrftime("%Y-%m-%dT%T", $data["end"]);
        }
        $events[] = array("id" => $data["id"], "title" => $data["title"], "start" => $start, "end" => $end, "allDay" => $data["allday"] ? true : false, "editable" => true);
    }
    return $events;
}
function calendar_core_products($vars)
{
    $events = array();
    if ($vars["start"] == 0) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("tblhosting", "tblhosting.id, tblhosting.domain, tblhosting.nextduedate, tblproducts.name", "domainstatus IN ('Active','Suspended') AND nextduedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'", "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
    while ($data = mysql_fetch_assoc($result)) {
        $events[] = array("id" => $data["id"], "title" => $data["name"] . ($data["domain"] ? " - " . $data["domain"] : ""), "start" => $data["nextduedate"], "allDay" => true, "editable" => false, "url" => "clientshosting.php?id=" . $data["id"]);
    }
    return $events;
}
function calendar_core_addons($vars)
{
    $addons = array();
    $result = select_query("tbladdons", "id, name", "");
    while ($data = mysql_fetch_array($result)) {
        $addon_id = $data["id"];
        $addons[$addon_id] = $data["name"];
    }
    $events = array();
    if ($vars["start"] == 0) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("tblhostingaddons", "id, addonid, name, hostingid, nextduedate", "status IN ('Active', 'Suspended') AND nextduedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'");
    while ($data = mysql_fetch_assoc($result)) {
        $name = 0 < strlen($data["name"]) ? $data["name"] : $addons[$data["addonid"]];
        $events[] = array("id" => $data["id"], "title" => $name, "start" => $data["nextduedate"], "allDay" => true, "editable" => false, "url" => "clientsservices.php?id=" . $data["hostingid"] . "&aid=" . $data["id"]);
    }
    return $events;
}
function calendar_core_domains($vars)
{
    $events = array();
    if ($vars["start"] == 0) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("tbldomains", "", "status IN ('Active', 'Suspended') AND nextduedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'");
    while ($data = mysql_fetch_assoc($result)) {
        $events[] = array("id" => $data["id"], "title" => "Domain Renewal - " . $data["domain"], "start" => $data["nextduedate"], "allDay" => true, "editable" => false, "url" => "clientsdomains.php?id=" . $data["id"]);
    }
    return $events;
}
function calendar_core_todoitems($vars)
{
    $events = array();
    if ($vars["start"] == 0) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("tbltodolist", "", "duedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'");
    while ($data = mysql_fetch_assoc($result)) {
        $events[] = array("id" => "td" . $data["id"], "title" => $data["title"], "start" => $data["duedate"], "allDay" => true, "editable" => true, "url" => "todolist.php?action=edit&id=" . $data["id"]);
    }
    return $events;
}

?>