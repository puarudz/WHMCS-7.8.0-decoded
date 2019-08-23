<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$type = $whmcs->get_req_var("type");
$relatedId = (int) $whmcs->get_req_var("id");
$action = $whmcs->get_req_var("action");
$defaultTranslation = $whmcs->get_req_var("origvalue");
if (!$type) {
    $aInt = new WHMCS\Admin("loginonly");
    $aInt->setBodyContent(array("body" => "This page cannot be accessed directly"));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
switch ($type) {
    case "configurable_option.name":
    case "configurable_option_option.name":
    case "product.description":
    case "product.name":
        $aInt = new WHMCS\Admin("Edit Products/Services");
        break;
    case "custom_field.description":
    case "custom_field.name":
        $customFieldType = $relatedId ? WHMCS\Database\Capsule::table("tblcustomfields")->find($relatedId, array("type"))->type : $whmcs->get_req_var("cf-type");
        switch ($customFieldType) {
            case "client":
            case "product":
                $aInt = new WHMCS\Admin("View Products/Services");
                break;
            case "support":
                $aInt = new WHMCS\Admin("Configure Support Departments");
                break;
            default:
                $aInt = new WHMCS\Admin("Configure Custom Client Fields");
                break;
        }
    case "download.description":
    case "download.title":
        $aInt = new WHMCS\Admin("Manage Downloads");
        break;
    case "product_addon.description":
    case "product_addon.name":
        $aInt = new WHMCS\Admin("Configure Product Addons");
        break;
    case "product_bundle.description":
    case "product_bundle.name":
        $aInt = new WHMCS\Admin("Configure Product Bundles");
        break;
    case "product_group.headline":
    case "product_group.name":
    case "product_group.tagline":
    case "product_group_feature.feature":
        $aInt = new WHMCS\Admin("Manage Product Groups");
        break;
    case "ticket_department.description":
    case "ticket_department.name":
        $aInt = new WHMCS\Admin("Configure Support Departments");
        break;
    default:
        $aInt->setBodyContent(array("body" => "Invalid Type"));
        $aInt->output();
        WHMCS\Terminus::getInstance()->doExit();
}
break;

?>