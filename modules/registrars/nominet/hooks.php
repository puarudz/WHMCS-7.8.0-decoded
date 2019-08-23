<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("ClientAreaPrimarySidebar", -1, "nominet_HideReleaseDomain");
function nominet_HideReleaseDomain(WHMCS\View\Menu\Item $primarySidebar)
{
    $settingAllowClientTag = get_query_val("tblregistrars", "value", "registrar = 'nominet' AND setting = 'AllowClientTAGChange'");
    $settingAllowClientTag = decrypt($settingAllowClientTag);
    if ($settingAllowClientTag == "on") {
        return NULL;
    }
    if (!is_null($primarySidebar->getChild("Domain Details Management"))) {
        $primarySidebar->getChild("Domain Details Management")->removeChild("Release Domain");
    }
}

?>