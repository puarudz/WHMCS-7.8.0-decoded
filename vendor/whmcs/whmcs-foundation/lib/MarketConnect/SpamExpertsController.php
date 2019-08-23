<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class SpamExpertsController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $isAdminPreview = \App::getFromRequest("preview") && \WHMCS\Session::get("adminid");
        if (!$isAdminPreview) {
            $service = Service::where("name", "spamexperts")->first();
            if (is_null($service) || !$service->status) {
                return new \Zend\Diactoros\Response\RedirectResponse("index.php");
            }
        }
        $ca = new \WHMCS\ClientArea();
        $ca->setPageTitle(\Lang::trans("store.emailServices.title"));
        $ca->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $ca->addToBreadCrumb(routePath("store"), \Lang::trans("navStore"));
        $ca->addToBreadCrumb(routePath("store-emailservices-index"), \Lang::trans("store.emailServices.title"));
        $ca->initPage();
        $all = \WHMCS\Product\Product::spamexperts()->visible()->get();
        $sessionCurrency = \WHMCS\Session::get("currency");
        $currency = getCurrency($ca->getUserId(), $sessionCurrency);
        $ca->assign("activeCurrency", $currency);
        $products = array();
        foreach (array("incoming", "incomingarchiving", "outgoing", "outgoingarchiving", "incomingoutgoing", "incomingoutgoingarchiving") as $productKey) {
            $products[$productKey] = $all->where("configoption1", "spamexperts_" . $productKey)->first();
            if (is_null($products[$productKey])) {
                continue;
            }
            $products[$productKey]->pricing($currency);
        }
        $upgradeOptions = array("incoming" => array(array("product" => "incomingoutgoing", "label" => "Add Outgoing Filtering"), array("product" => "incomingarchiving", "label" => "Add Incoming Archiving"), array("product" => "incomingoutgoingarchiving", "label" => "Add Outgoing Filtering & Archiving")), "outgoing" => array(array("product" => "incomingoutgoing", "label" => "Add Incoming Filtering"), array("product" => "outgoingarchiving", "label" => "Add Outgoing Archiving"), array("product" => "incomingoutgoingarchiving", "label" => "Add Incoming Filtering & Archiving")));
        $options = array("incoming" => array(), "outgoing" => array());
        foreach ($upgradeOptions as $type => $upgrades) {
            foreach ($upgrades as $upgrade) {
                if ($products[$type] && $products[$upgrade["product"]]) {
                    $bundlePricing = $products[$upgrade["product"]]->pricing($currency);
                    $singlePricing = $products[$type]->pricing($currency);
                    if ($bundlePricing->monthly() && $singlePricing->monthly()) {
                        $bundlePriceNum = (double) $bundlePricing->monthly()->price()->toNumeric();
                        $singlePriceNum = (double) $singlePricing->monthly()->price()->toNumeric();
                        $pricing = new \WHMCS\Product\Pricing\Price(array("price" => new \WHMCS\View\Formatter\Price($bundlePriceNum - $singlePriceNum, $currency), "cycle" => "monthly", "currency" => $currency));
                    } else {
                        if ($bundlePricing->annually() && $singlePricing->annually()) {
                            $bundlePriceNum = (double) $bundlePricing->annually()->price()->toNumeric();
                            $singlePriceNum = (double) $singlePricing->annually()->price()->toNumeric();
                            $pricing = new \WHMCS\Product\Pricing\Price(array("price" => new \WHMCS\View\Formatter\Price($bundlePriceNum - $singlePriceNum, $currency), "cycle" => "annually", "currency" => $currency));
                        } else {
                            $pricing = null;
                        }
                    }
                    if ($pricing) {
                        $options[$type][] = array("product" => $upgrade["product"], "description" => $upgrade["label"], "pricing" => $pricing);
                    }
                }
            }
        }
        $numberOfFeaturedProducts = 0;
        foreach (array("incoming", "outgoing") as $productKey) {
            if (!is_null($products[$productKey])) {
                $numberOfFeaturedProducts++;
            }
        }
        if (!is_null($products["incomingarchiving"]) || !is_null($products["outgoingarchiving"]) || !is_null($products["incomingoutgoingarchiving"])) {
            $numberOfFeaturedProducts++;
        }
        $domains = \WHMCS\Service\Service::where("userid", $ca->getUserId())->where("domain", "!=", "")->where("domainstatus", "Active")->pluck("domain");
        $domainRegistrations = \WHMCS\Domain\Domain::where("userid", $ca->getUserId())->where("domain", "!=", "")->where("status", "Active")->pluck("domain");
        $ca->assign("products", $products);
        $ca->assign("productOptions", $options);
        $ca->assign("numberOfFeaturedProducts", $numberOfFeaturedProducts);
        $ca->assign("domains", $domains->merge($domainRegistrations)->unique());
        $ca->assign("inPreview", $isAdminPreview);
        $ca->setTemplate("store/spamexperts/index");
        $ca->skipMainBodyContainer();
        return $ca;
    }
}

?>