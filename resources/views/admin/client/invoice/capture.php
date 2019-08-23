<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form action=\"";
echo routePath("admin-client-invoice-capture-confirm", $client->id, $invoice->id);
echo "\">\n    ";
if (0 < count($payMethods)) {
    echo "        <div class=\"form-group bottom-margin-10 text-center\">\n            ";
    echo AdminLang::trans("payments.capturePayment");
    echo "        </div>\n        <div class=\"row\">\n            <div class=\"form-group col-sm-8\">\n                <label for=\"selectCard\">";
    echo AdminLang::trans("fields.selectCard");
    echo "</label>\n                <select id=\"selectCard\" name=\"paymentId\" class=\"form-control\">\n                    ";
    foreach ($payMethods as $payMethod) {
        $selected = "";
        $default = "";
        if ($payMethod->isDefaultPayMethod()) {
            $selected = " selected=\"selected\"";
            $default = " (" . AdminLang::trans("global.default") . ")";
        }
        echo "<option value=\"" . $payMethod->id . "\"" . $selected . ">";
        echo $payMethod->payment->getDisplayName();
        if ($payMethod->payment instanceof WHMCS\Payment\Contracts\CreditCardDetailsInterface && $payMethod->payment->getExpiryDate()) {
            echo " - " . $payMethod->payment->getExpiryDate()->toCreditCard();
        }
        if ($payMethod->description) {
            echo " - " . $payMethod->description;
        }
        echo $default;
        echo "</option>";
    }
    echo "                </select>\n            </div>\n            <div class=\"form-group col-sm-4\">\n                <label for=\"cardcvv\">\n                    ";
    echo AdminLang::trans("fields.cardcvv");
    echo "                </label>\n                <input type=\"number\"\n                       id=\"cardcvv\"\n                       name=\"cardcvv\"\n                       class=\"form-control\"\n                       autocomplete=\"off\"\n                       maxlength=\"4\"\n                       placeholder=\"123 (";
    echo AdminLang::trans("global.optional");
    echo ")\"\n                />\n            </div>\n        </div>\n    ";
} else {
    echo "        <p class=\"top-margin-10\">No Credit Card Details are stored for this client so the capture could not be attempted.</p>\n    ";
}
echo "\n</form>\n<script type=\"text/javascript\">\n    // Make the button green\n    jQuery(document).ready(function() {\n        var btn = jQuery('#btnAttemptCapture');\n        btn.toggleClass('btn-primary btn-success');\n        ";
if (count($payMethods) == 0) {
    echo "        btn.remove();\n        ";
}
echo "    });\n</script>\n";

?>