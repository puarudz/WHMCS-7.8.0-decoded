<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain;

class Checker
{
    protected $request = NULL;
    protected $lookupProvider = NULL;
    protected $domain = NULL;
    protected $type = "";
    protected $searchResult = array();
    public function __construct(\WHMCS\Domains\DomainLookup\Provider\AbstractProvider $lookupProvider = NULL)
    {
        if (!function_exists("getTLDList")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php";
        }
        if (!function_exists("cartAvailabilityResultsBackwardsCompat")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "cartfunctions.php";
        }
        $this->request = \WHMCS\Http\Message\ServerRequest::fromGlobals();
        $this->lookupProvider = $lookupProvider ?: \WHMCS\Domains\DomainLookup\Provider::factory();
    }
    public function ajaxCheck()
    {
        check_token();
        $this->type = $this->request->get("type", "domain");
        try {
            $source = $this->request->get("source", "");
            if ((!$source || $source != "cartAddDomain") && !in_array($this->type, array("spotlight", "suggestions"))) {
                $this->checkCaptcha();
            }
            $this->prepareAjaxDomain();
            $functionToCall = "check" . ucfirst(strtolower($this->type));
            $this->{$functionToCall}();
            $this->processPremiumDomains();
        } catch (\Exception $e) {
            $this->searchResult = array("error" => $e->getMessage());
        }
        $this->conditionallyReleaseSession();
        $result = $this->searchResult;
        if ($result instanceof \WHMCS\Domains\DomainLookup\ResultsList) {
            $result = $result->toArray();
        }
        $response = new \WHMCS\Http\JsonResponse(array("result" => $result), 200, array("Content-Type" => "application/json"));
        $response->send();
        \WHMCS\Terminus::getInstance()->doExit();
    }
    public function cartDomainCheck(\WHMCS\Domains\Domain $searchDomain, array $tlds)
    {
        $this->domain = $searchDomain;
        $this->searchResult = $this->lookupProvider->checkAvailability($searchDomain, $tlds);
    }
    public function getLookupProvider()
    {
        return $this->lookupProvider;
    }
    public function getSearchResult()
    {
        return $this->searchResult;
    }
    public function populateSuggestionsInSmartyValues(array &$smartyVariables)
    {
        $suggestions = $this->lookupProvider->getSuggestions($this->domain);
        $otherSuggestions = array();
        $smartyVariables["searchResults"]["suggestions"] = array();
        foreach ($suggestions as $suggestion) {
            $smartyVariables["searchResults"]["suggestions"][] = $suggestion->toArray();
            $otherSuggestions[] = array("domain" => $suggestion->getDomain(), "status" => $suggestion->getStatus(), "regoptions" => $suggestion->pricing()->toArray());
        }
        $smartyVariables["othersuggestions"] = $otherSuggestions;
    }
    protected function conditionallyReleaseSession()
    {
        switch ($this->type) {
            case "incart":
            case "owndomain":
            case "subdomain":
                break;
            default:
                \WHMCS\Session::release();
        }
    }
    protected function checkCaptcha()
    {
        if (\WHMCS\Session::get("CaptchaComplete") === true) {
            return NULL;
        }
        $captcha = new \WHMCS\Utility\Captcha();
        if ($captcha->isEnabled() && !$captcha->recaptcha->isInvisible() && \WHMCS\Session::get("CaptchaComplete") !== true) {
            throw new \WHMCS\Exception(\Lang::trans("googleRecaptchaIncorrect"));
        }
        $validate = new \WHMCS\Validate();
        $captcha->validateAppropriateCaptcha(\WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER, $validate);
        if ($validate->hasErrors()) {
            throw new \WHMCS\Exception($validate->getErrors()[0]);
        }
        \WHMCS\Session::set("CaptchaComplete", true);
    }
    protected function processIdnLabel($label)
    {
        $label = \WHMCS\Config\Setting::getValue("AllowIDNDomains") ? mb_strtolower($label) : strtolower($label);
        $label = str_replace(array("'", "+", ",", "|", "!", "\\", "\"", "£", "\$", "%", "&", "/", "(", ")", "=", "?", "^", "*", " ", "°", "§", ";", ":", "_", "<", ">", "]", "[", "@", ")"), "", $label);
        return $label;
    }
    protected function prepareAjaxDomain()
    {
        if ($this->request->has("sld") && $this->request->has("tld")) {
            $sld = $this->processIdnLabel(\WHMCS\Input\Sanitize::decode($this->request->get("sld")));
            $tld = $this->processIdnLabel(\WHMCS\Input\Sanitize::decode($this->request->get("tld")));
            $this->domain = \WHMCS\Domains\Domain::createFromSldAndTld($sld, $tld);
        } else {
            $this->domain = new \WHMCS\Domains\Domain($this->processIdnLabel(\WHMCS\Input\Sanitize::decode($this->request->get("domain"))));
        }
    }
    protected function checkDomain()
    {
        $validate = new \WHMCS\Validate();
        $validate->validate("unique_domain", "unique_domain", "ordererrordomainalreadyexists", "", $this->domain);
        run_validate_hook($validate, "ShoppingCartValidateDomain", array("domainoption" => "register", "sld" => $this->domain->getSecondLevel(), "tld" => $this->domain->getDotTopLevel()));
        if ($validate->hasErrors()) {
            $errors = "";
            foreach ($validate->getErrors() as $error) {
                $errors .= $error . "<br />";
            }
            $this->searchResult = array("error" => $errors);
            return NULL;
        } else {
            $originalTld = $tld = $this->domain->getDotTopLevel();
            $tlds = $this->getTldsList();
            $preferredTLDNotAvailable = false;
            if ($tld == "." || !in_array($tld, $tlds)) {
                if ($tld != ".") {
                    $preferredTLDNotAvailable = true;
                }
                $tld = $tlds[0];
            }
            $this->cartDomainCheck($this->domain, array($tld));
            $searchResult = $this->getSearchResult();
            if ($searchResult instanceof \WHMCS\Domains\DomainLookup\ResultsList) {
                $searchResult = $searchResult->toArray();
            }
            if ($preferredTLDNotAvailable) {
                $searchResult[0]["preferredTLDNotAvailable"] = $preferredTLDNotAvailable;
                $searchResult[0]["originalUnavailableDomain"] = $searchResult[0]["sld"] . $originalTld;
            }
            $this->searchResult = $searchResult;
        }
    }
    protected function checkIncart()
    {
        $orderForm = new \WHMCS\OrderForm();
        $productId = (int) $this->request->get("pid");
        $productInfo = $orderForm->setPid($productId);
        $passedVariables = $_SESSION["cart"]["passedvariables"];
        unset($_SESSION["cart"]["passedvariables"]);
        $this->cartPreventDuplicateProduct($this->domain->getDomain());
        $productArray = array("pid" => $productId, "domain" => $this->domain->getDomain(), "billingcycle" => $passedVariables["billingcycle"] ?: $orderForm->validateBillingCycle(""), "configoptions" => $passedVariables["configoption"], "customfields" => $passedVariables["customfield"], "addons" => $passedVariables["addons"], "server" => "", "noconfig" => true, "skipConfig" => isset($passedVariables["skipconfig"]) && $passedVariables["skipconfig"]);
        if (isset($passedVariables["bnum"])) {
            $productArray["bnum"] = $passedVariables["bnum"];
        }
        if (isset($passedVariables["bitem"])) {
            $productArray["bitem"] = $passedVariables["bitem"];
        }
        $_SESSION["cart"]["newproduct"] = true;
        $updatedExistingQuantity = false;
        if ($productInfo["allowqty"]) {
            foreach ($_SESSION["cart"]["products"] as &$cart_prod) {
                if ($productId == $cart_prod["pid"]) {
                    if (empty($cart_prod["qty"])) {
                        $cart_prod["qty"] = 1;
                    }
                    $cart_prod["qty"]++;
                    if ($productInfo["stockcontrol"] && $productInfo["qty"] < $cart_prod["qty"]) {
                        $cart_prod["qty"] = $productInfo["qty"];
                    }
                    $updatedExistingQuantity = true;
                    break;
                }
            }
        }
        if (!$updatedExistingQuantity) {
            $_SESSION["cart"]["products"][] = $productArray;
        }
        $newProductIValue = count($_SESSION["cart"]["products"]) - 1;
        if (isset($passedVariables["skipconfig"]) && $passedVariables["skipconfig"]) {
            unset($_SESSION["cart"]["products"][$newProductIValue]["noconfig"]);
            $_SESSION["cart"]["lastconfigured"] = array("type" => "product", "i" => $newProductIValue);
        }
        $searchResult[] = array("status" => true, "num" => $newProductIValue);
        $this->searchResult = $searchResult;
    }
    protected function checkOwndomain()
    {
        $this->lookupProvider->checkOwnDomain($this->domain);
        $this->checkIncart();
    }
    protected function checkSpotlight()
    {
        $spotlightTlds = $this->getSpotlightTlds();
        $searchResult = new \WHMCS\Domains\DomainLookup\ResultsList();
        if (0 < count($spotlightTlds)) {
            $searchResult = $this->lookupProvider->checkAvailability($this->domain, $spotlightTlds);
        }
        $this->searchResult = $searchResult;
    }
    protected function checkSubdomain()
    {
        $this->lookupProvider->checkSubDomain($this->domain);
        $this->checkIncart();
    }
    protected function checkSuggestions()
    {
        $this->searchResult = $this->lookupProvider->getSuggestions($this->domain);
    }
    protected function checkTransfer()
    {
        $this->overrideCheckIfDomainAlreadyOrdered();
        if (empty($this->searchResult)) {
            $tld = $this->domain->getDotTopLevel();
            $this->searchResult = $this->lookupProvider->checkAvailability($this->domain, array($tld));
        }
    }
    protected function overrideCheckIfDomainAlreadyOrdered()
    {
        if (cartCheckIfDomainAlreadyOrdered($this->domain)) {
            $errorResult = new \WHMCS\Domains\DomainLookup\SearchResult($this->domain->getSecondLevel(), $this->domain->getTopLevel());
            $errorResult->setStatus(\WHMCS\Domains\DomainLookup\SearchResult::STATUS_UNKNOWN);
            $this->searchResult = $errorResult;
        }
    }
    protected function processPremiumDomains()
    {
        if (\WHMCS\Config\Setting::getValue("PremiumDomains")) {
            $premiumSessionData = array();
            foreach ($this->searchResult as $key => $domain) {
                if (is_object($domain)) {
                    $domain = $domain->toArray();
                }
                if ($domain["isPremium"]) {
                    $premiumSessionData[$domain["domainName"]] = array("markupPrice" => $domain["pricing"], "cost" => $domain["premiumCostPricing"]);
                }
            }
            if ($premiumSessionData) {
                $storedSessionData = \WHMCS\Session::get("PremiumDomains");
                if ($storedSessionData && is_array($storedSessionData)) {
                    $premiumSessionData = array_merge($storedSessionData, $premiumSessionData);
                }
                \WHMCS\Session::setAndRelease("PremiumDomains", $premiumSessionData);
            }
        }
    }
    protected function cartPreventDuplicateProduct($domain)
    {
        if ($domain) {
            $domains = array();
            foreach ($_SESSION["cart"]["products"] as $k => $values) {
                $domains[$k] = $values["domain"];
            }
            if (in_array($domain, $domains)) {
                $i = array_search($domain, $domains);
                if ($i !== false) {
                    unset($_SESSION["cart"]["products"][$i]);
                    $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
                }
            }
        }
    }
    public function populateCartWithDomainSmartyVariables($domainOption, array &$smartyVariables)
    {
        $searchResult = $this->searchResult[0];
        if ($domainOption == "register") {
            $matchString = \WHMCS\Domains\DomainLookup\SearchResult::STATUS_NOT_REGISTERED;
        } else {
            $matchString = \WHMCS\Domains\DomainLookup\SearchResult::STATUS_REGISTERED;
        }
        if ($searchResult->getStatus() == \WHMCS\Domains\DomainLookup\SearchResult::STATUS_UNKNOWN) {
            $matchString = \WHMCS\Domains\DomainLookup\SearchResult::STATUS_UNKNOWN;
        }
        $smartyVariables["searchvar"] = $matchString;
        $smartyVariables["searchResults"] = $searchResult->toArray();
        $smartyVariables["availabilityresults"] = cartAvailabilityResultsBackwardsCompat($this->domain, $searchResult, $matchString);
    }
    protected function getTldsList()
    {
        return getTLDList();
    }
    protected function getSpotlightTlds()
    {
        return getSpotlightTlds();
    }
}

?>