<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Client;

class TicketsController
{
    public function tickets(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!function_exists("getStatusColour")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $userId = (int) $request->getAttribute("userId");
        $aInt = new \WHMCS\Admin("List Support Tickets");
        $aInt->setClientsProfilePresets();
        $aInt->valUserID($userId);
        $aInt->setClientsProfilePresets($userId);
        $aInt->assertClientBoundary($userId);
        $aInt->setResponseType($aInt::RESPONSE_HTML_MESSAGE);
        $aInt->addHeadOutput(\WHMCS\View\Asset::jsInclude("AdminClientTicketTab.js?v=" . \WHMCS\View\Helper::getAssetVersionHash()));
        $csrfToken = generate_token("plain");
        $tickets = \WHMCS\Support\Ticket::userId($userId)->notMerged()->with("department", "flaggedAdmin")->get();
        $endOfLastMonth = \WHMCS\Carbon::now()->subMonth()->lastOfMonth()->endOfDay()->toDateTimeString();
        $endOfTwoMonthsAgo = \WHMCS\Carbon::now()->subMonth(2)->lastOfMonth()->endOfDay()->toDateTimeString();
        $firstOfThisMonth = \WHMCS\Carbon::now()->firstOfMonth()->toDateTimeString();
        $endOfLastYear = \WHMCS\Carbon::now()->subYear(1)->lastOfYear()->endOfDay()->toDateTimeString();
        $endOfTwoYearsAgo = \WHMCS\Carbon::now()->subYear(2)->lastOfYear()->endOfDay()->toDateTimeString();
        $firstOfThisYear = \WHMCS\Carbon::now()->firstOfYear()->toDateTimeString();
        $aInt->content = view("admin.client.profile.tickets", array("csrfToken" => $csrfToken, "tickets" => $tickets, "userId" => $userId, "ticketCounts" => array("thisMonth" => $tickets->filter(function (\WHMCS\Support\Ticket $value) use($endOfLastMonth) {
            return $endOfLastMonth < $value->date;
        })->count(), "lastMonth" => $tickets->filter(function (\WHMCS\Support\Ticket $value) use($endOfTwoMonthsAgo, $firstOfThisMonth) {
            return $endOfTwoMonthsAgo < $value->date && $value->date < $firstOfThisMonth;
        })->count(), "thisYear" => $tickets->filter(function (\WHMCS\Support\Ticket $value) use($endOfLastYear) {
            return $endOfLastYear < $value->date;
        })->count(), "lastYear" => $tickets->filter(function (\WHMCS\Support\Ticket $value) use($endOfTwoYearsAgo, $firstOfThisYear) {
            return $endOfTwoYearsAgo < $value->date && $value->date < $firstOfThisYear;
        })->count())));
        return $aInt->display();
    }
    public function close(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!function_exists("closeTicket")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $ticketIds = $request->request()->get("ticketIds");
        foreach ($ticketIds as $ticketId) {
            try {
                closeTicket($ticketId);
            } catch (\Exception $e) {
                logAdminActivity("Unable to close ticket. Ticket ID: " . $ticketId . " - Error: " . $e->getMessage());
            }
        }
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!function_exists("deleteTicket")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $ticketIds = $request->request()->get("ticketIds");
        foreach ($ticketIds as $ticketId) {
            try {
                deleteTicket($ticketId);
            } catch (\WHMCS\Exception\Fatal $e) {
                logAdminActivity("Unable to delete ticket. Ticket ID: " . $ticketId . " - Error: " . $e->getMessage());
            } catch (\Exception $e) {
            }
        }
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
    public function merge(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!function_exists("addTicketLog")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $ticketIds = $request->request()->get("ticketIds");
        $userId = $request->get("userId");
        sort($ticketIds);
        $mainTicket = $ticketIds[0];
        unset($ticketIds[0]);
        try {
            \WHMCS\Support\Ticket::where("userid", $userId)->where("id", $mainTicket)->firstOrFail()->mergeOtherTicketsInToThis($ticketIds);
        } catch (\Exception $e) {
        }
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
}

?>