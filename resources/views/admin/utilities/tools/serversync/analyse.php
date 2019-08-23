<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$continueClass = "";
$continueDisabled = "";
if (count($syncItems) === 0) {
    $continueClass = " disabled";
    $continueDisabled = " disabled=\"disabled\"";
}
echo "<h2 class=\"step-label analyse\">";
echo AdminLang::trans("global.stepOfStep", array(":step" => 2, ":steps" => 4));
echo ": Analyse</h2>\n";
if ($error) {
    echo "<div class=\"alert alert-danger\">" . $error . "</div>";
}
echo "\n";
if (!$error) {
    echo "<h2 class=\"step-label review hidden\">";
    echo AdminLang::trans("global.stepOfStep", array(":step" => 3, ":steps" => 4));
    echo ": Review</h2>\n\n<form id=\"frmAccounts\" class=\"form form-horizontal\" method=\"post\" action=\"";
    echo routePath("admin-utilities-tools-serversync-review", $server->id);
    echo "\">\n\n<table id=\"tableServerSync\" class=\"table table-serversync table-striped\">\n    <tr>\n        <th></th>\n        <th colspan=\"6\" class=\"divider\">Module/Server</th>\n        <th colspan=\"7\" class=\"divider\">WHMCS</th>\n        <th rowspan=\"2\">Import<br>/Sync</th>\n    </tr>\n    <tr>\n        <th></th>\n        <th>";
    echo $uniqueIdDisplayName;
    echo "</th>\n        <th>Primary IP</th>\n        <th>Username</th>\n        <th>Product</th>\n        <th>Status</th>\n        <th class=\"divider\">Created</th>\n        <th>ID</th>\n        <th>";
    echo $uniqueIdDisplayName;
    echo "</th>\n        <th>Primary IP</th>\n        <th>Username</th>\n        <th>Product</th>\n        <th>Status</th>\n        <th class=\"divider\">Created</th>\n    </tr>\n    ";
    foreach ($syncItems as $item) {
        echo "        <tr class=\"";
        echo ($item->hasMatches() ? "matched" : "") . ($item->hasExactMatch() ? " exact" : "");
        echo "\">\n        ";
        $rowSpan = "";
        if ($item->getServicesCount()) {
            $rowSpan = " rowspan=\"" . $item->getServicesCount() . "\"";
        }
        echo "            <td ";
        echo $rowSpan;
        echo "\">\n                ";
        if ($item->hasExactMatch()) {
            echo "                    <i class=\"fas fa-check\"></i>\n                ";
        } else {
            if ($item->hasMatches()) {
                echo "                    <i class=\"fas fa-exclamation-circle\"></i>\n                ";
            } else {
                echo "                    <i class=\"fas fa-times\"></i>\n                ";
            }
        }
        echo "            <td class=\"display-name\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getUniqueIdentifier();
        echo "</td>\n            <td class=\"primary-ip\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getPrimaryIp();
        echo "</td>\n            <td class=\"username\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getUsername();
        echo "</td>\n            <td class=\"product\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getProduct();
        echo "</td>\n            <td class=\"status\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getStatus();
        echo "</td>\n            <td class=\"divider created\" ";
        echo $rowSpan;
        echo ">";
        echo $item->getCreated();
        echo "</td>\n            ";
        if ($item->getServicesCount() == 0) {
            echo "                <td colspan=\"7\" class=\"divider\"><span>No match found in WHMCS</span></td>\n                <td>\n                    <input type=\"checkbox\" name=\"import[]\" value=\"";
            echo $item->getUniqueIdentifier();
            echo "\" ";
            echo in_array($item->getUniqueIdentifier(), $import) ? " checked=\"checked\"" : "";
            echo ">\n                </td>\n            ";
        } else {
            echo "                ";
            foreach ($item->getMatches() as $serviceIterator => $service) {
                echo "                    ";
                if ($item->hasMultipleMatches() && 0 < $serviceIterator) {
                    echo "                        <tr class=\"matched dupe";
                    echo $service->isTerminated() ? " terminated" : "";
                    echo "\">\n                    ";
                }
                echo "                    <td class=\"service-id\"><a href=\"clientsservices.php?id=";
                echo $service->getId();
                echo "\" target=\"_blank\">";
                echo $service->getId();
                echo "</a></td>\n                    <td class=\"display-name\">";
                echo ($service->hasUniqueIdMatch() ? "<span>" : "<span class=\"e\">") . $service->getUniqueIdentifier();
                echo "</span></td>\n                    <td class=\"primary-ip\">";
                echo ($service->hasPrimaryIpMatch() ? "<span>" : "<span class=\"e\">") . $service->getPrimaryIp();
                echo "</span></td>\n                    <td class=\"username\">";
                echo ($service->hasUsernameMatch() ? "<span>" : "<span class=\"e\">") . $service->getUsername();
                echo "</span></td>\n                    <td class=\"product\">";
                echo ($service->hasProductMatch() ? "<span>" : "<span class=\"e\">") . $service->getProduct();
                echo "</span></td>\n                    <td class=\"status\">";
                echo ($service->hasStatusMatch() ? "<span>" : "<span class=\"e\">") . $service->getStatus();
                echo "</span></td>\n                    <td class=\"divider\">";
                echo ($service->hasCreatedMatch() ? "<span>" : "<span class=\"e\">") . $service->getCreated();
                echo "</span></td>\n                    <td>\n                        ";
                if ($item->hasMultipleMatches() && !$service->isTerminated() && 0 < $serviceIterator) {
                    echo "                            <input type=\"checkbox\" name=\"terminate[]\" value=\"";
                    echo $service->getId();
                    echo "\" ";
                    echo in_array($service->getId(), $terminate) ? " checked=\"checked\"" : "";
                    echo ">\n                        ";
                } else {
                    if (!$item->hasExactMatch()) {
                        $value = (string) $item->getUniqueIdentifier() . "||" . $service->getId();
                        $checked = "";
                        if (in_array($value, $sync)) {
                            $checked = " checked=\"checked\"";
                        }
                        echo "                            <input type=\"checkbox\" name=\"sync[]\" value=\"";
                        echo $value;
                        echo "\"";
                        echo $checked;
                        echo ">\n                        ";
                    }
                }
                echo "                    </td>\n                    ";
                if ($item->hasMultipleMatches() && $serviceIterator < $item->getServicesCount() - 1) {
                    echo "                        </tr>\n                    ";
                }
                echo "                    ";
                $syncedServiceIds[] = $service->getId();
                echo "                ";
            }
            echo "            ";
        }
        echo "        </tr>\n    ";
    }
    echo "    ";
    foreach ($services as $service) {
        if (!in_array($service->id, $syncedServiceIds) && $service->domainStatus != WHMCS\Service\Status::TERMINATED) {
            echo "            <tr class=\"nomodulematch\">\n                <td><i class=\"fas fa-minus\"></i></td>\n                <td colspan=\"6\" class=\"divider\"><span>No match found on server</span></td>\n                <td class=\"service-id\"><a href=\"clientsservices.php?id=";
            echo $service->id;
            echo "\" target=\"_blank\">";
            echo $service->id;
            echo "</a></td>\n                <td class=\"display-name\">";
            echo $service->serviceProperties->get("instanceid");
            echo "</td>\n                <td class=\"primary-ip\">";
            echo $service->dedicatedip;
            echo "</td>\n                <td class=\"username\">";
            echo $service->username;
            echo "</td>\n                <td class=\"product\">";
            echo $service->product->name;
            echo "</td>\n                <td class=\"status\">";
            echo $service->status;
            echo "</td>\n                <td class=\"divider created\">";
            echo $service->registrationDate->format("Y-m-d");
            echo "</td>\n                <td>\n                    <input type=\"checkbox\" name=\"terminate[]\" value=\"";
            echo $service->id;
            echo "\" ";
            echo in_array($service->id, $terminate) ? " checked=\"checked\"" : "";
            echo ">\n                </td>\n            </tr>\n            ";
        }
    }
    if (!$syncItems) {
        echo "    <tr class=\"nomodulematch\">\n        <td colspan=\"15\" class=\"text-center\">\n            ";
        echo AdminLang::trans("global.norecordsfound");
        echo "        </td>\n    </tr>\n    ";
    }
    echo "</table>\n\n<div id=\"divConfirmChoices\" class=\"hidden\">\n    <div id=\"divAccountsToSync\" class=\"hidden\">\n        <table id=\"tableToSync\" class=\"table table-bordered table-striped\">\n            <caption>\n                The following records will be syncronized within WHMCS.\n            </caption>\n            <tr>\n                <th>Service ID</th>\n                <th>";
    echo $uniqueIdDisplayName;
    echo "</th>\n                <th>Primary IP</th>\n                <th>Username</th>\n                <th>Product</th>\n                <th>Status</th>\n                <th>Created</th>\n            </tr>\n            <tr class=\"clone-row hidden\">\n                <td class=\"service-id\">-</td>\n                <td class=\"display-name\"></td>\n                <td class=\"primary-ip\"></td>\n                <td class=\"username\"></td>\n                <td class=\"product\"></td>\n                <td class=\"status\"></td>\n                <td class=\"created\"></td>\n            </tr>\n        </table>\n    </div>\n    <div id=\"divAccountsToImport\" class=\"hidden\">\n        <table id=\"tableToImport\" class=\"table table-bordered table-striped\">\n            <caption>\n                The following records will be created within WHMCS.\n            </caption>\n            <tr>\n                <th>Service ID</th>\n                <th>";
    echo $uniqueIdDisplayName;
    echo "</th>\n                <th>Primary IP</th>\n                <th>Username</th>\n                <th>Product</th>\n                <th>Status</th>\n                <th>Created</th>\n            </tr>\n            <tr class=\"clone-row hidden\">\n                <td class=\"service-id\">-</td>\n                <td class=\"display-name\"></td>\n                <td class=\"primary-ip\"></td>\n                <td class=\"username\"></td>\n                <td class=\"product\"></td>\n                <td class=\"status\"></td>\n                <td class=\"created\"></td>\n            </tr>\n            <tfoot>\n                <tr>\n                    <td colspan=\"7\">\n                        <div id=\"additionalActions\" class=\"admin-tabs-v2 hidden\">\n                            <h2>\n                                New Account Import Settings\n                            </h2>\n                            <div class=\"form-group\">\n                                <label class=\"col-sm-6 control-label\" for=\"toggleClientWelcome\">\n                                    Send Client Welcome Email\n                                    <br>\n                                    <small>\n                                        Send client welcome email for newly created accounts\n                                    </small>\n                                </label>\n                                <div class=\"col-sm-6\">\n                                    <input type=\"hidden\" name=\"client_welcome\" value=\"0\">\n                                    <input id=\"toggleClientWelcome\" type=\"checkbox\" name=\"client_welcome\" value=\"1\" class=\"slide-toggle\" data-size=\"small\" data-on-text=\"";
    echo AdminLang::trans("global.yes");
    echo "\" data-on-color=\"success\" data-off-text=\"";
    echo AdminLang::trans("global.no");
    echo "\" checked=\"checked\">\n                                    <select id=\"dropdownClientWelcomeEmail\" name=\"client_welcome_email\" class=\"form-control select-inline\">\n                                        ";
    foreach ($clientWelcomeEmails as $clientWelcomeEmail) {
        $selected = "";
        if ($clientWelcomeEmail == "Client Signup Email") {
            $selected = " selected=\"selected\"";
        }
        echo "<option" . $selected . ">" . $clientWelcomeEmail . "</option>";
    }
    echo "                                    </select>\n                                </div>\n                            </div>\n                            <div class=\"form-group\">\n                                <label class=\"col-sm-6 control-label\" for=\"togglePasswordReset\">\n                                    Reset Service Account Passwords\n                                    <br>\n                                    <small>\n                                        Auto-generate new password on import\n                                    </small>\n                                </label>\n                                <div class=\"col-sm-6\">\n                                    <input type=\"hidden\" name=\"password_reset\" value=\"0\">\n                                    <input id=\"togglePasswordReset\" type=\"checkbox\" name=\"password_reset\" value=\"1\" class=\"slide-toggle\" data-size=\"small\" data-on-text=\"";
    echo AdminLang::trans("global.yes");
    echo "\" data-on-color=\"success\" data-off-text=\"";
    echo AdminLang::trans("global.no");
    echo "\" checked=\"checked\">\n                                </div>\n                            </div>\n                            <div class=\"form-group\">\n                                <label class=\"col-sm-6 control-label\" for=\"toggleServiceWelcome\">\n                                    Send Service Welcome Email\n                                    <br>\n                                    <small>\n                                        Only supported for existing products\n                                    </small>\n                                </label>\n                                <div class=\"col-sm-6\">\n                                    <input type=\"hidden\" name=\"service_welcome\" value=\"0\">\n                                    <input id=\"toggleServiceWelcome\" type=\"checkbox\" name=\"service_welcome\" value=\"1\" class=\"slide-toggle\" data-size=\"small\" data-on-text=\"";
    echo AdminLang::trans("global.yes");
    echo "\" data-on-color=\"success\" data-off-text=\"";
    echo AdminLang::trans("global.no");
    echo "\" checked=\"checked\">\n                                </div>\n                            </div>\n                            <div class=\"form-group\">\n                                <label class=\"col-sm-6 control-label\" for=\"toggleBilling\">\n                                    Set Recurring Billing Information\n                                    <br>\n                                    <small>\n                                        Price will be set automatically based on billing cycle. Only supported for existing products.\n                                    </small>\n                                </label>\n                                <div class=\"col-sm-6\">\n                                    <input type=\"hidden\" name=\"set_billing\" value=\"0\">\n                                    <input id=\"toggleBilling\" name=\"set_billing\" type=\"checkbox\" class=\"slide-toggle\" data-size=\"small\" data-on-text=\"";
    echo AdminLang::trans("global.yes");
    echo "\" data-on-color=\"success\" data-off-text=\"";
    echo AdminLang::trans("global.no");
    echo "\" value=\"1\">\n                                </div>\n                            </div>\n                            <div class=\"form-group\">\n                                <div id=\"additionalBillingOptions\" class=\"hidden col-md-offset-2 col-md-9\">\n                                    <div class=\"form-group\">\n                                        <label class=\"col-sm-6 control-label\" for=\"toggleNextDueDate\">\n                                            Set Next Due Date\n                                            <br>\n                                            <small>\n                                                Set the next due date of the imported items\n                                            </small>\n                                        </label>\n                                        <div class=\"col-sm-6\">\n                                            <input id=\"toggleNextDueDate\" name=\"next_due_date\" class=\"form-control input-inline date-picker future\" data-drops=\"up\" value=\"";
    echo WHMCS\Carbon::today()->addDays(5)->toAdminDateFormat();
    echo "\">\n                                        </div>\n                                    </div>\n                                    <div class=\"form-group\">\n                                        <label class=\"col-md-6 control-label\" for=\"selectBillingCycle\">\n                                            Billing Cycle\n                                        </label>\n                                        <div class=\"col-md-6\">\n                                            <select id=\"selectBillingCycle\" name=\"billing_cycle\" class=\"form-control select-inline\">\n                                                ";
    foreach ((new WHMCS\Billing\Cycles())->getRecurringCycles() as $cycle => $billingCycle) {
        $lang = AdminLang::trans("billingcycles." . $cycle);
        echo "<option value=\"" . $billingCycle . "\">" . $lang . "</option>";
    }
    echo "                                            </select>\n                                        </div>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </td>\n                </tr>\n            </tfoot>\n\n        </table>\n    </div>\n    <div id=\"divAccountsToTerminate\" class=\"hidden\">\n        <table id=\"tableToTerminate\" class=\"table table-bordered table-striped\">\n            <caption>\n                The following records will be set to Terminated status within WHMCS.\n            </caption>\n            <tr>\n                <th>Service ID</th>\n                <th>";
    echo $uniqueIdDisplayName;
    echo "</th>\n                <th>Primary IP</th>\n                <th>Username</th>\n                <th>Product</th>\n                <th>Status</th>\n                <th>Created</th>\n            </tr>\n            <tr class=\"clone-row hidden\">\n                <td class=\"service-id\">-</td>\n                <td class=\"display-name\"></td>\n                <td class=\"primary-ip\"></td>\n                <td class=\"username\"></td>\n                <td class=\"product\"></td>\n                <td class=\"status\"></td>\n                <td class=\"created\"></td>\n            </tr>\n        </table>\n    </div>\n\n</div>\n\n<div class=\"text-right\">\n    <button type=\"button\" class=\"btn btn-link btn-sm btn-check-all\" data-checkbox-container=\"tableServerSync\" data-btn-check-toggle=\"1\" id=\"btnSelectAll\" data-label-text-select=\"";
    echo AdminLang::trans("global.checkall");
    echo "\" data-label-text-deselect=\"";
    echo AdminLang::trans("global.uncheckAll");
    echo "\" style=\"margin:5px 0;\">\n        ";
    echo AdminLang::trans("global.checkall");
    echo "    </button>\n</div>\n";
}
echo "<div class=\"pull-left\">\n    <button type=\"button\" id=\"btnBack\" class=\"btn btn-default\">\n        <i class=\"fas fa-chevron-left\"></i>\n        Back\n    </button>\n</div>\n";
if (!$error) {
    echo "<div class=\"text-right\">\n    <button type=\"submit\" id=\"btnSubmit\" class=\"btn btn-primary";
    echo $continueClass;
    echo "\"";
    echo $continueDisabled;
    echo ">\n        Continue\n        <i class=\"fas fa-chevron-right\"></i>\n    </button>\n</div>\n\n</form>\n";
}
echo "\n<style>\n.table-serversync {\n    border-left: 1px solid #ddd;\n    border-right: 1px solid #ddd;\n}\n.table-serversync .divider {\n    border-right: 1px solid #ddd;\n}\n.table-serversync th, .table-serversync td {\n    text-align: center;\n}\n.table-serversync tr.matched td {\n    background-color: #ddffdd;\n}\n.table-serversync tr.matched.exact td {\n    background-color: #97f295;\n}\n.table-serversync tr.matched.dupe td {\n    background-color: #ffff99;\n}\n.table-serversync tr.matched.dupe.terminated td {\n    background-color: #ffb6ba;\n}\n.table-serversync tr.matched:not(.dupe) span {\n    display: inline-block;\n    padding: 0 1px;\n    background-color: #97f295;\n}\n.table-serversync tr.matched span.e {\n    background-color: #ffb6ba;\n}\n.table-serversync tr.nomodulematch td {\n    background-color: #fee8e9;\n}\n.table-serversync tr.nomodulematch span {\n    display: inline-block;\n    padding: 0 1px;\n    background-color: #ffb6ba;\n}\n</style>\n<script>\n    jQuery(document).ready(function(){\n        jQuery('#frmAccounts').on('submit', function(e) {\n            var confirmChoiceDiv = jQuery('#divConfirmChoices');\n            if (confirmChoiceDiv.is(':visible')) {\n                return true;\n            }\n            e.preventDefault();\n            var checkedBoxes = jQuery('input[type=\"checkbox\"]:checked').length\n            if (checkedBoxes === 0) {\n                return false;\n            }\n\n            jQuery('#divConfirmChoices tr.confirm-row').remove();\n            jQuery('input[name=\"import[]\"]:checked,input[name=\"sync[]\"]:checked,input[name=\"terminate[]\"]:checked').each(function(){\n                var inputName = jQuery(this).attr('name'),\n                    nameString = inputName.substr(0, inputName.length - 2),\n                    divName = jQuery('#divAccountsTo' + nameString[0].toUpperCase() + nameString.slice(1)),\n                    cloneRow = jQuery(divName).find('.clone-row'),\n                    clone = cloneRow.clone(),\n                    rowData = jQuery(this).closest('tr'),\n                    additionalActions = jQuery('#additionalActions');\n\n                if (nameString === 'import' && additionalActions.hasClass('hidden')) {\n                    additionalActions.removeClass('hidden');\n                }\n                if (this.checked) {\n                    clone.removeClass('clone-row hidden').addClass('confirm-row');\n                    clone.find('.service-id').html(rowData.find('.service-id').html()).end();\n                    clone.find('.display-name').html(rowData.find('.display-name').html()).end();\n                    clone.find('.primary-ip').html(rowData.find('.primary-ip').html()).end();\n                    clone.find('.username').html(rowData.find('.username').html()).end();\n                    clone.find('.product').html(rowData.find('.product').html()).end();\n                    clone.find('.status').html(rowData.find('.status').html()).end();\n                    clone.find('.created').html(rowData.find('.created').html()).end();\n                    cloneRow.before(clone);\n                }\n                var rowCount = (divName.find('tr').length - 2);\n                if (divName.hasClass('hidden') && rowCount > 0) {\n                    divName.removeClass('hidden');\n                }\n                if (!(divName.hasClass('hidden')) && rowCount === 0) {\n                    divName.addClass('hidden');\n                }\n            });\n\n            jQuery('#tableServerSync').hide();\n            jQuery('.step-label.review').removeClass('hidden').show();\n            jQuery('.step-label.analyse').hide();\n            confirmChoiceDiv.removeClass('hidden').show();\n        });\n        jQuery('#btnBack').on('click', function() {\n            var confirmChoiceDiv = jQuery('#divConfirmChoices'),\n                additionalActions = jQuery('#additionalActions');\n            if (confirmChoiceDiv.is(':visible')) {\n                confirmChoiceDiv.hide();\n                jQuery('#tableServerSync').show();\n                jQuery('.step-label.review').hide();\n                jQuery('.step-label.analyse').hide();\n                additionalActions.addClass('hidden');\n            } else {\n                window.location = 'configservers.php';\n            }\n        });\n        jQuery('#toggleBilling').on('switchChange.bootstrapSwitch', function(event, state) {\n            var additionalBillingOptions = jQuery('#additionalBillingOptions');\n            if (state) {\n                if (additionalBillingOptions.hasClass('hidden')) {\n                    additionalBillingOptions.hide('fast').removeClass('hidden');\n                }\n                if (additionalBillingOptions.not(':visible')) {\n                    additionalBillingOptions.slideDown('fast');\n                }\n            } else {\n                if (additionalBillingOptions.is(':visible')) {\n                    additionalBillingOptions.slideUp('fast');\n                }\n            }\n        });\n        jQuery('#toggleClientWelcome').on('switchChange.bootstrapSwitch', function(event, state) {\n            var dropdown = jQuery('#dropdownClientWelcomeEmail');\n            if (state) {\n                dropdown.prop('disabled', false).removeClass('disabled');\n            } else {\n                dropdown.prop('disabled', 'disabled').addClass('disabled');\n            }\n        });\n        WHMCS.form.register();\n    });\n</script>\n";

?>