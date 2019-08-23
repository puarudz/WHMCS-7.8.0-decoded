<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form id=\"frmClientSearch\" method=\"post\" action=\"clients.php\">\n    <input id=\"status\" type=\"hidden\" name=\"status\" value=\"";
echo $searchCriteria["status"];
echo "\" />\n    <div class=\"search-bar\" id=\"search-bar\">\n        <div class=\"simple\">\n            <div class=\"search-icon\">\n                <div class=\"icon-wrapper\">\n                    <i class=\"fas fa-search\"></i>\n                </div>\n            </div>\n            <div class=\"search-fields\">\n                <div class=\"row\">\n                    <div class=\"col-xs-12 col-sm-4 col-md-3 col-lg-2\">\n                        <div class=\"form-group\">\n                            <label for=\"inputName\">Client/Company Name</label>\n                            <input type=\"text\" name=\"name\" id=\"inputName\" class=\"form-control\"\n                                value=\"";
echo e($searchCriteria["name"]);
echo "\">\n                        </div>\n                    </div>\n                    <div class=\"col-sm-4 col-md-3 col-lg-2 hidden-xs\">\n                        <div class=\"form-group\">\n                            <label for=\"inputEmail\">Email Address</label>\n                            <input type=\"text\" name=\"email\" id=\"inputEmail\" class=\"form-control\"\n                                value=\"";
echo e($searchCriteria["email"]);
echo "\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 col-lg-2 visible-lg\">\n                        <div class=\"form-group\">\n                            <label for=\"inputPhone\">Phone Number</label>\n                            <input type=\"tel\" name=\"phone\" id=\"inputPhone\" class=\"form-control\"\n                                value=\"";
echo e($searchCriteria["phone"]);
echo "\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 col-lg-2 visible-md visible-lg\">\n                        <div class=\"form-group\">\n                            <label for=\"inputGroup\">Client Group</label>\n                            <select type=\"text\" name=\"group\" id=\"inputGroup\" class=\"form-control\">\n                                <option value=\"\">Any</option>\n                                ";
foreach ($clientGroups as $groupId => $groupName) {
    echo "<option value=\"" . $groupId . "\"" . ($groupId == $searchCriteria["group"] ? " selected" : "") . ">" . $groupName . "</option>";
}
echo "                            </select>\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 col-lg-2 visible-md visible-lg\">\n                        <div class=\"form-group\">\n                            <label for=\"inputStatus\">Status</label>\n                            <select id=\"inputStatus\" class=\"form-control status\">\n                                <option value=\"any\"\n                                    ";
echo $searchCriteria["status"] == "any" ? "selected" : "";
echo "                                >Any</option>\n                                ";
foreach ($clientStatuses as $status) {
    echo "<option value=\"" . $status . "\"" . ($status == $searchCriteria["status"] ? " selected" : "") . ">" . $status . "</option>";
}
echo "                            </select>\n                        </div>\n                    </div>\n                    <div class=\"col-xs-6 col-sm-2 col-md-1\">\n                        <label class=\"hidden-xs\">&nbsp;</label>\n                        <button type=\"button\" id=\"btnSearchClientsAdvanced\" class=\"btn btn-default btn-search-advanced btn-block\">\n                            <i class=\"fas fa-plus fa-fw\"></i>\n                            <span class=\"hidden-md\">Advanced</span>\n                        </button>\n                    </div>\n                    <div class=\"col-xs-6 col-sm-2 col-md-1\">\n                        <label class=\"clear-search hidden-xs\">\n                            &nbsp;\n                            <a href=\"clients.php\" class=\"";
echo !$searchActive ? " hidden" : "";
echo "\">\n                                <i class=\"fas fa-times fa-fw\"></i>\n                                Reset\n                            </a>\n                        </label>\n                        <button type=\"submit\" id=\"btnSearchClients\" class=\"btn btn-primary btn-search btn-block\">\n                            <i class=\"fas fa-search fa-fw\"></i>\n                            <span class=\"hidden-md\">Search</span>\n                        </button>\n                    </div>\n                </div>\n            </div>\n        </div>\n        <div class=\"advanced-search-options\">\n            <div class=\"row\">\n                <div class=\"col-sm-6 col-md-3\">\n                    <div class=\"form-group visible-xs\">\n                        <label for=\"inputEmail2\">Email Address</label>\n                        <input type=\"text\" name=\"email2\" id=\"inputEmail2\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["email2"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputAddress1\">Address 1</label>\n                        <input type=\"text\" name=\"address1\" id=\"inputAddress1\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["address1"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputAddress2\">Address 2</label>\n                        <input type=\"text\" name=\"address2\" id=\"inputAddress2\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["address2"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCity\">City</label>\n                        <input type=\"text\" name=\"city\" id=\"inputCity\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["city"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputState\">State</label>\n                        <input type=\"text\" name=\"state\" id=\"inputState\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["state"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputPostcode\">Postcode</label>\n                        <input type=\"text\" name=\"postcode\" id=\"inputPostcode\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["postcode"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCountry\">Country</label>\n                        <select name=\"country\" id=\"inputCountry\" class=\"form-control\">\n                            <option value=\"\">Any</option>\n                            ";
foreach ($countries as $code => $displayName) {
    echo "                                <option value=\"";
    echo $code;
    echo "\">";
    echo $displayName;
    echo "</option>\n                            ";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group hidden-lg\">\n                        <label for=\"inputPhone2\">Phone Number</label>\n                        <input type=\"text\" name=\"phone2\" id=\"inputPhone2\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["phone2"]);
echo "\">\n                    </div>\n                </div>\n                <div class=\"col-sm-6 col-md-3\">\n                    <div class=\"form-group hidden-md hidden-lg\">\n                        <label for=\"inputGroup2\">Client Group</label>\n                        <select type=\"text\" name=\"group2\" id=\"inputGroup2\" class=\"form-control\">\n                            <option value=\"\">Any Group</option>\n                            ";
foreach ($clientGroups as $groupId => $groupName) {
    echo "<option value=\"" . $groupId . "\"" . ($groupId == $searchCriteria["group2"] ? " selected" : "") . ">" . $groupName . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputPaymentmethod\">Default Payment Method</label>\n                        <select name=\"paymentmethod\" id=\"inputPaymentmethod\" class=\"form-control\">\n                            <option value=\"\">Any</option>\n                            ";
foreach ($paymentMethods as $moduleName => $displayName) {
    echo "<option value=\"" . $moduleName . "\"" . ($moduleName == $searchCriteria["paymentmethod"] ? " selected" : "") . ">" . $displayName . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCctype\">Credit Card Type</label>\n                        <select name=\"cctype\" id=\"inputCctype\" class=\"form-control\">\n                            <option value=\"\">Any</option>\n                            ";
foreach ($cardTypes as $cardType) {
    echo "<option value=\"" . $cardType . "\"" . ($cardType == $searchCriteria["cctype"] ? " selected" : "") . ">" . $cardType . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCclastfour\">Credit Card Last Four</label>\n                        <input type=\"text\" name=\"cclastfour\" id=\"inputCclastfour\"\n                            class=\"form-control\" value=\"";
echo e($searchCriteria["cclastfour"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputAutoccbilling\">Automatic Credit Card Billing</label>\n                        <select name=\"autoccbilling\" id=\"inputAutoccbilling\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptionsInverse as $key => $value) {
    echo "<option value=\"" . $key . "\"" . ((string) $key === $searchCriteria["autoccbilling"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCredit\">Credit Balance</label>\n                        <input type=\"text\" name=\"credit\" id=\"inputCredit\" class=\"form-control\"\n                            value=\"";
echo e($searchCriteria["credit"]);
echo "\">\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputCurrency\">Currency</label>\n                        <select name=\"currency\" id=\"inputCurrency\" class=\"form-control\">\n                            <option value=\"\">Any</option>\n                            ";
foreach ($currencies as $currencyId => $currencyCode) {
    echo "<option value=\"" . $currencyId . "\"" . ($currencyId == $searchCriteria["currency"] ? " selected" : "") . ">" . $currencyCode . "</option>";
}
echo "                        </select>\n                    </div>\n                </div>\n                <div class=\"col-sm-6 col-md-3\">\n                    <div class=\"form-group\">\n                        <label for=\"inputDateCreated\">Signup Date</label>\n                        <div class=\"form-group date-picker-prepend-icon\">\n                            <label for=\"inputDateCreated\" class=\"field-icon\">\n                                <i class=\"fal fa-calendar-alt\"></i>\n                            </label>\n                            <input id=\"inputDateCreated\"\n                                   type=\"text\"\n                                   name=\"signupdaterange\"\n                                   value=\"";
echo $searchCriteria["signupdaterange"];
echo "\"\n                                   class=\"form-control date-picker-search date-picker-search-100pc\"\n                            />\n                        </div>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputLanguage\">Language</label>\n                        <select name=\"language\" id=\"inputLanguage\" class=\"form-control\">\n                            <option value=\"\">Any</option>\n                            ";
foreach ($clientLanguages as $language) {
    echo "<option value=\"" . $language . "\"" . ($language == $searchCriteria["language"] ? " selected" : "") . ">" . ucfirst($language) . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputMarketingoptin\">Marketing Emails Opt-in</label>\n                        <select name=\"marketingoptin\" id=\"inputMarketingoptin\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptions as $key => $value) {
    if ($key === "true") {
        $value = "Opted-In";
    } else {
        if ($key === "false") {
            $value = "Opted-Out";
        }
    }
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["marketingoptin"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputEmailverification\">Email Verification Status</label>\n                        <select name=\"emailverification\" id=\"inputEmailverification\"\n                            class=\"form-control\">\n                            ";
foreach ($searchEnabledOptions as $key => $value) {
    if ($key === "true") {
        $value = "Verified";
    } else {
        if ($key === "false") {
            $value = "Unverified";
        }
    }
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["emailverification"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputAutostatus\">Automatic Status Update</label>\n                        <select name=\"autostatus\" id=\"inputAutostatus\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptionsInverse as $key => $value) {
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["autostatus"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                </div>\n                <div class=\"col-sm-6 col-md-3\">\n                    <div class=\"form-group hidden-md hidden-lg\">\n                        <label for=\"inputStatus2\">Status</label>\n                        <select id=\"inputStatus2\" class=\"form-control status\">\n                            <option value=\"any\"\n                                ";
echo $searchCriteria["status"] == "any" ? "selected" : "";
echo "                            >Any Status</option>\n                            ";
foreach ($clientStatuses as $status) {
    echo "<option value=\"" . $status . "\"" . ($status == $searchCriteria["status"]) . ">" . $status . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputTaxexempt\">Tax Exempt Status</label>\n                        <select name=\"taxexempt\" id=\"inputTaxexempt\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptions as $key => $value) {
    if ($key === 1) {
        $value = "Exempt";
    } else {
        if ($key === 0) {
            $value = "Non-Exempt";
        }
    }
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["taxexempt"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputLatefees\">Late Fees</label>\n                        <select name=\"latefees\" id=\"inputLatefees\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptionsInverse as $key => $value) {
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["latefees"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputOverduenotices\">Overdue Notices</label>\n                        <select name=\"overduenotices\" id=\"inputOverduenotices\" class=\"form-control\">\n                            ";
foreach ($searchEnabledOptionsInverse as $key => $value) {
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["overduenotices"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputSeparateinvoices\">Separate Invoices</label>\n                        <select name=\"separateinvoices\" id=\"inputSeparateinvoices\"\n                            class=\"form-control\">\n                            ";
foreach ($searchEnabledOptions as $key => $value) {
    echo "<option value=\"" . $key . "\"" . ($key == $searchCriteria["separateinvoices"] ? " selected" : "") . ">" . $value . "</option>";
}
echo "                        </select>\n                    </div>\n                </div>\n                <div class=\"clearfix\"></div>\n                ";
foreach ($customFields as $field) {
    echo "                    <div class=\"col-sm-6 col-md-3\">\n                        <div class=\"form-group\">\n                            <label for=\"inputCf";
    echo $field->id;
    echo "\">\n                                ";
    echo $field->fieldname;
    echo "                            </label>\n                            ";
    if ($field->fieldtype == "dropdown") {
        echo "                                <select name=\"customfields[";
        echo $field->id;
        echo "]\" id=\"inputCf";
        echo $field->id;
        echo "\"\n                                class=\"form-control\">\n                                    <option value=\"\">Any</option>\n                                    ";
        foreach (explode(",", $field->fieldoptions) as $value) {
            echo "<option value=\"" . $value . "\"" . ($value == $searchCriteria["customfields"][$field->id] ? " selected" : "") . ">" . $value . "</option>";
        }
        echo "                                </select>\n                            ";
    } else {
        echo "                                <input type=\"text\" name=\"customfields[";
        echo $field->id;
        echo "]\"\n                                    id=\"inputCf";
        echo $field->id;
        echo "\"\n                                    value=\"";
        echo e($searchCriteria["customfields"][$field->id]);
        echo "\"\n                                    class=\"form-control\">\n                            ";
    }
    echo "                        </div>\n                    </div>\n                ";
}
echo "            </div>\n        </div>\n    </div>\n</form>\n\n";
echo $tableOutput;

?>