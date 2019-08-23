<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class CodeGuardController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $isAdminPreview = \App::getFromRequest("preview") && \WHMCS\Session::get("adminid");
        if (!$isAdminPreview) {
            $service = Service::where("name", "codeguard")->first();
            if (is_null($service) || !$service->status) {
                return new \WHMCS\ApplicationLink\Server\SingleSignOn\RedirectResponse("index.php");
            }
        }
        $ca = new \WHMCS\ClientArea();
        $ca->setPageTitle(\Lang::trans("store.codeGuard.title"));
        $ca->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $ca->addToBreadCrumb(routePath("store"), \Lang::trans("navStore"));
        $ca->addToBreadCrumb(routePath("store-codeguard-index"), \Lang::trans("store.codeGuard.title"));
        $ca->initPage();
        $products = \WHMCS\Product\Product::codeguard()->visible()->orderBy("order")->get();
        $sessionCurrency = \WHMCS\Session::get("currency");
        $currency = getCurrency($ca->getUserId(), $sessionCurrency);
        $ca->assign("activeCurrency", $currency);
        foreach ($products as $key => $product) {
            $products[$key]->diskSpace = Promotion\Service\CodeGuard::getDiskSpaceFromName($product->name);
            $product->pricing($currency);
        }
        $ca->assign("products", $products);
        $ca->assign("codeGuardFaqs", $this->getFaqs());
        $ca->assign("inPreview", $isAdminPreview);
        $ca->setTemplate("store/codeguard/index");
        $ca->skipMainBodyContainer();
        return $ca;
    }
    protected function getFaqs()
    {
        $faqs = array();
        for ($i = 1; $i <= 9; $i++) {
            $faqs[] = array("question" => \Lang::trans("store.codeGuard.faq.q" . $i), "answer" => \Lang::trans("store.codeGuard.faq.a" . $i));
        }
        return $faqs;
    }
}

?>