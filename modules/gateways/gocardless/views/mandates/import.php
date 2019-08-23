<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form action=\"";
echo $routePath;
echo "\">\n    <input type=\"hidden\" name=\"action\" value=\"do_import\">\n    <input type=\"hidden\" name=\"mandate_id\" value=\"";
echo $mandateId;
echo "\">\n    <div class=\"row\">\n        <div class=\"col-md-3 text-right\">\n            ";
echo AdminLang::trans("clients.search");
echo "        </div>\n        <div class=\"col-md-9\">\n            ";
echo $dropdown->getFormattedBodyContent();
echo "        </div>\n        <div class=\"col-md-3 text-right\">\n            Mandate ID\n        </div>\n        <div class=\"col-md-9\">\n            <input type=\"hidden\" name=\"mandate_id\" value=\"";
echo $mandateId;
echo "\">\n            ";
echo $mandateId;
echo "        </div>\n        <div class=\"col-md-3 text-right\">Mandate Information</div>\n        <div class=\"col-md-9\">\n            ";
if ($customer["company_name"]) {
    echo "                <strong>";
    echo $customer["company_name"];
    echo "</strong><br>\n                <em>";
    echo $customer["given_name"] . " " . $customer["family_name"];
    echo "</em><br>\n            ";
} else {
    echo "                <strong>";
    echo $customer["given_name"] . " " . $customer["family_name"];
    echo "</strong>\n                <br>\n            ";
}
echo "            ";
echo $customer["address_line1"];
echo "<br>\n            ";
if ($customer["address_line2"]) {
    echo "                ";
    echo $customer["address_line2"];
    echo "<br>\n            ";
}
echo "            ";
if ($customer["address_line3"]) {
    echo "                ";
    echo $customer["address_line3"];
    echo "<br>\n            ";
}
echo "            ";
if ($customer["city"]) {
    echo "                ";
    echo $customer["city"];
    echo "<br>\n            ";
}
echo "            ";
if ($customer["region"]) {
    echo "                ";
    echo $customer["region"];
    echo "<br>\n            ";
}
echo "            ";
if ($customer["postal_code"]) {
    echo "                ";
    echo $customer["postal_code"];
    echo "<br>\n            ";
}
echo "            ";
if ($customer["country_code"]) {
    echo "                ";
    echo $customer["country_code"];
    echo "            ";
}
echo "        </div>\n    </div>\n</form>\n";
echo $dropdown->getFormattedHtmlHeadContent();
echo "<script>\n    jQuery(document).ready(function() {\n        var modal = jQuery('#modalAjax');\n\n        if (modal.children('div[class=\"modal-dialog modal-lg\"]').length) {\n            modal.children('div[class=\"modal-dialog modal-lg\"]').removeClass('modal-lg');\n        }\n    });\n</script>\n";

?>