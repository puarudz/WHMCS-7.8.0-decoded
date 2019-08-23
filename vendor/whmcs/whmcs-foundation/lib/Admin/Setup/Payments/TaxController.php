<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Payments;

class TaxController
{
    public function saveSettings(\WHMCS\Http\Message\ServerRequest $request)
    {
        $taxEnabled = $request->get("taxenabled", "");
        $taxType = $request->get("taxtype", "");
        $taxDomains = $request->get("taxdomains", "");
        $taxBillableItems = $request->get("taxbillableitems", "");
        $taxLateFee = $request->get("taxlatefee", "");
        $taxCustomInvoices = $request->get("taxcustominvoices", "");
        $taxL2Compound = $request->get("taxl2compound", "");
        $taxInclusiveDeduct = $request->get("taxinclusivededuct", "");
        $taxPerLineItem = $request->get("taxperlineitem");
        $taxVatEnabled = (bool) (int) $request->get("vatenabled", 0);
        $euTaxValidation = (bool) (int) $request->get("eu_tax_validation", 0);
        $euTaxExempt = (bool) (int) $request->get("eu_tax_exempt", 0);
        $homeCountry = $request->get("home_country", "");
        $homeCountryExempt = (bool) (int) $request->get("home_country_exempt", 0);
        $customInvoiceNumbering = (bool) (int) $request->get("custom_invoice_numbering", 0);
        $customInvoiceNumberFormat = $request->get("custom_invoice_number_format", "{NUMBER}");
        $autoResetNumbering = $request->get("custom_invoice_number_reset_frequency", "");
        $autoResetPaidNumbering = $request->get("paid_invoice_number_reset_frequency", "");
        $setInvoiceDateOnPayment = (bool) (int) $request->get("set_invoice_date", 0);
        $sequentialPaidFormat = $request->get("sequential_paid_format", "{NUMBER}");
        $paidInvoiceNumbering = (bool) (int) $request->get("sequential_paid_numbering", 0);
        $taxIdEnabled = (bool) (int) $request->get("tax_id_enabled", 0);
        $taxSettings = array("TaxEnabled" => $taxEnabled, "TaxType" => $taxType, "TaxDomains" => $taxDomains, "TaxBillableItems" => $taxBillableItems, "TaxLateFee" => $taxLateFee, "TaxCustomInvoices" => $taxCustomInvoices, "TaxL2Compound" => $taxL2Compound, "TaxInclusiveDeduct" => $taxInclusiveDeduct, "TaxPerLineItem" => $taxPerLineItem, "TaxVATEnabled" => $taxVatEnabled, "TaxEUTaxValidation" => $euTaxValidation, "TaxEUHomeCountry" => $homeCountry, "TaxEUTaxExempt" => $euTaxExempt, "TaxEUHomeCountryNoExempt" => $homeCountryExempt, "TaxCustomInvoiceNumbering" => $customInvoiceNumbering, "TaxCustomInvoiceNumberFormat" => $customInvoiceNumberFormat, "TaxAutoResetNumbering" => $autoResetNumbering, "TaxAutoResetPaidNumbering" => $autoResetPaidNumbering, "TaxSetInvoiceDateOnPayment" => $setInvoiceDateOnPayment, "SequentialInvoiceNumberFormat" => $sequentialPaidFormat, "SequentialInvoiceNumbering" => $paidInvoiceNumbering, "TaxCode" => $request->get("tax_code", ""), "TaxIDDisabled" => !$taxIdEnabled, "EnableProformaInvoicing" => $paidInvoiceNumbering);
        $nextCustomInvoiceNumber = $request->get("next_custom_invoice_number");
        if ($nextCustomInvoiceNumber && is_numeric($nextCustomInvoiceNumber)) {
            $taxSettings["TaxNextCustomInvoiceNumber"] = $nextCustomInvoiceNumber;
        }
        $nextSequentialInvoiceNumber = $request->get("next_paid_invoice_number");
        if ($nextSequentialInvoiceNumber && is_numeric($nextSequentialInvoiceNumber)) {
            $taxSettings["SequentialInvoiceNumberValue"] = $nextSequentialInvoiceNumber;
        }
        $changes = array();
        foreach ($taxSettings as $k => $v) {
            if ($k != "TaxEnabled" && \WHMCS\Config\Setting::getValue($k) != $v) {
                $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z][0-9])(?=[A-Z][a-z])/x";
                $friendlySettingParts = preg_split($regEx, $k);
                $friendlySetting = implode(" ", $friendlySettingParts);
                if ($k == "TaxType") {
                    $changes[] = (string) $friendlySetting . " Set to '" . $v . "'";
                } else {
                    if ($v == "on") {
                        $changes[] = (string) $friendlySetting . " Enabled";
                    } else {
                        $changes[] = (string) $friendlySetting . " Disabled";
                    }
                }
            }
            \WHMCS\Config\Setting::setValue($k, $v);
        }
        if ($changes) {
            logAdminActivity("Tax Configuration: " . implode(". ", $changes) . ".");
        }
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
    }
    public function create(\WHMCS\Http\Message\ServerRequest $request)
    {
        $name = $request->get("name", "Tax");
        $state = $request->get("state", "");
        $country = $request->get("country", "");
        $taxRate = $request->get("taxrate", 0);
        $level = (int) $request->get("level", 1);
        $countryType = $request->get("countrytype", "any");
        $stateType = $request->get("statetype", "any");
        $response = array("status" => "error", "title" => \AdminLang::trans("global.error"), "message" => "An unknown error occurred");
        if (!$name) {
            $name = "Tax";
        }
        if ($countryType == "any" && $stateType != "any") {
            $response["title"] = \AdminLang::trans("global.validationerror");
            $response["message"] = \AdminLang::trans("taxconfig.taxvalidationerrorcountry");
        } else {
            if ($countryType == "any") {
                $country = "";
            }
            if ($stateType == "any") {
                $state = "";
            }
            logAdminActivity("Tax Configuration: Level " . $level . " Rule Added: " . $name);
            $taxId = \WHMCS\Database\Capsule::table("tbltax")->insertGetId(array("level" => $level, "name" => $name, "state" => $state, "country" => $country, "taxrate" => $taxRate));
            $countries = (new \WHMCS\Utility\Country())->getCountryNameArray();
            if (array_key_exists($country, $countries)) {
                $country = $countries[$country];
            }
            if ($state == "") {
                $state = \AdminLang::trans("taxconfig.taxappliesanystate");
            }
            if ($country == "") {
                $country = \AdminLang::trans("taxconfig.taxappliesanycountry");
            }
            $response = array("status" => "success", "id" => $taxId, "level" => $level, "name" => $name, "state" => $state, "country" => $country, "rate" => $taxRate);
        }
        return new \WHMCS\Http\Message\JsonResponse($response);
    }
    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        $id = (int) $request->get("id", 0);
        $taxRule = \WHMCS\Database\Capsule::table("tbltax")->find($id);
        if ($taxRule) {
            logAdminActivity("Tax Configuration: Level " . $taxRule->level . " Rule Deleted: " . $taxRule->name);
            \WHMCS\Database\Capsule::table("tbltax")->delete($id);
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
        }
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "error"));
    }
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper())->setTitle(\AdminLang::trans("taxconfig.taxConfiguration"))->setSidebarName("config")->setFavicon("taxrules")->setHelpLink("Tax_Configuration");
        $content = "";
        if ($request->get("saved")) {
            $content .= infoBox(\AdminLang::trans("global.changesuccess"), \AdminLang::trans("global.changesuccessdesc"));
        }
        $content .= $this->pageSummaryHtml();
        $content .= \WHMCS\View\Asset::jsInclude("StatesDropdown.js");
        $view->addJavascript("var stateNotRequired = true;" . PHP_EOL);
        $view->setBodyContent($content);
        $view->addJquery($this->pageJQuery());
        return $view;
    }
    protected function pageSummaryHtml()
    {
        $taxEnabledAttribute = $exclusiveTaxAttribute = $inclusiveTaxAttribute = "";
        $taxDomainsAttribute = $taxBillableItems = $taxLateFees = $taxCustomInvoices = "";
        $taxL2Compound = $taxInclusiveDeduct = $taxIndividuallyPerLineItem = "";
        $taxCombinedLines = $taxEUTaxValidation = $taxEUTaxExempt = "";
        $taxEUHomeCountryNoExempt = $taxCustomInvoiceNumbering = "";
        $sequentialInvoiceNumbering = $taxSetInvoiceDateOnPayment = "";
        $taxVatAttribute = "";
        if (\WHMCS\Config\Setting::getValue("TaxEnabled")) {
            $taxEnabledAttribute = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxVATEnabled")) {
            $taxVatAttribute = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxType") == "Exclusive") {
            $exclusiveTaxAttribute = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxType") == "Inclusive") {
            $inclusiveTaxAttribute = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxDomains")) {
            $taxDomainsAttribute = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxBillableItems")) {
            $taxBillableItems = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxLateFee")) {
            $taxLateFees = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxCustomInvoices")) {
            $taxCustomInvoices = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxL2Compound")) {
            $taxL2Compound = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
            $taxInclusiveDeduct = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
            $taxIndividuallyPerLineItem = " checked=\"checked\"";
        } else {
            $taxCombinedLines = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxEUTaxValidation")) {
            $taxEUTaxValidation = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxEUTaxExempt")) {
            $taxEUTaxExempt = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxEUHomeCountryNoExempt")) {
            $taxEUHomeCountryNoExempt = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxCustomInvoiceNumbering")) {
            $taxCustomInvoiceNumbering = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("SequentialInvoiceNumbering")) {
            $sequentialInvoiceNumbering = " checked=\"checked\"";
        }
        if (\WHMCS\Config\Setting::getValue("TaxSetInvoiceDateOnPayment")) {
            $taxSetInvoiceDateOnPayment = " checked=\"checked\"";
        }
        $taxIdEnabled = " checked=\"checked\"";
        if (\WHMCS\Config\Setting::getValue("TaxIDDisabled")) {
            $taxIdEnabled = "";
        }
        $taxCustomInvoiceNumberFormat = \WHMCS\Config\Setting::getValue("TaxCustomInvoiceNumberFormat");
        $customFieldOutput = "";
        $taxCustomField = (int) \WHMCS\Config\Setting::getValue("TaxVatCustomFieldId");
        if ($taxCustomField) {
            $customFields = \WHMCS\Database\Capsule::table("tblcustomfields")->where("type", "client")->pluck("fieldname", "id");
            if ($customFields) {
                foreach ($customFields as $fieldId => $customField) {
                    $selected = "";
                    if ($taxCustomField == $fieldId) {
                        $selected = " selected=\"selected\"";
                    }
                    $customFieldOutput .= "<option value=\"" . $fieldId . "\"" . $selected . ">" . $customField . "</option>";
                }
            }
        }
        $countryArray = (new \WHMCS\Utility\Country())->getCountryNameArray();
        $homeCountryOutput = "";
        foreach (\WHMCS\Billing\Tax\Vat::EU_COUNTRIES as $country => $rate) {
            $selected = "";
            $countryName = $countryArray[$country];
            if ($country == \WHMCS\Config\Setting::getValue("TaxEUHomeCountry")) {
                $selected = " selected=\"selected\"";
            }
            $homeCountryOutput .= "<option value=\"" . $country . "\"" . $selected . ">" . $countryName . "</option>";
        }
        $autoResetNumbering = \WHMCS\Config\Setting::getValue("TaxAutoResetNumbering");
        switch ($autoResetNumbering) {
            case "monthly":
                $autoResetNumberingNever = "";
                $autoResetNumberingMonthly = " checked=\"checked\"";
                $autoResetNumberingAnnually = "";
                break;
            case "annually":
                $autoResetNumberingNever = "";
                $autoResetNumberingMonthly = "";
                $autoResetNumberingAnnually = " checked=\"checked\"";
                break;
            default:
                $autoResetNumberingNever = " checked=\"checked\"";
                $autoResetNumberingMonthly = "";
                $autoResetNumberingAnnually = "";
        }
        $paidResetNumbering = \WHMCS\Config\Setting::getValue("TaxAutoResetPaidNumbering");
        switch ($paidResetNumbering) {
            case "monthly":
                $paidResetNumberingNever = "";
                $paidResetNumberingMonthly = " checked=\"checked\"";
                $paidResetNumberingAnnually = "";
                break;
            case "annually":
                $paidResetNumberingNever = "";
                $paidResetNumberingMonthly = "";
                $paidResetNumberingAnnually = " checked=\"checked\"";
                break;
            default:
                $paidResetNumberingNever = " checked=\"checked\"";
                $paidResetNumberingMonthly = "";
                $paidResetNumberingAnnually = "";
        }
        $nextCustomInvoiceNumber = (int) \WHMCS\Config\Setting::getValue("TaxNextCustomInvoiceNumber");
        if (!$nextCustomInvoiceNumber || $nextCustomInvoiceNumber === 0) {
            $nextCustomInvoiceNumber = 1;
        }
        $sequentialInvoiceNumberValue = (int) \WHMCS\Config\Setting::getValue("SequentialInvoiceNumberValue");
        if (!$sequentialInvoiceNumberValue || $sequentialInvoiceNumberValue === 0) {
            $sequentialInvoiceNumberValue = 1;
        }
        return view("admin.setup.payments.tax.index", array("taxEnabled" => $taxEnabledAttribute, "exclusiveTaxAttribute" => $exclusiveTaxAttribute, "inclusiveTaxAttribute" => $inclusiveTaxAttribute, "taxDomainsAttribute" => $taxDomainsAttribute, "taxBillableItems" => $taxBillableItems, "taxLateFees" => $taxLateFees, "taxCustomInvoices" => $taxCustomInvoices, "taxL2Compound" => $taxL2Compound, "taxInclusiveDeduct" => $taxInclusiveDeduct, "taxIndividuallyPerLineItem" => $taxIndividuallyPerLineItem, "taxCombinedLines" => $taxCombinedLines, "taxVatSupport" => $taxVatAttribute, "taxEUTaxValidation" => $taxEUTaxValidation, "customFieldOutput" => $customFieldOutput, "taxCustomField" => $taxCustomField, "taxEUTaxExempt" => $taxEUTaxExempt, "taxEUHomeCountryNoExempt" => $taxEUHomeCountryNoExempt, "taxCustomInvoiceNumbering" => $taxCustomInvoiceNumbering, "taxCustomInvoiceNumberFormat" => $taxCustomInvoiceNumberFormat, "taxCode" => \WHMCS\Config\Setting::getValue("TaxCode"), "taxIdEnabled" => $taxIdEnabled, "nextCustomInvoiceNumber" => $nextCustomInvoiceNumber, "autoResetNumberingNever" => $autoResetNumberingNever, "autoResetNumberingMonthly" => $autoResetNumberingMonthly, "autoResetNumberingAnnually" => $autoResetNumberingAnnually, "sequentialInvoiceNumbering" => $sequentialInvoiceNumbering, "sequentialInvoiceNumberValue" => $sequentialInvoiceNumberValue, "sequentialInvoiceNumberFormat" => \WHMCS\Config\Setting::getValue("SequentialInvoiceNumberFormat"), "paidResetNumberingNever" => $paidResetNumberingNever, "paidResetNumberingMonthly" => $paidResetNumberingMonthly, "paidResetNumberingAnnually" => $paidResetNumberingAnnually, "taxSetInvoiceDateOnPayment" => $taxSetInvoiceDateOnPayment, "levelOneRules" => $this->taxRuleQuery(1)->get(), "levelTwoRules" => $this->taxRuleQuery(2)->get(), "countries" => $countryArray, "homeCountryOutput" => $homeCountryOutput));
    }
    protected function taxRuleQuery($level)
    {
        return \WHMCS\Database\Capsule::table("tbltax")->where("level", $level)->orderBy("country")->orderBy("state");
    }
    protected function getTaxHtmlTableData($level = 1)
    {
        $countries = new \WHMCS\Utility\Country();
        $countries = $countries->getCountryNameArray();
        $tableData = array();
        $query = $this->taxRuleQuery($level);
        $ruleSet = $query->orderBy("country", "asc")->orderBy("state", "asc")->orderBy("country", "asc")->get();
        foreach ($ruleSet as $data) {
            if (array_key_exists($data->country, $countries)) {
                $country = $countries[$data->country];
            } else {
                $country = $data->country;
            }
            $state = $data->state;
            if ($state == "") {
                $state = \AdminLang::trans("taxconfig.taxappliesanystate");
            }
            if ($country == "") {
                $country = \AdminLang::trans("taxconfig.taxappliesanycountry");
            }
            $tableData[] = array((string) $data->name, $country, $state, (string) $data->taxrate . "%", "<a class=\"deleteRule\" href=\"#\" " . " data-href=\"" . routePath("admin-setup-payments-tax-delete") . "\" " . " data-id=\"" . (string) $data->id . "\" " . "\">" . "<img src=\"images/delete.gif\" border=\"0\"></a>");
        }
        return $tableData;
    }
    protected function pageJQuery()
    {
        $successTitle = \AdminLang::trans("global.changesuccess");
        $successDesc = \AdminLang::trans("global.changesuccessdesc");
        $errorTitle = \AdminLang::trans("global.error");
        $errorDescription = \AdminLang::trans("taxconfig.errorDeleting");
        $deleteConfirmTitle = \AdminLang::trans("global.delete");
        $deleteConfirmDesc = \AdminLang::trans("taxconfig.delsuretaxrule");
        $euRatesSuccess = \AdminLang::trans("taxconfig.ratesSetup");
        $taxRuleAddSuccess = \AdminLang::trans("taxconfig.taxRuleAddSuccess");
        $migrateConfirmTitle = "Migrate Custom Field Data";
        $migrateConfirmDesc = "This is a one time process that migrates Tax ID/VAT Number" . " custom field values to the native field in WHMCS 7.7 and later. Once the" . " migration has been performed, it cannot be undone. For installations where" . " there is a lot of data to migrate, the process may take some time." . " Are you sure you wish to continue?";
        return "jQuery(document).on('click', '.deleteRule',\n    function(e) {\n        e.preventDefault();\n        var anchor = jQuery(this);\n        swal(\n            {\n                title: \"" . $deleteConfirmTitle . "\",\n                text: \"" . $deleteConfirmDesc . "\",\n                type: \"warning\",\n                showCancelButton: true,\n                confirmButtonColor: \"#DD6B55\",\n                closeOnConfirm: false,\n                showLoaderOnConfirm: true\n            },\n            function() {\n                WHMCS.http.jqClient.post(\n                    anchor.data('href'),\n                    {\n                        id: anchor.data('id'),\n                        token: csrfToken\n                    },\n                    function (data) {\n                        if (data.status === 'success') {\n                            anchor.parents('tr').slideUp('fast', function() {\n                            jQuery(this).remove();\n                            swal(\"" . $successTitle . "\", \"" . $successDesc . "\", \"success\");\n                    });\n                        } else {\n                            swal(\"" . $errorTitle . "\", \"" . $errorDescription . "\", \"error\");\n                        }\n                    },\n                    'json'\n                );\n            }\n        );              \n    }\n);\n\njQuery(\"#country\").on(\n    \"change\",\n    function() {\n        if (jQuery('input:radio[name=\"countrytype\"]:checked').val() === 'any') {\n            jQuery('input:radio[name=\"countrytype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"focus\",\n    \"#stateinput\",\n    function() {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() === 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);\njQuery(document).on(\n    \"change\",\n    \"#stateselect\",\n    function() {\n        if (jQuery('input:radio[name=\"statetype\"]:checked').val() === 'any') {\n            jQuery('input:radio[name=\"statetype\"][value=\"specific\"]').click();\n        }\n    }\n);\n\njQuery('#frmTaxSettings').submit(function(e){\n    e.preventDefault();\n    var form = jQuery(this),\n        url = form.attr(\"action\"),\n        data = form.serialize();\n    \n    WHMCS.http.jqClient.post(\n        url,\n        data,\n        function () {\n            document.activeElement.blur()\n            \$(window).scrollTop(0);\n            swal(\"" . $successTitle . "\", \"" . $successDesc . "\", \"success\");\n        }\n    );\n});\njQuery('.add-rule-field').on('keypress', function(e) {\n    if (e.keyCode === 13) {\n        e.preventDefault();\n        jQuery('#btnAddRule').click();\n    }\n});\njQuery('#btnAddRule').on('click', function(e){\n    e.preventDefault();\n    var form = jQuery('div#addTaxRule'),\n        url = form.data(\"action\"),\n        data = form.find('input,select').serialize() + '&token=' + csrfToken;\n    \n    WHMCS.http.jqClient.post(\n        url,\n        data,\n        function (response) {\n            jQuery('#growls').fadeOut('fast').remove();\n            if (response.status === 'success') {\n                var table = jQuery('table[data-level-id=\"' + response.level + '\"]'),\n                    newRow = jQuery('#emptyRow').clone();\n                newRow.attr('id', 'newRow' + response.id)\n                    .find('.ruleName').html(response.name).removeClass('ruleName').end()\n                    .find('.ruleCountry').html(response.country).removeClass('ruleCountry').end()\n                    .find('.ruleState').html(response.state).removeClass('ruleState').end()\n                    .find('.ruleRate').html(response.rate + '%').removeClass('ruleRate').end()\n                    .find('.deleteRule').attr('data-id', response.id).end()\n                    .removeClass('hidden');\n                table.append(newRow);\n                form.find('input[name=\"name\"]').val('').end()\n                    .find('input[name=\"taxrate\"]').val('0.00').end()\n                    .find('input[name=\"state\"]').val('').end()\n                    .find('input[name=\"countrytype\"][value=\"any\"]').click().end()\n                    .find('input[name=\"statetype\"][value=\"any\"]').click().end();\n                jQuery.growl.notice(\n                    {\n                        title: \"\",\n                        message: \"" . $taxRuleAddSuccess . "\"\n                    }\n                );\n            } else {\n                jQuery.growl.error(\n                    {\n                        title: \"\",\n                        message: response.message\n                    }\n                );\n            }\n            data = '';\n        }\n    );\n});\njQuery('#frmEUTax').submit(function(e){\n    e.preventDefault();\n    var form = jQuery(this),\n        url = form.attr(\"action\"),\n        data = form.serialize();\n    \n    WHMCS.http.jqClient.post(\n        url,\n        data,\n        function (response) {\n            if (response.status === 'success') {\n                swal(\n                    {\n                        title: \"" . $successTitle . "\",\n                        text: \"" . $euRatesSuccess . "\",\n                        type: \"success\"\n                    },\n                    function() {\n                        location.reload(true);\n                    }\n                );\n            } else {\n                swal(\"" . $errorTitle . "\", \"An unknown error occurred\", \"error\");\n            }\n        }\n    );\n});\njQuery('.tax-toggle-switch').bootstrapSwitch({'onColor': 'success'});\njQuery('#btnMigrate').on('click', function() {\n    var self = jQuery(this),\n        customFieldId = jQuery('select[name=\"vat_custom_field\"]').val(),\n        customFieldRows = jQuery('.custom-field-row');\n    \n    swal(\n        {\n            title: \"" . $migrateConfirmTitle . "\",\n            text: \"" . $migrateConfirmDesc . "\",\n            type: \"info\",\n            showCancelButton: true,\n            confirmButtonColor: \"#DD6B55\",\n            closeOnConfirm: false,\n            showLoaderOnConfirm: true\n        },\n        function() {\n            WHMCS.http.jqClient.post(\n                self.data('href'),\n                {\n                    id: customFieldId,\n                    token: csrfToken\n                },\n                function (data) {\n                    if (data.status === 'success') {\n                        customFieldRows.slideUp('fast', function() {\n                            jQuery(this).remove();\n                            swal(\"" . $successTitle . "\", \"" . $successDesc . "\", \"success\");\n                        });\n                    } else {\n                        swal(\"" . $errorTitle . "\", \"" . $errorDescription . "\", \"error\");\n                    }\n                },\n                'json'\n            );\n        }\n    );\n});\njQuery('#vatenabled').on('switchChange.bootstrapSwitch', function(event, state) {\n    var form = jQuery('#frmTaxSettings'),\n        url = form.attr(\"action\"),\n        data = form.serialize();\n    \n    WHMCS.http.jqClient.post(\n        url,\n        data,\n        function () {\n            //do nothing\n        }\n    );\n    \n    if (state === true) {\n        jQuery('#modalAutoSetupEuVatRules').modal('show');\n    }\n});";
    }
    public function setupEuRates(\WHMCS\Http\Message\ServerRequest $request)
    {
        $vatLabel = $request->get("vat_label", "VAT");
        foreach (\WHMCS\Billing\Tax\Vat::EU_COUNTRIES as $country => $rate) {
            \WHMCS\Database\Capsule::table("tbltax")->updateOrInsert(array("country" => $country, "state" => "", "level" => 1), array("taxrate" => $rate, "name" => $vatLabel));
        }
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
    }
    public function migrateCustomField(\WHMCS\Http\Message\ServerRequest $request)
    {
        $id = $request->get("id");
        if (!$id) {
            $id = \WHMCS\Config\Setting::getValue("TaxVatCustomFieldId");
        }
        if ($id) {
            $fieldValues = \WHMCS\CustomField\CustomFieldValue::with("client")->where("fieldid", $id)->limit(50)->get();
            foreach ($fieldValues as $fieldValue) {
                if ($fieldValue->value) {
                    $fieldValue->client->taxId = $fieldValue->value;
                    $fieldValue->client->save();
                }
                $fieldValue->delete();
            }
            $total = \WHMCS\CustomField\CustomFieldValue::where("fieldid", $id)->count();
            if (0 >= $total) {
                \WHMCS\Config\Setting::setValue("TaxVatCustomFieldId", "");
                try {
                    \WHMCS\CustomField::findOrFail($id)->delete();
                } catch (\Exception $e) {
                }
            }
        }
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
    }
}

?>