<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class DomainTransferSync extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 2100;
    protected $defaultFrequency = 240;
    protected $defaultDescription = "Syncing Domain Pending Transfer Status";
    protected $defaultName = "Domain Transfer Status Synchronisation";
    protected $systemName = "DomainTransferSync";
    protected $outputs = array("synced" => array("defaultValue" => 0, "identifier" => "synced", "name" => "Synced"));
    protected $icon = "fas fa-exchange-alt";
    protected $successCountIdentifier = "synced";
    protected $successKeyword = "Transfers Checked";
    protected $skipDailyCron = true;
    public function __invoke()
    {
        if (!\WHMCS\Config\Setting::getValue("DomainSyncEnabled")) {
            logActivity("Domain Transfer Status Cron: Disabled. Run Aborted.");
        } else {
            $syncCount = 0;
            try {
                $cronreport = "Domain Transfer Status Checks for " . date("d-m-Y H:i:s") . "<br />\n<br />\n";
                $registrarConfiguration = $curlErrorRegistrars = array();
                $transfersreport = "";
                for ($result = select_query("tbldomains", implode(",", array("id", "domain", "registrar", "registrationperiod", "status", "dnsmanagement", "emailforwarding", "idprotection")), "registrar!='' AND status='Pending Transfer'", "id", "ASC"); $data = mysql_fetch_array($result); $syncCount++) {
                    $domainid = $data["id"];
                    $domain = $data["domain"];
                    $registrar = $data["registrar"];
                    $regperiod = $data["registrationperiod"];
                    $expirydate = $data["expirydate"];
                    $status = $data["status"];
                    $domainparts = explode(".", $domain, 2);
                    $params = is_array($registrarConfiguration[$registrar]) ? $registrarConfiguration[$registrar] : $registrarConfiguration[$registrar];
                    $params["domainid"] = $domainid;
                    $params["domain"] = $domain;
                    list($params["sld"], $params["tld"]) = $domainparts;
                    $params["registrar"] = $registrar;
                    $params["regperiod"] = $regperiod;
                    $params["status"] = $status;
                    $params["dnsmanagement"] = $data["dnsmanagement"];
                    $params["emailforwarding"] = $data["emailforwarding"];
                    $params["idprotection"] = $data["idprotection"];
                    loadRegistrarModule($registrar);
                    if (function_exists($registrar . "_TransferSync") && !in_array($registrar, $curlErrorRegistrars)) {
                        $transfersreport .= " - " . $domain . ": ";
                        $updateqry = array();
                        $response = call_user_func($registrar . "_TransferSync", $params);
                        if (!$response["error"]) {
                            if ($response["active"] || $response["completed"]) {
                                $transfersreport .= "Transfer Completed";
                                $updateqry["status"] = "Active";
                                if (!$response["expirydate"] && function_exists($registrar . "_Sync") && !in_array($registrar, $curlErrorRegistrars)) {
                                    $response = call_user_func($registrar . "_Sync", $params);
                                }
                                if ($response["expirydate"]) {
                                    $updateqry["expirydate"] = $response["expirydate"];
                                    $updateqry["reminders"] = "";
                                    $expirydate = $updateqry["expirydate"];
                                    $transfersreport .= " - In Sync";
                                }
                                if (\WHMCS\Config\Setting::getValue("DomainSyncNextDueDate") && $response["expirydate"]) {
                                    $newexpirydate = $response["expirydate"];
                                    $expirydate = $updateqry["expirydate"];
                                    if ($syncDueDateDays = \WHMCS\Config\Setting::getValue("DomainSyncNextDueDateDays")) {
                                        $newexpirydate = explode("-", $newexpirydate);
                                        $newexpirydate = date("Y-m-d", mktime(0, 0, 0, $newexpirydate[1], $newexpirydate[2] - $syncDueDateDays, $newexpirydate[0]));
                                    }
                                    $updateqry["nextduedate"] = $newexpirydate;
                                    $updateqry["nextinvoicedate"] = $newexpirydate;
                                }
                            } else {
                                if ($response["failed"]) {
                                    $transfersreport .= "Transfer Failed";
                                    $updateqry["status"] = "Cancelled";
                                    $failurereason = $response["reason"];
                                    if (!$failurereason) {
                                        $failurereason = \Lang::trans("domaintrffailreasonunavailable");
                                    }
                                    sendMessage("Domain Transfer Failed", $domainid, array("domain_transfer_failure_reason" => $failurereason));
                                } else {
                                    $transfersreport .= "Transfer Still In Progress";
                                }
                            }
                            if (!\WHMCS\Config\Setting::getValue("DomainSyncNotifyOnly") && count($updateqry)) {
                                update_query("tbldomains", $updateqry, array("id" => $domainid));
                                if ($updateqry["status"] == "Active") {
                                    sendMessage("Domain Transfer Completed", $domainid);
                                    run_hook("DomainTransferCompleted", array("domainId" => $domainid, "domain" => $domain, "registrationPeriod" => $regperiod, "expiryDate" => $expirydate, "registrar" => $registrar));
                                } else {
                                    if ($updateqry["status"] == "Cancelled") {
                                        run_hook("DomainTransferFailed", array("domainId" => $domainid, "domain" => $domain, "registrationPeriod" => $regperiod, "expiryDate" => $expirydate, "registrar" => $registrar));
                                    }
                                }
                            }
                        } else {
                            if ($response["error"] && strtolower(substr($response["error"], 0, 4)) == "curl") {
                                if (!in_array($registrar, $curlErrorRegistrars)) {
                                    $curlErrorRegistrars[] = $registrar;
                                }
                                $transfersreport .= "Error: " . $response["error"];
                            } else {
                                if ($response["error"]) {
                                    $transfersreport .= "Error: " . $response["error"];
                                }
                            }
                        }
                        $transfersreport .= "<br />\n";
                    }
                }
                if ($transfersreport) {
                    $cronreport .= $transfersreport . "<br />\n";
                    logActivity("Domain Transfer Status Cron: Completed");
                    sendAdminNotification("system", "WHMCS Domain Transfer Status Cron Report", $cronreport);
                }
                $this->output("synced")->write($syncCount);
            } catch (\Exception $e) {
                logActivity("Domain Transfer Status Cron Error: " . $e->getMessage());
                $this->output("synced")->write($syncCount);
            }
        }
        return $this;
    }
    public function getFrequencyMinutes()
    {
        $frequency = (int) \WHMCS\Config\Setting::getValue("DomainTransferStatusCheckFrequency") * 60;
        if (!$frequency || $frequency < 0) {
            $frequency = $this->defaultFrequency;
        }
        return $frequency;
    }
}

?>