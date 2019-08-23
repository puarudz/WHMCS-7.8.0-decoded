<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<ul class=\"products";
echo !empty($isActivationForm) ? " activation" : "";
echo "\">\n";
foreach ($products as $product) {
    echo "    <li class=\"product\">\n        <div class=\"content-padded\">\n            <div class=\"row\">\n                <div class=\"col-sm-8 product-name\">\n                    <a href=\"#\" class=\"toggle-pricing\">\n                        &nbsp;&nbsp;\n                        <i class=\"fas fa-chevron-right\"></i>\n                        &nbsp;\n                        ";
    echo $product->name;
    echo "                    </a>\n                </div>\n                <div class=\"col-sm-2\">\n                    <input type=\"checkbox\" class=\"product-status\" data-productkey=\"";
    echo $product->productKey;
    echo "\"";
    echo $isActivationForm || !$product->isHidden ? " checked " : "";
    echo ">\n                </div>\n                <div class=\"col-sm-2 text-right\">\n                    <div class=\"btn-group\">\n                        <button type=\"button\" class=\"btn btn-manage dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n                            <i class=\"fas fa-cog\"></i> <span class=\"caret\"></span>\n                        </button>\n                        <ul class=\"dropdown-menu dropdown-menu-right\">\n                            <li><a href=\"#\" class=\"btn-manage-pricing\">Manage Pricing</a></li>\n                        </ul>\n                    </div>\n                </div>\n            </div>\n        </div>\n        <div style=\"background-color:#eaf5ff;padding:10px;\" class=\"pricing hidden\">\n            <form>\n                <input type=\"hidden\" name=\"productkey\" value=\"";
    echo $product->productKey;
    echo "\">\n                <table class=\"table table-pricing\">\n                    <tr>\n                        <th>Cycle</th>\n                        <th>Enabled</th>\n                        <th>Your Cost</th>\n                        <th>Recommended<br>Selling Price</th>\n                        <th width=\"150\">Your Selling<br>Price (";
    echo $currency["code"];
    echo ")</th>\n                    </tr>\n                    ";
    foreach ($serviceTerms[$product->productKey] as $term) {
        echo "                        <tr>\n                            <td>";
        echo $billingCycles->getNameByMonths($term["term"]);
        echo "</td>\n                            <td><input type=\"checkbox\" name=\"enabled[";
        echo $term["term"];
        echo "]\" value=\"1\" data-product-key=\"";
        echo $product->productKey;
        echo "\"\n                                    ";
        if (!$product->exists) {
            $isEnabled = " checked";
        } else {
            $storedPricing = $product->pricing($currency)->months($term["term"]);
            if (!is_null($storedPricing)) {
                $isEnabled = " checked";
            } else {
                if ($term["term"] == WHMCS\MarketConnect\MarketConnect::PRICING_TERM_FREE) {
                    $isEnabled = " checked";
                } else {
                    $isEnabled = "";
                }
            }
        }
        echo $isEnabled;
        echo "                                / ></td>\n                            <td>\n                                \$";
        echo number_format(format_as_currency($term["price"]), 2);
        echo "<br>\n                                <small>";
        echo 0 < $term["term"] ? "\$" . number_format(format_as_currency($term["price"] / $term["term"]), 2) . " per month" : "";
        echo "</small>\n                            </td>\n                            <td>\n                                \$";
        echo number_format(format_as_currency($term["recommendedRrp"]), 2);
        echo "<br>\n                                <small>";
        echo 0 < $term["term"] ? "\$" . number_format(format_as_currency($term["recommendedRrp"] / $term["term"]), 2) . " per month" : "";
        echo "</small>\n                            </td>\n                            <td><input type=\"text\" name=\"price[";
        echo $term["term"];
        echo "]\" class=\"form-control price-field\"\n                                ";
        if ($term["term"] == WHMCS\MarketConnect\MarketConnect::PRICING_TERM_FREE) {
            echo "disabled=\"disabled\"";
        }
        if (!$product->exists) {
            $value = format_as_currency($term["recommendedRrpDefaultCurrency"]);
        } else {
            if ($isEnabled && $term["term"] != WHMCS\MarketConnect\MarketConnect::PRICING_TERM_FREE) {
                $value = $product->pricing($currency)->months($term["term"])->price()->toNumeric();
            } else {
                $value = format_as_currency($term["recommendedRrpDefaultCurrency"]);
            }
        }
        echo sprintf(" value=\"%s\"", $value);
        echo " data-default-price=\"";
        echo format_as_currency($term["recommendedRrpDefaultCurrency"]);
        echo "\"></td>\n                        </tr>\n                    ";
    }
    echo "                </table>\n                ";
    if (!empty($isActivationForm)) {
        echo "<input type=\"hidden\" name=\"isActivationForm\" value=\"1\"/>";
    }
    echo "            </form>\n            <button type=\"submit\" class=\"btn btn-link btn-sm btn-restore-default-pricing\">Restore Defaults</button>\n            ";
    if (!$isActivationForm) {
        echo "            <button type=\"submit\" class=\"btn btn-default btn-sm pull-right btn-save-pricing\">Save Changes</button>\n            ";
    }
    echo "        </div>\n    </li>\n";
}
echo "</ul>\n";

?>