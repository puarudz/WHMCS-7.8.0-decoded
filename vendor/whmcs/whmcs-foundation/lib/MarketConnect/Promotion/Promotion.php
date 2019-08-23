<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion;

class Promotion
{
    protected $promotion = NULL;
    protected $product = NULL;
    protected $upsellService = NULL;
    public function __construct(PromotionContentWrapper $promotion, \WHMCS\Product\Product $product, $upsellService = NULL)
    {
        $this->promotion = $promotion;
        $this->product = $product;
        $this->upsellService = $upsellService;
    }
    public function getPromotion()
    {
        return $this->promotion;
    }
    public function getProduct()
    {
        return $this->product;
    }
    public function getUpsellService()
    {
        return $this->upsellService;
    }
    protected function getTemplate()
    {
        $activeTemplate = \WHMCS\Config\Setting::getValue("Template");
        return ROOTDIR . "/templates/" . $activeTemplate . "/store/promos/upsell.tpl";
    }
    protected function getTargetUrl()
    {
        return routePath("store-order");
    }
    protected function getInputParameters()
    {
        $params = array("pid" => $this->getProduct()->id);
        if ($this->getUpsellService() && $this->getUpsellService()->isService()) {
            $params["serviceid"] = $this->getUpsellService()->id;
        }
        return $params;
    }
    public function render()
    {
        try {
            $result = (new \WHMCS\Smarty())->fetch($this->getTemplate(), array("targetUrl" => $this->getTargetUrl(), "inputParameters" => $this->getInputParameters(), "product" => $this->getProduct(), "promotion" => $this->getPromotion(), "upsellService" => $this->getUpsellService()));
        } catch (\WHMCS\Exception $e) {
            $result = "";
        }
        return $result;
    }
    public function __toString()
    {
        return $this->render();
    }
}

?>