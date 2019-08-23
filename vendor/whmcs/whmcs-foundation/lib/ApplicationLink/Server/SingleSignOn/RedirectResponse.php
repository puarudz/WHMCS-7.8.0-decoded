<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\Server\SingleSignOn;

class RedirectResponse extends \Symfony\Component\HttpFoundation\RedirectResponse
{
    protected $pathScopeMap = array("clientarea:homepage" => "/clientarea.php", "clientarea:profile" => "/clientarea.php?action=details", "clientarea:billing_info" => "/clientarea.php?action=creditcard", "clientarea:emails" => "/clientarea.php?action=emails", "clientarea:announcements" => "/index.php?rp=/announcements", "clientarea:downloads" => "/index.php?rp=/download", "clientarea:knowledgebase" => "/knowledgebase.php", "clientarea:network_status" => "/serverstatus.php", "clientarea:services" => "/clientarea.php?action=services", "clientarea:product_details" => "/clientarea.php?action=productdetails&id=:serviceId", "clientarea:domains" => "/clientarea.php?action=domains", "clientarea:domain_details" => "/clientarea.php?action=domaindetails&id=:domainId", "clientarea:invoices" => "/clientarea.php?action=invoices", "clientarea:tickets" => "/supporttickets.php", "clientarea:submit_ticket" => "/submitticket.php", "clientarea:shopping_cart" => "/cart.php", "clientarea:shopping_cart_addons" => "/cart.php?gid=addons", "clientarea:upgrade" => "/upgrade.php?type=package&id=:serviceId", "clientarea:shopping_cart_domain_register" => "/cart.php?a=add&domain=register", "clientarea:shopping_cart_domain_transfer" => "/cart.php?a=add&domain=transfer");
    const DEFAULT_URL = "/clientarea.php";
    const DEFAULT_SCOPE = "clientarea:homepage";
    public function __construct($url = "", $status = 302, $headers = array())
    {
        if (empty($url)) {
            $url = static::DEFAULT_URL;
        }
        parent::__construct($url, $status, $headers);
    }
    public function setTargetUrlFromToken(\WHMCS\ApplicationLink\AccessToken $token)
    {
        $scopeForRedirect = $this->getScope($token);
        $path = $this->getPathFromScope($scopeForRedirect);
        $path = $this->fillPlaceHolders($path, $token);
        $pathParts = explode("?", $path, 2);
        $systemUrl = \App::getSystemURL(false);
        if (!empty($pathParts[1])) {
            $url = \App::getRedirectUrl($pathParts[0], $pathParts[1], $systemUrl);
        } else {
            $url = \App::getRedirectUrl($path, "", $systemUrl);
        }
        parent::setTargetUrl($url);
        return $this;
    }
    protected function getScope(\WHMCS\ApplicationLink\AccessToken $token)
    {
        $scopeForRedirect = "";
        foreach ($token->scopes()->get() as $scope) {
            if ($scope->scope != "clientarea:sso") {
                $scopeForRedirect = $scope->scope;
                break;
            }
        }
        if (empty($scopeForRedirect)) {
            $scopeForRedirect = static::DEFAULT_SCOPE;
        }
        return $scopeForRedirect;
    }
    protected function getPathFromScope($scope)
    {
        $path = $this->pathScopeMap[static::DEFAULT_SCOPE];
        if (!empty($this->pathScopeMap[$scope])) {
            $path = $this->pathScopeMap[$scope];
        }
        return $path;
    }
    protected function fillPlaceHolders($path, \WHMCS\ApplicationLink\AccessToken $token)
    {
        $placeholders = array("serviceId");
        foreach ($placeholders as $variable) {
            $value = sprintf("%s", $token->client->{$variable});
            $path = str_replace(":" . $variable, $value, $path);
        }
        return $path;
    }
}

?>