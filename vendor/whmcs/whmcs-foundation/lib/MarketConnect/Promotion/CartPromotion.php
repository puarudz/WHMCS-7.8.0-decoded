<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion;

class CartPromotion extends Promotion
{
    protected function getTemplate()
    {
        $orderFormTemplate = \WHMCS\View\Template\OrderForm::factory("marketconnect-promo.tpl");
        return $orderFormTemplate->getTemplatePath() . "marketconnect-promo.tpl";
    }
}

?>