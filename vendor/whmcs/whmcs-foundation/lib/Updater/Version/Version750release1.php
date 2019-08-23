<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version750release1 extends IncrementalVersion
{
    protected $updateActions = array("addInvoiceModifiedEmail");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "whoisfunctions.php";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "admin" . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . "adminlangupdate.php";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . "project_management" . DIRECTORY_SEPARATOR . "edittask.php";
    }
    protected function addInvoiceModifiedEmail()
    {
        $existingTemplate = \WHMCS\Mail\Template::where("name", "Invoice Modified")->first();
        if (!$existingTemplate) {
            $newTemplate = new \WHMCS\Mail\Template();
            $newTemplate->name = "Invoice Modified";
            $newTemplate->subject = "Invoice #{\$invoice_num} Updated";
            $newTemplate->type = "invoice";
            $newTemplate->message = "<p>Dear {\$client_name},</p>\n<p>This is a notice that invoice #{\$invoice_num} which was originally generated on {\$invoice_date_created} has been updated.</p>\n<p>Your payment method is: {\$invoice_payment_method}</p>\n<p>\n    Invoice #{\$invoice_num}<br>\n    Amount Due: {\$invoice_balance}<br>\n    Due Date: {\$invoice_date_due}\n</p>\n<p>Invoice Items</p>\n<p>\n    {\$invoice_html_contents}<br>\n    ------------------------------------------------------\n</p>\n<p>You can login to our client area to view and pay the invoice at {\$invoice_link}</p>\n<p>{\$signature}</p>";
            $newTemplate->custom = false;
            $newTemplate->plaintext = false;
            $newTemplate->disabled = false;
            $newTemplate->fromName = "";
            $newTemplate->fromEmail = "";
            $newTemplate->attachments = array();
            $newTemplate->copyTo = array();
            $newTemplate->blindCopyTo = array();
            $newTemplate->language = "";
            $newTemplate->save();
        }
    }
}

?>