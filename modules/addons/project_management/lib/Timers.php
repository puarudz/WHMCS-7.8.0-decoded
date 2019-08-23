<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Timers extends BaseProjectEntity
{
    public function get($timerId = NULL)
    {
        $where = array("projectid" => $this->project->id);
        if ($timerId) {
            $where["id"] = $timerId;
        }
        $tasks = array();
        foreach ($this->project->tasks()->listall() as $task) {
            $tasks[$task["id"]] = $task["task"];
        }
        $adminNames = Helper::getAdmins();
        $taskTimes = $this->getTaskTimes();
        $timers = array();
        $result = select_query("mod_projecttimes", "", $where, "start", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $endTime = "-";
            $duration = 0;
            $totalTaskTime = "00:00";
            $endDate = $endDateTime = "";
            if ($data["end"]) {
                $endDate = \WHMCS\Carbon::createFromTimestampUTC($data["end"])->toAdminDateFormat();
                $endDateTime = \WHMCS\Carbon::createFromTimestampUTC($data["end"])->toAdminDateTimeFormat();
                $endTime = \WHMCS\Carbon::createFromTimestampUTC($data["end"])->format("g:ia");
                $duration = Helper::timeToHuman($data["end"] - $data["start"]);
            }
            if ($taskTimes[$data["taskid"]]) {
                $totalTaskTime = $this->formatTimerSecondsToReadableTime($taskTimes[$data["taskid"]]);
            }
            $timers[] = array("id" => $data["id"], "taskId" => $data["taskid"], "taskName" => isset($tasks[$data["taskid"]]) ? $tasks[$data["taskid"]] : "Unassigned", "adminId" => $data["adminid"], "adminName" => $adminNames[$data["adminid"]] ?: "", "date" => \WHMCS\Carbon::createFromTimestampUTC($data["start"])->toAdminDateFormat(), "dateTime" => \WHMCS\Carbon::createFromTimestampUTC($data["start"])->toAdminDateTimeFormat(), "startTimestamp" => $data["start"], "startTime" => \WHMCS\Carbon::createFromTimestampUTC($data["start"])->format("g:ia"), "endDate" => $endDate, "endDateTime" => $endDateTime, "endTimestamp" => $data["end"], "endTime" => $endTime, "duration" => $duration, "billed" => (int) $data["donotbill"], "totalTaskTime" => $totalTaskTime);
        }
        return $timers;
    }
    public function getSingle($timerId = NULL)
    {
        if (is_null($timerId)) {
            $timerId = \App::getFromRequest("timerid");
        }
        return array("timer" => $this->get($timerId));
    }
    public function getOpenTimerId()
    {
        return get_query_val("mod_projecttimes", "id", array("end" => "", "projectid" => $this->project->id, "adminid" => Helper::getCurrentAdminId()));
    }
    protected function endExistingTimers($taskId = 0)
    {
        $activetimers = select_query("mod_projecttimes", "id", array("end" => "", "projectid" => $this->project->id, "taskid" => $taskId, "adminid" => Helper::getCurrentAdminId()));
        while ($activetimersdata = mysql_fetch_assoc($activetimers)) {
            update_query("mod_projecttimes", array("end" => time()), array("id" => $activetimersdata["id"]));
        }
    }
    public function start()
    {
        $taskId = (int) \App::getFromRequest("taskid");
        $this->endExistingTimers($taskId);
        $newTimerId = insert_query("mod_projecttimes", array("projectid" => $this->project->id, "taskid" => $taskId, "start" => time(), "adminid" => Helper::getCurrentAdminId()));
        $this->project->log()->add("Timer Started: " . ($taskId ? get_query_val("mod_projecttasks", "task", array("projectid" => $this->project->id, "id" => $taskId)) : "Unassigned Task"));
        return array("newTimerId" => $newTimerId, "newTimer" => $this->get($newTimerId));
    }
    public function end()
    {
        $timerId = (int) \App::getFromRequest("timerid");
        if (!$timerId) {
            throw new Exception("Timer ID is required");
        }
        $timerData = \WHMCS\Database\Capsule::table("mod_projecttimes")->find($timerId);
        if (!$timerData) {
            throw new Exception("Invalid Timer ID");
        }
        $end = time();
        if ($end - $timerData->start < 60) {
            $end = $timerData->start + 60;
        }
        update_query("mod_projecttimes", array("end" => $end), array("id" => $timerId));
        $this->project->log()->add("Timer Ended");
        $timer = $this->getSingle($timerId);
        return array_merge(array("endedTimerId" => $timerId, "timer" => $timer["timer"]), $this->getStats());
    }
    public function getStats()
    {
        $times = \WHMCS\Database\Capsule::connection()->selectOne("SELECT\n (SELECT COUNT(`id`) FROM `mod_projecttimes` WHERE `projectid` = " . $this->project->id . ") AS counter,\n (SELECT IFNULL(SUM(`end` - `start`), 0) FROM `mod_projecttimes` WHERE `projectid` = " . $this->project->id . " AND end > 0) AS total,\n (SELECT IFNULL(SUM(`end` - `start`), 0) FROM `mod_projecttimes` WHERE `projectid` = " . $this->project->id . " AND end > 0 AND `donotbill` = 1) AS billed");
        return array("totalCount" => $times->counter, "totalTime" => $times->total == 0 ? "N/A" : Helper::timeToHuman($times->total) . " (" . number_format(round($times->total / 3600, 2), 2) . ")", "totalBilled" => $times->billed == 0 ? "N/A" : Helper::timeToHuman($times->billed) . " (" . number_format(round($times->billed / 3600, 2), 2) . ")");
    }
    public function update()
    {
        check_token("WHMCS.admin.default");
        $project = $this->project;
        $timerId = (int) \App::getFromRequest("timerId");
        if (!$timerId) {
            throw new Exception("Timer ID is required");
        }
        $timer = $this->getSingle($timerId);
        if (!$timer) {
            throw new Exception("Invalid Timer ID");
        }
        $taskId = (int) \App::getFromRequest("taskId");
        if ($taskId) {
            $task = $project->tasks()->getSingle($taskId);
            if (!$task) {
                throw new Exception("Invalid Task Selected");
            }
        }
        $adminId = (int) \App::getFromRequest("adminId");
        $startDate = \App::getFromRequest("start");
        if (!$startDate) {
            $startDate = fromMySQLDate(\WHMCS\Carbon::now()->toDateString());
        }
        $startDate = \WHMCS\Carbon::createFromAdminDateTimeFormat($startDate);
        $startDate = strtotime($startDate->toDateTimeString());
        $endDate = \App::getFromRequest("end");
        if ($endDate) {
            $endDate = \WHMCS\Carbon::createFromAdminDateTimeFormat($endDate);
            $endDate = strtotime($endDate->toDateTimeString());
        } else {
            $endDate = 0;
        }
        \WHMCS\Database\Capsule::table("mod_projecttimes")->where("id", $timerId)->where("projectid", $project->id)->update(array("adminid" => $adminId, "taskid" => $taskId, "start" => $startDate, "end" => $endDate));
        return $this->getSingle($timerId);
    }
    public function invoiceItems()
    {
        check_token("WHMCS.admin.default");
        if (!$this->project->permissions()->check("Bill Tasks")) {
            throw new Exception("Access Denied");
        }
        $timerIds = \App::getFromRequest("timerId");
        $descriptions = \App::getFromRequest("description");
        $hours = \App::getFromRequest("hours");
        $displayHours = \App::getFromRequest("displayHours");
        $rate = \App::getFromRequest("rate");
        if (!$timerIds) {
            throw new Exception("Invalid Timers Selected");
        }
        $project = $this->project;
        if (!$project->userid) {
            throw new Exception("User Required to Generate Invoice");
        }
        if (!function_exists("getClientsPaymentMethod")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
        }
        $gateway = getClientsPaymentMethod($project->userid);
        if (!$gateway) {
            throw new Exception("There are no active Payment Gateways. Please enable a Payment Gateway and try again");
        }
        $tasks = array();
        foreach ($project->tasks()->listall() as $task) {
            $tasks[$task["id"]] = $task["task"];
        }
        $timers = \WHMCS\Database\Capsule::table("mod_projecttimes")->where("projectid", $project->id)->where("donotbill", 0)->whereIn("id", $timerIds);
        if ($timers->count() != count($timerIds)) {
            throw new Exception("One or more selected timers has already been invoiced");
        }
        $invoice = \WHMCS\Billing\Invoice::newInvoice($project->userid, $gateway);
        $invoice->status = "Unpaid";
        $invoice->save();
        $invoiceId = $invoice->id;
        $taxed = 0;
        if ($invoice->taxRate1 || $invoice->taxRate2) {
            $taxed = 1;
        }
        $invoiceItems = array();
        foreach ($descriptions as $key => $description) {
            $key = (int) $key;
            $description .= " - " . $displayHours[$key];
            $amount = $hours[$key] * $rate[$key];
            $invoiceItems[] = array("invoiceid" => $invoiceId, "userid" => $project->userid, "type" => "Project", "relid" => $project->id, "description" => $description, "paymentmethod" => $gateway, "amount" => round($amount, 2), "taxed" => $taxed);
        }
        \WHMCS\Database\Capsule::table("tblinvoiceitems")->insert($invoiceItems);
        \WHMCS\Database\Capsule::table("mod_projecttimes")->whereIn("id", $timerIds)->update(array("donotbill" => 1));
        updateInvoiceTotal($invoiceId);
        $project->invoiceids[] = $invoiceId;
        $project->save();
        if ($invoiceId && \App::getFromRequest("sendInvoiceCreatedEmail") == "on") {
            sendMessage("Invoice Created", $invoiceId);
        }
        $project->notify()->staff(array("field" => "Invoice Added", "oldValue" => "", "newValue" => $invoiceId));
        $project->log()->add("Created Time Based Invoice - Invoice ID: " . $invoiceId);
        $invoiceArr = array("source" => "adminarea", "user" => \WHMCS\Session::get("adminid"), "invoiceid" => $invoiceId, "status" => "Unpaid");
        run_hook("InvoiceCreation", $invoiceArr);
        run_hook("InvoiceCreationAdminArea", $invoiceArr);
        $invoice = $project->invoices()->getSingleInvoiceById($invoiceId);
        $invoice["total"] = (string) formatCurrency($invoice["total"], $clientsDetails["currency"]);
        $invoice["balance"] = (string) formatCurrency($invoice["balance"], $clientsDetails["currency"]);
        $invoice["dateCreated"] = fromMySQLDate($invoice["date"]);
        $invoice["dateDue"] = fromMySQLDate($invoice["duedate"]);
        $return = $this->getStats();
        $return["timers"] = $this->get();
        $return["invoiceId"] = $invoiceId;
        $return["invoice"] = $invoice;
        $return["invoiceCount"] = count($this->project->invoiceids);
        return $return;
    }
    public function add()
    {
        check_token("WHMCS.admin.default");
        $project = $this->project;
        $taskId = (int) \App::getFromRequest("taskId");
        $adminId = (int) \App::getFromRequest("adminId");
        if ($taskId) {
            $task = $project->tasks()->getSingle($taskId);
            if (!$task) {
                throw new Exception("Invalid Task Selected");
            }
        }
        $startDate = \App::getFromRequest("start");
        if (!$startDate) {
            $startDate = fromMySQLDate(\WHMCS\Carbon::now()->toDateString());
        }
        $startDate = toMySQLDate($startDate);
        $startTime = \App::getFromRequest("startTime");
        $startDate = \WHMCS\Carbon::createFromFormat("Y-m-d g:ia", $startDate . " " . $startTime);
        $startDate = strtotime($startDate->toDateTimeString());
        $endDate = \App::getFromRequest("end");
        $endTime = \App::getFromRequest("endTime");
        if (!$endDate && $endTime) {
            $endDate = fromMySQLDate(\WHMCS\Carbon::now()->toDateString());
        }
        if ($endDate) {
            $endDate = toMySQLDate($endDate);
            $endDate = \WHMCS\Carbon::createFromFormat("Y-m-d g:ia", $endDate . " " . $endTime);
            $endDate = strtotime($endDate->toDateTimeString());
        } else {
            $endDate = 0;
        }
        $timerId = \WHMCS\Database\Capsule::table("mod_projecttimes")->insertGetId(array("projectid" => $project->id, "adminid" => $adminId, "taskid" => $taskId, "start" => $startDate, "end" => $endDate));
        return array_merge($this->getSingle($timerId), $this->getStats());
    }
    public function delete()
    {
        $timerId = \App::getFromRequest("timerId");
        $timer = $this->getSingle($timerId);
        if (0 < !count($timer["timer"])) {
            throw new Exception("Invalid Timer");
        }
        $timer = $timer["timer"][0];
        if ($timer["billed"] == 1) {
            throw new Exception("Unable to remove billed timer");
        }
        \WHMCS\Database\Capsule::table("mod_projecttimes")->delete($timer["id"]);
        $this->project->log()->add("Timer Deleted: " . $timer["id"]);
        $this->project->notify()->staff(array(array("field" => "Timer Deleted", "oldValue" => $timer["id"], "newValue" => "")));
        return array_merge(array("deletedTimerId" => $timerId, "openTimerId" => $this->getOpenTimerId()), $this->getStats());
    }
    public function prepareInvoiceTimers()
    {
        $timerIds = \App::getFromRequest("timerId");
        $rate = \App::getFromRequest("rate");
        $project = $this->project;
        if (!$project->userid) {
            throw new Exception("User Required to Generate Invoice");
        }
        $timers = \WHMCS\Database\Capsule::table("mod_projecttimes")->where("projectid", $project->id)->where("donotbill", 0)->whereIn("id", $timerIds);
        if ($timers->count() != count($timerIds)) {
            throw new Exception("One or more selected timers has already been invoiced");
        }
        $tasks = array();
        foreach ($project->tasks()->listall() as $task) {
            $tasks[$task["id"]] = $task["task"];
        }
        $times = array();
        foreach ($timers->get() as $timer) {
            $times[$timer->taskid]["description"] = isset($tasks[$timer->taskid]) ? $tasks[$timer->taskid] : "Unassigned Task";
            $times[$timer->taskid]["seconds"] += $timer->end - $timer->start;
            $times[$timer->taskid]["hours"] = $this->secondsToHours($times[$timer->taskid]["seconds"]);
            $times[$timer->taskid]["amount"] = round($times[$timer->taskid]["seconds"] / 3600 * $rate, 2);
            $times[$timer->taskid]["rate"] = $rate;
        }
        return array("currency" => $this->project->client->currencyrel->toArray(), "times" => $times);
    }
    public function getTaskTimes()
    {
        return \WHMCS\Database\Capsule::table("mod_projecttimes")->where("end", "!=", "0")->groupBy("taskid")->pluck(\WHMCS\Database\Capsule::raw("SUM(end-start)"), "taskid");
    }
    public function formatTimerSecondsToReadableTime($timer)
    {
        return $this->secondsToHours($timer, true);
    }
    protected function secondsToHours($secs, $padHours = false)
    {
        if ($secs <= 0) {
            $secs = 0;
        }
        $hms = "";
        $hours = intval(intval($secs) / 3600);
        $hms .= $padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":";
        $minutes = intval($secs / 60 % 60);
        $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":";
        $seconds = intval($secs % 60);
        $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
        return $hms;
    }
}

?>