<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail\Entity;

class Domain extends \WHMCS\Mail\Emailer
{
    protected function getEntitySpecificMergeData($domainId)
    {
        $result = select_query("tbldomains", "", array("id" => $domainId));
        $data = mysql_fetch_array($result);
        $id = $data["id"];
        if (!$id) {
            throw new \WHMCS\Exception("Invalid domain id provided");
        }
        $userid = $data["userid"];
        $orderid = $data["orderid"];
        $registrationdate = $data["registrationdate"];
        $status = $data["status"];
        $domain = $data["domain"];
        $firstpaymentamount = $data["firstpaymentamount"];
        $recurringamount = $data["recurringamount"];
        $registrar = $data["registrar"];
        $registrationperiod = $data["registrationperiod"];
        $expirydate = $data["expirydate"];
        $nextduedate = $data["nextduedate"];
        $gateway = $data["paymentmethod"];
        $dnsmanagement = $data["dnsmanagement"];
        $emailforwarding = $data["emailforwarding"];
        $idprotection = $data["idprotection"];
        $donotrenew = $data["donotrenew"];
        $this->setRecipient($userid);
        $status = \Lang::trans("clientarea" . strtolower(str_replace(" ", "", $status)));
        if ($expirydate == "0000-00-00" || empty($expirydate)) {
            $expirydate = $nextduedate;
        }
        $expirydays_todaysdate = date("Ymd");
        $expirydays_todaysdate = strtotime($expirydays_todaysdate);
        $expirydays_expirydate = strtotime($expirydate);
        $expirydays = round(($expirydays_expirydate - $expirydays_todaysdate) / 86400);
        $expirydays_nextduedate = strtotime($nextduedate);
        $nextduedays = round(($expirydays_nextduedate - $expirydays_todaysdate) / 86400);
        $registrationdate = fromMySQLDate($registrationdate, 0, 1);
        $expirydate = fromMySQLDate($expirydate, 0, 1);
        $nextduedate = fromMySQLDate($nextduedate, 0, 1);
        $domainparts = explode(".", $domain, 2);
        $email_merge_fields = array();
        $email_merge_fields["domain_id"] = $id;
        $email_merge_fields["domain_order_id"] = $orderid;
        $email_merge_fields["domain_reg_date"] = $registrationdate;
        $email_merge_fields["domain_status"] = $status;
        $email_merge_fields["domain_name"] = $domain;
        list($email_merge_fields["domain_sld"], $email_merge_fields["domain_tld"]) = $domainparts;
        $email_merge_fields["domain_first_payment_amount"] = formatCurrency($firstpaymentamount);
        $email_merge_fields["domain_recurring_amount"] = formatCurrency($recurringamount);
        $email_merge_fields["domain_registrar"] = $registrar;
        $email_merge_fields["domain_reg_period"] = $registrationperiod . " " . \Lang::trans("orderyears");
        $email_merge_fields["domain_expiry_date"] = $expirydate;
        $email_merge_fields["domain_next_due_date"] = $nextduedate;
        $email_merge_fields["domain_renewal_url"] = fqdnRoutePath("domain-renewal", $domain);
        $email_merge_fields["domains_manage_url"] = \App::getSystemUrl() . "clientarea.php?action=domains";
        if (0 <= $expirydays) {
            $email_merge_fields["days_until_expiry"] = $expirydays;
            $email_merge_fields["domain_days_until_expiry"] = $expirydays;
            $email_merge_fields["domain_days_after_expiry"] = 0;
        } else {
            $email_merge_fields["days_until_expiry"] = 0;
            $email_merge_fields["domain_days_until_expiry"] = 0;
            $email_merge_fields["domain_days_after_expiry"] = $expirydays * -1;
        }
        if (0 <= $nextduedays) {
            $email_merge_fields["domain_days_until_nextdue"] = $nextduedays;
            $email_merge_fields["domain_days_after_nextdue"] = 0;
        } else {
            $email_merge_fields["domain_days_until_nextdue"] = 0;
            $email_merge_fields["domain_days_after_nextdue"] = $nextduedays * -1;
        }
        $email_merge_fields["domain_dns_management"] = $dnsmanagement ? "1" : "0";
        $email_merge_fields["domain_email_forwarding"] = $emailforwarding ? "1" : "0";
        $email_merge_fields["domain_id_protection"] = $idprotection ? "1" : "0";
        $email_merge_fields["domain_do_not_renew"] = $donotrenew ? "1" : "0";
        $email_merge_fields["expiring_domains"] = array();
        $email_merge_fields["domains"] = array();
        $this->massAssign($email_merge_fields);
        if (in_array($this->message->getTemplateName(), array("Upcoming Domain Renewal Notice", "Domain Expiry Notice"))) {
            $clientEmail = get_query_val("tblclients", "email", array("id" => $userid));
            if ($this->getExtra("registrantEmail") && $this->getExtra("registrantEmail") != $clientEmail) {
                $this->message->addRecipient("cc", $this->getExtra("registrantEmail"));
            }
        }
    }
}

?>