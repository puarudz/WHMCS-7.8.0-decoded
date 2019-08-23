<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\Controller;

class DomainController
{
    public function pricing(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $currency = getCurrency(\WHMCS\Session::get("uid"), \WHMCS\Session::get("currency"));
        $view = new \WHMCS\ClientArea();
        $view->setTemplate("domain-pricing");
        $view->setPageTitle(\Lang::trans("domainspricing"));
        $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"))->addToBreadCrumb(routePath("/domain/pricing"), \Lang::trans("domainspricing"));
        $templateVariables = array();
        $sessionCurrency = \WHMCS\Session::get("currency");
        $currency = getCurrency($view->getUserId(), $sessionCurrency);
        $templateVariables["activeCurrency"] = $currency;
        $pricing = localAPI("GetTldPricing", array("clientid" => $view->getUserId(), "currencyid" => $currency["id"]));
        $templateVariables["pricing"] = $pricing["pricing"];
        foreach ($templateVariables["pricing"] as $tld => &$priceData) {
            foreach (array("register", "transfer", "renew") as $action) {
                foreach ($priceData[$action] as $term => &$price) {
                    $price = new \WHMCS\View\Formatter\Price($price, $currency);
                }
            }
        }
        unset($price);
        unset($priceData);
        $extensions = array_keys($pricing["pricing"]) ?: array();
        $featuredTlds = array();
        $spotlights = getSpotlightTldsWithPricing();
        foreach ($spotlights as $spotlight) {
            if (file_exists(ROOTDIR . "/assets/img/tld_logos/" . $spotlight["tldNoDots"] . ".png")) {
                $featuredTlds[] = $spotlight;
            }
        }
        $templateVariables["featuredTlds"] = $featuredTlds;
        try {
            $tldCategories = \WHMCS\Domain\TopLevel\Category::whereHas("topLevelDomains", function (\Illuminate\Database\Eloquent\Builder $query) use($extensions) {
                $query->whereIn("tld", $extensions);
            })->with("topLevelDomains")->tldsIn($extensions)->orderBy("is_primary", "desc")->orderBy("display_order")->orderBy("category")->get();
        } catch (\Exception $e) {
            $tldCategories = array();
        }
        $categoryCounts = array();
        foreach ($pricing["pricing"] as $extension => $price) {
            foreach ($price["categories"] as $category) {
                $categoryCounts[$category]++;
            }
        }
        $categoriesWithCounts = array();
        foreach ($tldCategories->pluck("category") as $category) {
            $categoriesWithCounts[$category] = $categoryCounts[$category];
        }
        if (array_key_exists("Other", $categoryCounts)) {
            $categoriesWithCounts["Other"] = $categoryCounts["Other"];
        }
        $templateVariables["tldCategories"] = $categoriesWithCounts;
        $view->setTemplateVariables($templateVariables);
        return $view;
    }
    public function sslCheck(\WHMCS\Http\Message\ServerRequest $request)
    {
        $domain = $request->get("domain");
        $userId = \WHMCS\Session::get("uid");
        \WHMCS\Session::release();
        $type = $request->get("type", "service");
        if (!in_array($type, array("domain", "service"))) {
            $type = "service";
        }
        $table = "tblhosting";
        $statusField = "domainstatus";
        if ($type == "domain") {
            $table = "tbldomains";
            $statusField = "status";
        }
        $activeDomain = \WHMCS\Database\Capsule::table($table)->where("domain", $domain)->where("userid", $userId)->whereIn($statusField, array("Active", "Completed", "Grace"))->pluck("id");
        if ($activeDomain) {
            $sslStatus = \WHMCS\Domain\Ssl\Status::factory($userId, $domain)->syncAndSave();
            $response = array("image" => $sslStatus->getImagePath(), "tooltip" => $sslStatus->getTooltipContent(), "class" => $sslStatus->getClass());
        } else {
            $response = array("invalid" => true);
        }
        return new \WHMCS\Http\Message\JsonResponse($response);
    }
}

?>