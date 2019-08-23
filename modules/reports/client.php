<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$userid = App::getFromRequest('userid');
$onloadUserReplaceJs = '';
if ($userid) {
    $onloadUserReplaceJs = 'jQuery("#selectUserid")[0].selectize.trigger("change");';
}
$reportdata["title"] = "Client Data Export";
$reportdata["description"] = "This report allows you to generate a JSON export of data relating to a given client. You can choose which data points you wish to be included in the export below.";
$reportdata["headertext"] = '
<form method="post" action="' . routePath('admin-client-export', 'xxx') . '" data-route="' . routePath('admin-client-export', 'xxx') . '" id="frmClientExport">
<input type="hidden" name="export" value="true">
<br>
<p>
    Choose the client to export<br>
    ' . $aInt->clientsDropDown($userid) . '
</p>
<div style="background-color:#f8f8f8;margin:10px 0 20px;padding:20px;border-radius:4px;">
    <div class="row">
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="profile" checked>
                Profile Data
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="paymethods">
                Pay Methods
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="contacts">
                Contacts
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="services">
                Products/Services
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="domains">
                Domains
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="billableitems">
                Billable Items
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="invoices">
                Invoices
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="quotes">
                Quotes
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="transactions">
                Transactions
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="tickets">
                Tickets
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="emails">
                Emails
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="notes">
                Notes
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="consenthistory">
                Consent History
            </label>
        </div>
        <div class="col-sm-3">
            <label class="checkbox-inline">
                <input type="checkbox" name="exportdata[]" value="activitylog">
                Activity Log
            </label>
        </div>
    </div>
</div>
<button type="submit" class="btn btn-default"' . ($userid ? '' : ' disabled="disabled"') . ' id="btnExport">
    <i class="fas fa-download fa-fw"></i>
    Generate and Download Export
</button>
<br><br>
<small>* Generating an export for a client with a substantial amount of history may take a while</small>
</form>

<script>
$(document).ready(function() {
    jQuery("#selectUserid")[0].selectize.on("change", function() {
        var userId = this.getValue();
        if (userId) {
            $("#frmClientExport").attr("action", $("#frmClientExport").data("route").replace("xxx", userId));
            $("#btnExport").removeProp("disabled");
        }
    });

    ' . $onloadUserReplaceJs . '
});
</script>
';

?>