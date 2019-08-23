<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class DomainStatusSync extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 2150;
    protected $defaultFrequency = 240;
    protected $defaultDescription = "Domain Status Syncing";
    protected $defaultName = "Domain Status Synchronisation";
    protected $systemName = "DomainStatusSync";
    protected $outputs = array("synced" => array("defaultValue" => 0, "identifier" => "synced", "name" => "Synced"));
    protected $icon = "fas fa-history";
    protected $successCountIdentifier = "synced";
    protected $successKeyword = "Domains Synced";
    protected $skipDailyCron = true;
    public function __invoke()
    {
        if (!\WHMCS\Config\Setting::getValue("DomainSyncEnabled")) {
            logActivity("Domain Sync Cron: Disabled. Run Aborted.");
        } else {
            $syncCount = 0;
            try {
                $cronreport = "Domain Synchronisation Cron Report for " . date("d-m-Y H:i:s") . "<br />\n<br />\n";
                $registrarConfiguration = $curlErrorRegistrars = array();
                $cronreport .= "Active Domain Syncs<br />\n";
                $totalunsynced = get_query_val("tbldomains", "COUNT(id)", "registrar!='' AND status='Active' AND synced=0");
                if (!$totalunsynced) {
                    update_query("tbldomains", array("synced" => "0"), "");
                }
                $result = select_query("tbldomains", "id,domain,expirydate,nextduedate,registrar,status", "registrar!='' AND status='Active' AND synced=0", "status` DESC, `id", "ASC", "0,50");
                while ($data = mysql_fetch_array($result)) {
                    $domainid = $data["id"];
                    $domain = $data["domain"];
                    $registrar = $data["registrar"];
                    $expirydate = $data["expirydate"];
                    $nextduedate = $data["nextduedate"];
                    $status = $data["status"];
                    $domainparts = explode(".", $domain, 2);
                    $params = is_array($registrarConfiguration[$registrar]) ? $registrarConfiguration[$registrar] : $registrarConfiguration[$registrar];
                    $params["domainid"] = $domainid;
                    $params["domain"] = $domain;
                    list($params["sld"], $params["tld"]) = $domainparts;
                    $params["registrar"] = $registrar;
                    $params["status"] = $status;
                    loadRegistrarModule($registrar);
                    $updateqry = array();
                    $updateqry["synced"] = "1";
                    $response = $synceditems = array();
                    if (function_exists($registrar . "_Sync") && !in_array($registrar, $curlErrorRegistrars)) {
                        $response = call_user_func($registrar . "_Sync", $params);
                        if (!$response["error"]) {
                            if ($response["active"] && $status != "Active") {
                                $updateqry["status"] = "Active";
                                $synceditems[] = "Status Changed to Active";
                            }
                            if ($response["cancelled"] && $status == "Active") {
                                $updateqry["status"] = "Cancelled";
                                $synceditems[] = "Status Changed to Cancelled";
                            }
                            if ($response["expirydate"] && $expirydate != $response["expirydate"]) {
                                $updateqry["expirydate"] = $response["expirydate"];
                                $updateqry["reminders"] = "";
                                $synceditems[] = "Expiry Date updated to " . fromMySQLDate($response["expirydate"]);
                            }
                            if (array_key_exists("transferredAway", $response) && $response["transferredAway"] == true && $status != "Transferred Away") {
                                $updateqry["status"] = "Transferred Away";
                                $synceditems[] = "Status Changed to Transferred Away";
                            }
                            if (\WHMCS\Config\Setting::getValue("DomainSyncNextDueDate") && $response["expirydate"]) {
                                $newexpirydate = $response["expirydate"];
                                if ($syncDueDateDays = \WHMCS\Config\Setting::getValue("DomainSyncNextDueDateDays")) {
                                    $newexpirydate = explode("-", $newexpirydate);
                                    $newexpirydate = date("Y-m-d", mktime(0, 0, 0, $newexpirydate[1], $newexpirydate[2] - $syncDueDateDays, $newexpirydate[0]));
                                }
                                if ($newexpirydate != $nextduedate) {
                                    $updateqry["nextduedate"] = $newexpirydate;
                                    $updateqry["nextinvoicedate"] = $newexpirydate;
                                    $synceditems[] = "Next Due Date updated to " . fromMySQLDate($newexpirydate);
                                }
                            }
                        }
                    }
                    if (\WHMCS\Config\Setting::getValue("DomainSyncNotifyOnly")) {
                        $updateqry = array("synced" => "1");
                    }
                    update_query("tbldomains", $updateqry, array("id" => $domainid));
                    $syncCount++;
                    $cronreport .= " - " . $domain . ": ";
                    if (!count($response)) {
                        if (in_array($registrar, $curlErrorRegistrars)) {
                            $cronreport .= "Sync Skipped Due to cURL Error";
                        } else {
                            $cronreport .= "Sync Not Supported by Registrar Module";
                        }
                    } else {
                        if ($response["error"] && strtolower(substr($response["error"], 0, 4)) == "curl") {
                            if (!in_array($registrar, $curlErrorRegistrars)) {
                                $curlErrorRegistrars[] = $registrar;
                            }
                            $cronreport .= "Error: " . $response["error"];
                        } else {
                            if ($response["error"]) {
                                $cronreport .= "Error: " . $response["error"];
                            } else {
                                if (!function_exists($registrar . "_TransfersSync") && $status == "Pending Transfer" && $response["active"]) {
                                    sendMessage("Domain Transfer Completed", $domainid);
                                }
                                $suffix = "In Sync";
                                if (count($synceditems) && \WHMCS\Config\Setting::getValue("DomainSyncNotifyOnly")) {
                                    $suffix = "Out of Sync " . implode(", ", $synceditems);
                                } else {
                                    if (count($synceditems)) {
                                        $suffix = implode(", ", $synceditems);
                                    }
                                }
                                $cronreport .= $suffix;
                            }
                        }
                    }
                    $cronreport .= "<br />\n";
                }
                logActivity("Domain Sync Cron: Completed");
                $this->output("synced")->write($syncCount);
                sendAdminNotification("system", "WHMCS Domain Synchronisation Cron Report", $cronreport);
            } catch (\Exception $e) {
                logActivity("Domain Sync Cron Error: " . $e->getMessage());
                $this->output("synced")->write($syncCount);
            }
        }
        return $this;
    }
    public function getFrequencyMinutes()
    {
        $frequency = (int) \WHMCS\Config\Setting::getValue("DomainStatusSyncFrequency") * 60;
        if (!$frequency || $frequency < 0) {
            $frequency = $this->defaultFrequency;
        }
        return $frequency;
    }
}

?>