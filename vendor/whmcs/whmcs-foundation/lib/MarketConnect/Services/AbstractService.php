<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

abstract class AbstractService implements ServiceInterface
{
    public abstract function provision($model, array $params);
    public abstract function configure($model, array $params);
    public abstract function cancel($model);
    public function renew($model, array $params = NULL)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        $term = marketconnect_DetermineTerm($params);
        $api = new \WHMCS\MarketConnect\Api();
        $response = $api->renew($orderNumber, $term);
        $model->serviceProperties->save(array("Order Number" => $response["order_number"]));
    }
    public function install($model)
    {
    }
    public function adminManagementButtons($params)
    {
        return array();
    }
    public function adminServicesTabOutput(array $params, \WHMCS\MarketConnect\OrderInformation $orderInfo = NULL, array $actionBtns = NULL)
    {
        $userId = $params["userid"];
        $serviceId = $params["serviceid"];
        $addonId = $params["addonId"];
        $orderInformationOutput = "";
        if (is_array($orderInfo->additionalInformation)) {
            foreach ($orderInfo->additionalInformation as $label => $value) {
                $label = preg_split("/(?=[A-Z])/", $label);
                $label = array_map("ucfirst", $label);
                $orderInformationOutput .= "<div class=\"row\"><div class=\"col-sm-4 field-label\">" . implode(" ", $label) . "</div><div class=\"col-sm-8\">\n                    " . (0 < strlen($value) ? htmlspecialchars($value) : "-") . "\n                </div></div>";
            }
        }
        $actionBtnsOutput = "";
        if (is_array($actionBtns)) {
            foreach ($actionBtns as $button) {
                $class = "btn btn-default";
                $href = isset($button["href"]) ? $button["href"] : "?userid=" . $userId . "&id=" . $serviceId;
                if ($addonId) {
                    $href .= "&aid=" . $addonId;
                }
                if (isset($button["moduleCommand"])) {
                    $href .= "&modop=custom&ac=" . $button["moduleCommand"];
                }
                $modalOptions = "";
                if (isset($button["modal"]) && is_array($button["modal"])) {
                    $options = $button["modal"];
                    $modalTitle = isset($options["title"]) ? $options["title"] : "";
                    $modalClass = isset($options["class"]) ? $options["class"] : "";
                    $modalSize = isset($options["size"]) ? $options["size"] : "";
                    $submitLabel = isset($options["submitLabel"]) ? $options["submitLabel"] : "";
                    $submitId = isset($options["submitId"]) ? $options["submitId"] : "";
                    $class .= " open-modal";
                    $href .= generate_token("link");
                    $modalOptions = " data-modal-title=\"" . $modalTitle . "\" data-modal-size=\"" . $modalSize . "\" data-modal-class=\"" . $modalClass . "\"" . ($submitLabel ? " data-btn-submit-label=\"" . $submitLabel . "\" data-btn-submit-id=\"" . $submitId . "\"" : "");
                }
                $disabled = "";
                if (!in_array($orderInfo->status, $button["applicableStatuses"])) {
                    $disabled = " disabled=\"disabled\"";
                }
                $actionBtnsOutput .= "<a href=\"" . $href . "\" class=\"" . $class . "\"" . $modalOptions . $disabled . ">\n                <i class=\"fas " . $button["icon"] . " fa-fw\"></i>\n                " . $button["label"] . "\n            </a>" . PHP_EOL;
            }
        }
        $disabled = "";
        if ($orderInfo->status && in_array($orderInfo->status, array("Cancelled", "Refunded", "Order not found"))) {
            $disabled = " disabled=\"disabled\"";
        }
        $actionBtnsOutput .= "<button type=\"button\" class=\"btn btn-default btn-cancel\" id=\"btnMcCancelOrder\"" . $disabled . ">\n                <i class=\"fas fa-times fa-fw\"></i>\n                Cancel\n            </button>" . PHP_EOL;
        $js = "";
        if ($orderInfo->orderNumber && $orderInfo->isCacheStale()) {
            $js = "<script>\n    \$(document).ready(function() {\n        \$('#btnMcServiceRefresh').click();\n    });\n</script>";
        }
        $lastUpdated = \AdminLang::trans("global.never");
        if ($orderInfo && $orderInfo->getLastUpdated()) {
            $lastUpdated = $orderInfo->getLastUpdated();
        }
        return array("" => "<div class=\"mc-smwrapper\" id=\"mcServiceManagementWrapper\">\n    <div class=\"mc-sm-container\">\n        <h3>\n            Service Management\n            <a href=\"userid=" . $userId . "&id=" . $serviceId . "&aid=" . $addonId . "&modop=custom&ac=refreshStatus\" class=\"btn btn-default btn-sm pull-right btn-refresh\" id=\"btnMcServiceRefresh\">\n                <i class=\"fas fa-sync\"></i>\n            </a>\n            <span>\n                Last Updated: " . $lastUpdated . "\n            </span>\n        </h3>\n\n        <div class=\"detailed-order-status\">\n            <div class=\"row\">\n                <div class=\"col-sm-4 field-label\">Marketplace Order Number</div>\n                <div class=\"col-sm-8\">\n                    " . ($orderInfo->orderNumber ? $orderInfo->orderNumber : "-") . "\n                </div>\n            </div>\n            <div class=\"row\">\n                <div class=\"col-sm-4 field-label\">Associated Domain</div>\n                <div class=\"col-sm-8\">\n                    " . ($orderInfo->domain ? $orderInfo->domain : "-") . "\n                </div>\n            </div>\n            " . $orderInformationOutput . "\n            <div class=\"row\">\n                <div class=\"col-sm-4 field-label\">Order Status</div>\n                <div class=\"col-sm-8\">\n                    <span class=\"status " . str_replace(" ", "", strtolower($orderInfo->status)) . "\">" . ($orderInfo->status ? $orderInfo->status : ($orderInfo->orderNumber ? "Refreshing..." : "Not Yet Provisioned")) . "</span>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"actions\">\n            " . $actionBtnsOutput . "\n        </div>\n\n        <div class=\"addt-info\">\n            <strong>Status Description</strong><br>\n            " . ($orderInfo->statusDescription ? $orderInfo->statusDescription : "You must accept/create this order to provision the service ready for use.") . "\n        </div>\n    </div>\n</div>" . PHP_EOL . $js);
    }
    public function emailMergeData(array $params, array $passedData = array())
    {
        return array();
    }
    public function isSslProduct()
    {
        return false;
    }
}

?>