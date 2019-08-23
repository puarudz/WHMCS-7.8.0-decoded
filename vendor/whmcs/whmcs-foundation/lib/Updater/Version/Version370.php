<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version370 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "functions.php";
        $query = "SELECT id,password FROM tblclients";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            list($id, $value) = $row;
            $value = $this->decryptOldPassword($value);
            $value = encrypt($value);
            $query2 = "UPDATE tblclients SET password='" . $value . "' WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        $query = "SELECT id,password FROM tblhosting";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            list($id, $value) = $row;
            $value = $this->decryptOldPassword($value);
            $value = encrypt($value);
            $query2 = "UPDATE tblhosting SET password='" . $value . "' WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        $query = "SELECT id,value FROM tblregistrars";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            list($id, $value) = $row;
            $value = $this->decryptOldPassword($value);
            $value = encrypt($value);
            $query2 = "UPDATE tblregistrars SET value='" . $value . "' WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        $query = "SELECT id,password FROM tblservers";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            list($id, $value) = $row;
            $value = $this->decryptOldPassword($value);
            $value = encrypt($value);
            $query2 = "UPDATE tblservers SET password='" . $value . "' WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        $general_email_merge_fields = array();
        $general_email_merge_fields["CustomerID"] = "client_id";
        $general_email_merge_fields["CustomerName"] = "client_name";
        $general_email_merge_fields["CustomerFirstName"] = "client_first_name";
        $general_email_merge_fields["CustomerLastName"] = "client_last_name";
        $general_email_merge_fields["CompanyName"] = "client_company_name";
        $general_email_merge_fields["CustomerEmail"] = "client_email";
        $general_email_merge_fields["Address1"] = "client_address1";
        $general_email_merge_fields["Address2"] = "client_address2";
        $general_email_merge_fields["City"] = "client_city";
        $general_email_merge_fields["State"] = "client_state";
        $general_email_merge_fields["Postcode"] = "client_postcode";
        $general_email_merge_fields["Country"] = "client_country";
        $general_email_merge_fields["PhoneNumber"] = "client_phonenumber";
        $general_email_merge_fields["MAPassword"] = "client_password";
        $general_email_merge_fields["CAPassword"] = "client_password";
        $general_email_merge_fields["CreditBalance"] = "client_credit";
        $general_email_merge_fields["CCType"] = "client_cc_type";
        $general_email_merge_fields["CCLastFour"] = "client_cc_number";
        $general_email_merge_fields["CCExpiryDate"] = "client_cc_expiry";
        $general_email_merge_fields["SystemCompanyName"] = "company_name";
        $general_email_merge_fields["ClientAreaLink"] = "whmcs_url";
        $general_email_merge_fields["Signature"] = "signature";
        $general_email_merge_fields["http://smartftp.com"] = "http://www.filezilla-project.org/";
        $general_email_merge_fields["smart ftp"] = "FileZilla";
        $email_merge_fields = array();
        $email_merge_fields["InvoiceID"] = "invoice_id";
        $email_merge_fields["InvoiceNo"] = "invoice_num";
        $email_merge_fields["InvoiceNum"] = "invoice_num";
        $email_merge_fields["InvoiceDate"] = "invoice_date_created";
        $email_merge_fields["DueDate"] = "invoice_date_due";
        $email_merge_fields["DatePaid"] = "invoice_date_paid";
        $email_merge_fields["Description"] = "invoice_html_contents";
        $email_merge_fields["SubTotal"] = "invoice_subtotal";
        $email_merge_fields["Credit"] = "invoice_credit";
        $email_merge_fields["Tax"] = "invoice_tax";
        $email_merge_fields["TaxRate"] = "invoice_tax_rate";
        $email_merge_fields["Total"] = "invoice_total";
        $email_merge_fields["AmountDue"] = "invoice_total";
        $email_merge_fields["AmountPaid"] = "invoice_amount_paid";
        $email_merge_fields["Balance"] = "invoice_balance";
        $email_merge_fields["LastPaymentAmount"] = "invoice_last_payment_amount";
        $email_merge_fields["Status"] = "invoice_status";
        $email_merge_fields["TransactionID"] = "invoice_last_payment_transid";
        $email_merge_fields["PayButton"] = "invoice_payment_link";
        $email_merge_fields["PaymentMethod"] = "invoice_payment_method";
        $email_merge_fields["InvoiceLink"] = "invoice_link";
        $email_merge_fields["PreviousBalance"] = "invoice_previous_balance";
        $email_merge_fields["AllDueInvoices"] = "invoice_all_due_total";
        $email_merge_fields["TotalBalanceDue"] = "invoice_total_balance_due";
        $query = "SELECT * FROM tblemailtemplates WHERE type='invoice'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        $email_merge_fields = array();
        $email_merge_fields["OrderID"] = "domain_order_id";
        $email_merge_fields["RegDate"] = "domain_reg_date";
        $email_merge_fields["Status"] = "domain_status";
        $email_merge_fields["Domain"] = "domain_name";
        $email_merge_fields["Amount"] = "domain_first_payment_amount";
        $email_merge_fields["FirstPaymentAmount"] = "domain_first_payment_amount";
        $email_merge_fields["RecurringAmount"] = "domain_recurring_amount";
        $email_merge_fields["Registrar"] = "domain_registrar";
        $email_merge_fields["RegPeriod"] = "domain_reg_period";
        $email_merge_fields["ExpiryDate"] = "domain_expiry_date";
        $email_merge_fields["NextDueDate"] = "domain_next_due_date";
        $email_merge_fields["DaysUntilExpiry"] = "domain_days_until_expiry";
        $query = "SELECT * FROM tblemailtemplates WHERE type='domain'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        $email_merge_fields = array();
        $email_merge_fields["Name"] = "client_name";
        $email_merge_fields["TicketID"] = "ticket_id";
        $email_merge_fields["Department"] = "ticket_department";
        $email_merge_fields["DateOpened"] = "ticket_date_opened";
        $email_merge_fields["Subject"] = "ticket_subject";
        $email_merge_fields["Message"] = "ticket_message";
        $email_merge_fields["Status"] = "ticket_status";
        $email_merge_fields["Priority"] = "ticket_priority";
        $email_merge_fields["TicketURL"] = "ticket_url";
        $email_merge_fields["TicketLink"] = "ticket_link";
        $email_merge_fields["AutoCloseTime"] = "ticket_auto_close_time";
        $query = "SELECT * FROM tblemailtemplates WHERE type='support'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        $email_merge_fields = array();
        $email_merge_fields["OrderID"] = "service_order_id";
        $email_merge_fields["ProductID"] = "service_id";
        $email_merge_fields["RegDate"] = "service_reg_date";
        $email_merge_fields["Domain"] = "service_domain";
        $email_merge_fields["domain"] = "service_domain";
        $email_merge_fields["ServerName"] = "service_server_name";
        $email_merge_fields["ServerIP"] = "service_server_ip";
        $email_merge_fields["serverip"] = "service_server_ip";
        $email_merge_fields["DedicatedIP"] = "service_dedicated_ip";
        $email_merge_fields["AssignedIPs"] = "service_assigned_ips";
        $email_merge_fields["Nameserver1"] = "service_ns1";
        $email_merge_fields["Nameserver2"] = "service_ns2";
        $email_merge_fields["Nameserver3"] = "service_ns3";
        $email_merge_fields["Nameserver4"] = "service_ns4";
        $email_merge_fields["Nameserver1IP"] = "service_ns1_ip";
        $email_merge_fields["Nameserver2IP"] = "service_ns2_ip";
        $email_merge_fields["Nameserver3IP"] = "service_ns3_ip";
        $email_merge_fields["Nameserver4IP"] = "service_ns4_ip";
        $email_merge_fields["Product"] = "service_product_name";
        $email_merge_fields["Package"] = "service_product_name";
        $email_merge_fields["ConfigOptions"] = "service_config_options_html";
        $email_merge_fields["PaymentMethod"] = "service_payment_method";
        $email_merge_fields["Amount"] = "service_recurring_amount";
        $email_merge_fields["FirstPaymentAmount"] = "service_first_payment_amount";
        $email_merge_fields["RecurringAmount"] = "service_recurring_amount";
        $email_merge_fields["BillingCycle"] = "service_billing_cycle";
        $email_merge_fields["NextDueDate"] = "service_next_due_date";
        $email_merge_fields["Status"] = "service_status";
        $email_merge_fields["Username"] = "service_username";
        $email_merge_fields["Password"] = "service_password";
        $email_merge_fields["CpanelUsername"] = "service_username";
        $email_merge_fields["CpanelPassword"] = "service_password";
        $email_merge_fields["RootUsername"] = "service_username";
        $email_merge_fields["RootPassword"] = "service_password";
        $email_merge_fields["OrderNumber"] = "order_number";
        $email_merge_fields["OrderDetails"] = "order_details";
        $email_merge_fields["SSLConfigurationLink"] = "ssl_configuration_link";
        $query = "SELECT * FROM tblemailtemplates WHERE type='product'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        $email_merge_fields = array();
        $email_merge_fields["TotalVisitors"] = "affiliate_total_visits";
        $email_merge_fields["CurrentBalance"] = "affiliate_balance";
        $email_merge_fields["AmountWithdrawn"] = "affiliate_withdrawn";
        $email_merge_fields["ReferralsTable"] = "affiliate_referrals_table";
        $email_merge_fields["ReferralLink"] = "affiliate_referral_url";
        $query = "SELECT * FROM tblemailtemplates WHERE type='affiliate'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        $query = "SELECT * FROM tblemailtemplates WHERE type='general'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $email_id = $data["id"];
            $email_subject = $data["subject"];
            $email_message = $data["message"];
            foreach ($general_email_merge_fields as $old_email_merge_fields => $new_email_merge_fields) {
                $email_subject = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_subject);
                $email_message = str_replace("[" . $old_email_merge_fields . "]", "{\$" . $new_email_merge_fields . "}", $email_message);
            }
            $query = "UPDATE tblemailtemplates SET subject='" . mysql_real_escape_string($email_subject) . "',message='" . mysql_real_escape_string($email_message) . "' WHERE id='" . $email_id . "'";
            $result2 = mysql_query($query);
        }
        return $this;
    }
    protected function decryptOldPassword($string)
    {
        $key = "5a8ej8WndK\$3#9Ua425!hg741KknN";
        $result = "";
        $string = base64_decode($string);
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, $i % strlen($key) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        unset($key);
        return $result;
    }
}

?>