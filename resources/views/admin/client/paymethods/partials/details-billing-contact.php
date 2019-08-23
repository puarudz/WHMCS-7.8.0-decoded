<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$newContactHref = "clientscontacts.php?userid=" . $client->id . "&contactid=addnew";
$viewPartial = new WHMCS\Admin\ApplicationSupport\View\Html\Helper\ContactSelectedDropDown($client, true, "billingContactId", "client");
$selectOptions = array();
$selectOptions[] = array("id" => "client", "descriptor" => " - client", "name" => $client->fullName, "companyname" => $client->companyName, "email" => $client->email, "address" => $client->address1 . " " . $client->state . " " . $client->postcode . " " . $client->countryName);
foreach ($client->contacts as $contact) {
    $selectOptions[] = array("id" => $contact->id, "descriptor" => " - contact", "name" => $contact->fullName, "companyname" => $contact->companyName, "email" => $contact->email, "address" => $contact->address1 . " " . $contact->state . " " . $contact->postcode . " " . $contact->countryName);
}
$selectedId = $client->billingContactId ? $client->billingContactId : "client";
if (isset($payMethod)) {
    $contact = $payMethod->contact;
    if (!$contact instanceof WHMCS\User\Client) {
        $selectedId = $contact->id;
    }
}
$selected = "payMethodSelectized.setValue('" . $selectedId . "', '');";
echo "<script>\n    jQuery(document).ready(function(){\n        var payMethodSelectized = WHMCS.selectize.billingContacts(\n            '#selectBillingContact',\n            ";
echo json_encode($selectOptions);
echo "        );\n        ";
echo $selected;
echo "    });\n</script>\n<div class=\"row\">\n    <div class=\"col-sm-12\">\n        <div class=\"form-group\" style=\"min-height: 9em\">\n            <label for=\"inputDescription\">Billing Address (<a class=\"link\" target=\"_blank\" href=\"";
echo $newContactHref;
echo "\">Manage</a>)</label>\n            <select id=\"selectBillingContact\"\n                name=\"billingContactId\"\n                class=\"form-control selectize\"\n                data-value-field=\"id\"\n                data-search-url=\"";
echo routePath("admin-search-client-contacts", $client->id);
echo "\"\n                placeholder=\"Start Typing to Search Contacts\">\n            </select>\n        </div>\n    </div>\n</div>\n";

?>