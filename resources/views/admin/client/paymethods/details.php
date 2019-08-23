<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (empty($payMethodType) && $payMethod) {
    $payMethodType = $payMethod->getType();
}
$type = strtolower($payMethodType);
$hasDetails = $type !== "remotebankaccount";
$inactiveGateway = "";
if (isset($payMethod) && $payMethod->isUsingInactiveGateway()) {
    $inactiveGateway = AdminLang::trans("clientsummary.inactiveGatewayRemoteToken");
}
if ($inactiveGateway) {
    echo "<div class=\"alert alert-info\">" . $inactiveGateway . "</div>";
}
echo "<div class=\"alert alert-danger admin-modal-error\" style=\"display: none\">\n</div>\n<form id=\"frmCreditCardDetails\" class=\"form\" method=\"POST\" action=\"";
echo $actionUrl;
echo "\">\n    <input type=\"hidden\" name=\"payMethodId\" value=\"";
echo $payMethod ? $payMethod->id : "";
echo "\"/>\n    <input type=\"hidden\" name=\"payMethodType\" value=\"";
echo $payMethodType;
echo "\"/>\n    <div class=\"row\">\n        <div class=\"col-sm-12\">\n            <div class=\"alert alert-danger text-center gateway-errors hidden\"></div>\n        </div>\n    </div>\n    <div class=\"row\">\n        ";
if ($remoteInput) {
    echo "            <div class=\"col-sm-12\">\n                ";
    echo $remoteInput;
    echo "            </div>\n        ";
} else {
    if ($hasDetails) {
        echo "        <div class=\"col-sm-6\">\n            ";
        $this->insert("client/paymethods/partials/details-" . $type);
        echo "        </div>\n        ";
    }
    echo "        <div class=\"col-sm-";
    echo $hasDetails ? "6" : "12";
    echo "\">\n            ";
    $this->insert("client/paymethods/partials/details-paymethod-attributes");
    echo "        </div>\n        ";
}
echo "    </div>\n</form>\n\n<script type=\"text/javascript\">\n    var modal = \$('#modalAjax');\n\n    if (!\$(modal).data('remove-payment-delete-btn')) {\n        \$(modal).data('remove-payment-delete-btn', true);\n\n        \$(modal).on('hide.bs.modal', function () {\n            \$('#divDeleteButton').remove();\n        });\n    };\n\n    ";
if (isset($deleteUrl)) {
    echo "    \$('#divDeleteButton').remove();\n    \$('#modalAjaxLoader').before('<div id=\"divDeleteButton\" class=\"pull-left\"><a class=\"btn btn-danger delete-paymethod open-modal pull-right\" href=\"";
    echo $deleteUrl;
    echo "\" data-role=\"btn-delete-paymethod\">";
    echo AdminLang::trans("global.delete");
    echo "</a></div>');\n    \$('.delete-paymethod').off('click').on('click', function() {\n        \$('#divDeleteButton').remove();\n    });\n    ";
}
if ($inactiveGateway) {
    echo "        \$('.delete-paymethod').prop('disabled', true).addClass('disabled');\n        \$('#savePaymentMethod').prop('disabled', true)\n            .addClass('disabled');\n    ";
}
echo "</script>\n";

?>