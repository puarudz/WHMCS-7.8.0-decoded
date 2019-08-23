<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("ClientAreaPrimarySidebar", -1, "cpanel_defineSsoSidebarLinks");
function cpanel_defineSsoSidebarLinks($sidebar)
{
    if (!$sidebar->getChild("Service Details Actions")) {
        return NULL;
    }
    $service = Menu::context("service");
    if ($service instanceof WHMCS\Service\Service && $service->product->module != "cpanel") {
        return NULL;
    }
    $ssoPermission = checkContactPermission("productsso", true);
    $sidebar->getChild("Service Details Actions")->addChild("Login to cPanel", array("uri" => "clientarea.php?action=productdetails&id=" . $service->id . "&dosinglesignon=1" . ($service->product->type == "reselleraccount" ? "&app=Home" : ""), "label" => Lang::trans("cpanellogin"), "attributes" => $ssoPermission ? array("target" => "_blank") : array(), "disabled" => $service->status != "Active", "order" => 1));
    if ($service->product->type == "reselleraccount") {
        $sidebar->getChild("Service Details Actions")->addChild("Login to WHM", array("uri" => "clientarea.php?action=productdetails&id=" . $service->id . "&dosinglesignon=1", "label" => Lang::trans("cpanelwhmlogin"), "attributes" => $ssoPermission ? array("target" => "_blank") : array(), "disabled" => $service->status != "Active", "order" => 2));
    }
    $moduleInterface = new WHMCS\Module\Server();
    $moduleInterface->loadByServiceID($service->id);
    $serverParams = $moduleInterface->getServerParams($service->server);
    $domain = $serverParams["serverhostname"] ?: $serverParams["serverip"];
    $port = $serverParams["serversecure"] ? "2096" : "2095";
    $webmailUrl = $serverParams["serverhttpprefix"] . "://" . $domain . ":" . $port;
    $sidebar->getChild("Service Details Actions")->addChild("Login to Webmail", array("uri" => $webmailUrl, "label" => Lang::trans("cpanelwebmaillogin"), "attributes" => array("target" => "_blank"), "disabled" => $service->status != "Active", "order" => 3));
}

?>