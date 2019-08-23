<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("");
$response = array();
$wizard = NULL;
try {
    $requestedWizard = App::getFromRequest("wizard");
    $wizard = WHMCS\Admin\Wizard\Wizard::factory($requestedWizard);
} catch (WHMCS\Exception\AccessDenied $e) {
    $response = array("body" => "<div class=\"container\"><h2>" . $e->getMessage() . "</h2></div>");
} catch (Exception $e) {
    $response = array("body" => $e->getMessage());
    $dismiss = App::getFromRequest("dismiss");
    if ($dismiss == "true") {
        WHMCS\Config\Setting::setValue("DisableSetupWizard", 1);
        $response = array("disabled" => true);
    }
}
if (!is_null($wizard)) {
    if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && 0 < count($_POST)) {
        check_token("WHMCS.admin.default");
        try {
            $step = App::getFromRequest("step");
            $action = App::getFromRequest("action");
            if (!$action) {
                $action = "save";
            }
            $returnData = $wizard->handleSubmit($step, $action, $_POST);
            $response = array("success" => true);
            if (is_array($returnData)) {
                $response = array_merge($response, $returnData);
            }
        } catch (Exception $e) {
            $response = array("success" => false, "error" => $e->getMessage());
        }
    } else {
        $output = $wizard->render(new WHMCS\Smarty(true, "mail"));
        $response = array("body" => $output);
    }
}
$aInt->setBodyContent($response);
$aInt->output();

?>