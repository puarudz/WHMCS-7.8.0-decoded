<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<h3>Client Area</h3>\n\n<div class=\"promotions\">\n    <div class=\"row\">\n        <div class=\"col-sm-6\">\n            <div class=\"promo\">\n                <h4>Homepage <input type=\"checkbox\" class=\"promo-switch\" data-promo=\"client-home\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo is_null($service) || is_null($service->setting("promotion.client-home")) && $isActivationForm || $service->setting("promotion.client-home") ? " checked" : "";
echo "></h4>\n                <p>Promotes to customers who don't yet have the service</p>\n            </div>\n        </div>\n        <div class=\"col-sm-6\">\n            <div class=\"promo\">\n                <h4>Product Details <input type=\"checkbox\" class=\"promo-switch\" data-promo=\"product-details\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo is_null($service) || is_null($service->setting("promotion.client-home")) && $isActivationForm || $service->setting("promotion.product-details") ? " checked" : "";
echo "></h4>\n                <p>Promote on products where the service is not an active add-on</p>\n            </div>\n        </div>\n        <div class=\"col-sm-6\">\n            <div class=\"promo\">\n                <h4>Product List <input type=\"checkbox\" class=\"promo-switch\" data-promo=\"product-list\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo is_null($service) || is_null($service->setting("promotion.client-home")) && $isActivationForm || $service->setting("promotion.product-list") ? " checked" : "";
echo "></h4>\n                <p>Promote in the sidebar of the Products/Services list</p>\n            </div>\n        </div>\n    </div>\n</div>\n\n<h3>Shopping Cart</h3>\n\n<div class=\"promotions\">\n    <div class=\"row\">\n        <div class=\"col-sm-6\">\n            <div class=\"promo\">\n                <h4>View Cart <input type=\"checkbox\" class=\"promo-switch\" data-promo=\"cart-view\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo is_null($service) || is_null($service->setting("promotion.client-home")) && $isActivationForm || $service->setting("promotion.cart-view") ? " checked" : "";
echo "></h4>\n                <p>Promotes to customers who don't yet have the service in their cart</p>\n            </div>\n        </div>\n        <div class=\"col-sm-6\">\n            <div class=\"promo\">\n                <h4>Checkout <input type=\"checkbox\" class=\"promo-switch\" data-promo=\"cart-checkout\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\"";
echo is_null($service) || is_null($service->setting("promotion.client-home")) && $isActivationForm || $service->setting("promotion.cart-checkout") ? " checked" : "";
echo "></h4>\n                <p>Promote during checkout of the cart when service not included</p>\n            </div>\n        </div>\n    </div>\n</div>\n";

?>