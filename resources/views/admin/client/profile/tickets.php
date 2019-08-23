<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "\n<div class=\"context-btn-container\">\n    <a href=\"supporttickets.php?action=open&amp;userid=";
echo $userId;
echo "\" class=\"btn btn-primary\">\n        <i class=\"fas fa-plus fa-fw\"></i>\n        ";
echo AdminLang::trans("support.opennewticket");
echo "    </a>\n</div>\n\n<div class=\"stat-blocks\">\n    <div class=\"row\">\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
echo $ticketCounts["thisMonth"];
echo "</strong>\n                <p class=\"truncate\">";
echo AdminLang::trans("clientsummary.ticketsThisMonth");
echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
echo $ticketCounts["lastMonth"];
echo "</strong>\n                <p class=\"truncate\">";
echo AdminLang::trans("clientsummary.ticketsLastMonth");
echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
echo $ticketCounts["thisYear"];
echo "</strong>\n                <p class=\"truncate\">";
echo AdminLang::trans("clientsummary.ticketsThisYear");
echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
echo $ticketCounts["lastYear"];
echo "</strong>\n                <p class=\"truncate\">";
echo AdminLang::trans("clientsummary.ticketsLastYear");
echo "</p>\n            </div>\n        </div>\n    </div>\n</div>\n\n<table id=\"tblClientTickets\" class=\"datatable\" width=\"100%\" data-lang-empty-table=\"";
echo AdminLang::trans("global.norecordsfound");
echo "\" data-searching=\"true\" data-responsive=\"true\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <thead>\n        <tr>\n            <th width=\"20\"><input type=\"checkbox\" id=\"checkAllTickets\"></th>\n            <th width=\"20\"></th>\n            <th>";
echo AdminLang::trans("mergefields.dateopened");
echo "</th>\n            <th>";
echo AdminLang::trans("support.department");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.subject");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.status");
echo "</th>\n            <th>";
echo AdminLang::trans("support.lastreply");
echo "</th>\n        </tr>\n    </thead>\n    <tbody>\n        ";
foreach ($tickets as $ticket) {
    echo "            <tr>\n                <td align=\"center\"><input type=\"checkbox\" name=\"id[]\" class=\"ticket-checkbox\" value=\"";
    echo $ticket->id;
    echo "\"></td>\n                <td align=\"center\">\n                    <img src=\"images/";
    echo strtolower($ticket->priority);
    echo "priority.gif\" width=\"16\" height=\"16\" alt=\"";
    echo AdminLang::trans("status." . strtolower($ticket->priority));
    echo "\" class=\"absmiddle\" />\n                </td>\n                <td><span class=\"hidden\">";
    echo $ticket->date->timestamp;
    echo "</span>";
    echo $ticket->date->toAdminDateFormat();
    echo "</td>\n                <td>\n                    ";
    echo $ticket->department->name;
    echo "                    ";
    if ($ticket->flaggedAdminId) {
        echo " (" . $ticket->flaggedAdmin->fullName . ")";
    }
    echo "                </td>\n                <td>\n                    <a href=\"supporttickets.php?action=view&id=";
    echo $ticket->id;
    echo "\">\n                        #";
    echo $ticket->ticketNumber;
    echo " - ";
    echo $ticket->title;
    echo "                    </a>\n                </td>\n                <td>";
    echo getStatusColour($ticket->status);
    echo "</td>\n                <td>\n                    <span class=\"hidden\">";
    echo $ticket->lastReply->toDateTimeString();
    echo "</span>\n                    ";
    echo $ticket->lastReply->diffForHumans();
    echo "                </td>\n            </tr>\n        ";
}
echo "    </tbody>\n    ";
if (0 < count($tickets)) {
    echo "        <tfoot>\n        <tr>\n            <td colspan=\"7\">\n                <div class=\"row\">\n                    <div class=\"col-md-2\">\n                        <a href=\"#\" id=\"ticketsMerge\" class=\"btn btn-xs btn-default\">";
    echo AdminLang::trans("clientsummary.merge");
    echo "</a>\n                        <a href=\"#\" id=\"ticketsClose\" class=\"btn btn-xs btn-default\">";
    echo AdminLang::trans("global.close");
    echo "</a>\n                        <a href=\"#\" id=\"ticketsDelete\" class=\"btn btn-xs btn-danger\">";
    echo AdminLang::trans("global.delete");
    echo "</a>\n                    </div>\n                    <div id=\"withSelectedSpinner\" class=\"text-center col-md-2 col-md-offset-3\">\n                        <i class=\"fas fa-spinner fa-spin fa-fw saveSpinner\"></i>\n                        ";
    echo AdminLang::trans("global.loading");
    echo "                    </div>\n                </div>\n            </td>\n        </tr>\n        </tfoot>\n    ";
}
echo "</table>\n\n<!--suppress JSUnusedLocalSymbols -->\n<script type=\"text/javascript\">\n    var missingSelections = {\n            title: '";
echo AdminLang::trans("global.error");
echo "',\n            text: '";
echo AdminLang::trans("global.pleaseSelectForMassAction");
echo "',\n            type: 'error',\n            confirmButtonText: '";
echo AdminLang::trans("global.ok");
echo "'\n        },\n        mergeError = {\n            title: '";
echo AdminLang::trans("global.error");
echo "',\n            text: '";
echo AdminLang::trans("support.mergeticketsfailed");
echo "',\n            type: 'error',\n            confirmButtonText: '";
echo AdminLang::trans("global.ok");
echo "'\n        },\n        ticketsClose = {\n            title: '";
echo AdminLang::trans("global.areYouSure");
echo "',\n            text: '";
echo AdminLang::trans("support.masscloseconfirm");
echo "',\n            type: 'info',\n            confirmButtonText: '";
echo AdminLang::trans("global.yes");
echo "',\n            cancelButtonText: '";
echo AdminLang::trans("global.no");
echo "',\n            url: '";
echo routePath("admin-client-tickets-close", $userId);
echo "'\n        },\n        ticketsDelete = {\n            title: '";
echo AdminLang::trans("global.areYouSure");
echo "',\n            text: '";
echo AdminLang::trans("support.massdeleteconfirm");
echo "',\n            type: 'warning',\n            confirmButtonText: '";
echo AdminLang::trans("global.yes");
echo "',\n            cancelButtonText: '";
echo AdminLang::trans("global.no");
echo "',\n            url: '";
echo routePath("admin-client-tickets-delete", $userId);
echo "'\n        },\n        ticketsMerge = {\n            title: '";
echo AdminLang::trans("global.areYouSure");
echo "',\n            text: '";
echo AdminLang::trans("support.massmergeconfirm");
echo "',\n            type: 'info',\n            confirmButtonText: '";
echo AdminLang::trans("global.yes");
echo "',\n            cancelButtonText: '";
echo AdminLang::trans("global.no");
echo "',\n            url: '";
echo routePath("admin-client-tickets-merge", $userId);
echo "'\n        }\n</script>\n";

?>