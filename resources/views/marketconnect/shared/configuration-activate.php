<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div id=\"activate-simple\" class=\"activate\">\n    <h3>Get Started Quickly with our automated setup</h3>\n    <ul>\n        <li><i class=\"fas fa-check text-success\"></i> ";
echo $firstBulletPoint;
echo "</li>\n        <li><i class=\"fas fa-check text-success\"></i> Setup with default recommended retail pricing (you can change this later)</li>\n        <li><i class=\"fas fa-check text-success\"></i> Make available for all hosting products</li>\n        <li>\n            <i class=\"fas fa-check text-success\"></i> Activate promotion and upsells within the client area (<a href=\"https://docs.whmcs.com/MarketConnect_Promotions_and_Upsells\">Learn more...</a>)\n        </li>\n        <li>\n            <i class=\"fas fa-check text-success\"></i> Create landing promotion page (<a href=\"";
$path = routePath($landingPageRoutePath);
echo $path . (strpos($path, "?") ? "&" : "?") . "preview=1";
echo "\" target=\"_blank\">Preview</a>)\n        </li>\n    </ul>\n    <div class=\"activate-btn-container\">\n        <button type=\"button\" class=\"btn btn-success btn-activate\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\">Activate Now</button>\n    </div>\n    <p><strong>Or</strong> switch to <a href=\"#\" class=\"do-advanced-setup-mode\">Advanced Mode</a></p>\n</div>\n<div id=\"activate-advanced\" class=\"activate advanced-mode hidden\">\n    <div class=\"steps\">\n        <a class=\"mode-label\">\n            Advanced Mode\n        </a>\n        <a href=\"#activate-advanced-products\" aria-controls=\"activate-advanced-products\" role=\"tab\" data-toggle=\"tab\" class=\"active\">\n            <div class=\"badge\">\n                <div class=\"border\"></div>\n                <div class=\"number\">1</div>\n            </div>\n            Choose Products\n        </a>\n        <a href=\"#activate-advanced-promos\" aria-controls=\"activate-advanced-promos\" role=\"tab\" data-toggle=\"tab\">\n            <div class=\"badge\">\n                <div class=\"border\"></div>\n                <div class=\"number\">2</div>\n            </div>\n            Configure Promos\n        </a>\n        <a href=\"#activate-advanced-finish\" aria-controls=\"activate-advanced-finish\" role=\"tab\" data-toggle=\"tab\">\n            <div class=\"badge\">\n                <div class=\"border\"></div>\n                <div class=\"number\">3</div>\n            </div>\n            Finish\n        </a>\n    </div>\n    <div class=\"tab-content\">\n        <div id=\"activate-advanced-products\" class=\"active tab-pane\" role=\"tabpanel\">\n            ";
$this->insert("shared/configuration-products", array("currency" => $currency, "billingCycles" => $billingCycles, "products" => $products, "serviceTerms" => $serviceTerms, "isActivationForm" => true));
echo "        </div>\n        <div id=\"activate-advanced-promos\" class=\"tab-pane\" role=\"tabpanel\">\n            ";
$this->insert("shared/configuration-promotions", array("serviceOffering" => $serviceOffering, "service" => $service, "isActivationForm" => true));
echo "        </div>\n        <div id=\"activate-advanced-finish\" class=\"tab-pane\" role=\"tabpanel\">\n            ";
$this->insert("shared/configuration-general-settings", array("serviceOffering" => $serviceOffering, "service" => $service, "isActivationForm" => true));
echo "        </div>\n    </div>\n    <div class=\"activate-btn-container\">\n        <p class=\"pull-left\"><strong>Or</strong> switch to <a href=\"#\" class=\"do-simple-setup-mode\">Simple Mode</a></p>\n        <button type=\"button\" class=\"btn btn-default btn-next pull-right\">\n            Next\n            <i class=\"fas fa-chevron-right\"></i>\n        </button>\n        <button type=\"button\" class=\"btn btn-success btn-activate btn-activate-advanced hidden\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\" style=\"margin-top:-10px;\">\n            Finish & Activate\n        </button>\n    </div>\n</div>\n";

?>