<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\ConfigureSsl;

class Csr
{
    public function getStepContent()
    {
        $langServerInfoTitle = \Lang::trans("sslserverinfo");
        $langSslServerType = \Lang::trans("sslservertype");
        $langPleaseChoose = \Lang::trans("pleasechooseone");
        $langSslCsr = \Lang::trans("sslcsr");
        $serviceId = \App::getFromRequest("serviceid");
        $addonId = \App::getFromRequest("addonid");
        $webServerTypes = array("cpanel" => "cPanel/WHM", "plesk" => "Plesk", "apache2" => "Apache 2", "apacheopenssl" => "Apache + OpenSSL", "apacheapachessl" => "Apache + ApacheSSL", "iis" => "Microsoft IIS", "other" => "Other");
        $webServerTypesOutput = array();
        foreach ($webServerTypes as $name => $displayLabel) {
            $webServerTypesOutput[] = "<option value=\"" . $name . "\">" . $displayLabel . "</option>";
        }
        $autoGenerateCsrButton = "";
        $serverInterface = new \WHMCS\Module\Server();
        if ($addonId && $serverInterface->loadByAddonId($addonId) && $serverInterface->functionExists("check_auto_install_panels")) {
            $response = $serverInterface->call("check_auto_install_panels");
            if (array_key_exists("supported", $response) && $response["supported"] === true) {
                $autoGenerateCsrButton = "<div id=\"autoGenerateCsr\" style=\"margin-top:16px;display: none;\">\n                <a id=\"btnAutoGenerateCsr\" href=\"#\" class=\"btn btn-default btn-sm pull-left\" style=\"margin-right:20px;\" onclick=\"return false;\">Auto Generate CSR</a>\n                Since this SSL product is attached to a " . $response["panel"] . " hosting account, we can automatically generate a CSR for you.\n            </div>";
            }
        }
        $webServerTypesOutput = implode($webServerTypesOutput);
        return "\n            <h2>" . $langServerInfoTitle . "</h2>\n\n            <div class=\"alert alert-warning info-alert\">Please enter the CSR below or use one of the automatic generation options if available.</div>\n\n            <div class=\"form-group\">\n                <label for=\"inputServerType\">" . $langSslServerType . "</label>\n                <select name=\"servertype\" id=\"inputServerType\" class=\"form-control\">\n                    <option value=\"\" selected>" . $langPleaseChoose . "</option>\n                    " . $webServerTypesOutput . "\n                </select>\n            </div>\n\n            <div class=\"form-group\">\n                <label for=\"inputCsr\">" . $langSslCsr . "</label>\n                <textarea name=\"csr\" id=\"inputCsr\" rows=\"7\" class=\"form-control\">-----BEGIN CERTIFICATE REQUEST-----\n-----END CERTIFICATE REQUEST-----</textarea>\n            </div>\n\n            " . $autoGenerateCsrButton . "\n\n            <input type=\"hidden\" name=\"serviceid\" value=\"" . $serviceId . "\">\n            <input type=\"hidden\" name=\"addonid\" value=\"" . $addonId . "\">\n\n<script type=\"text/javascript\">\njQuery(document).ready(function(){\n    jQuery('#btnAutoGenerateCsr').on('click', function(){\n        jQuery('#modalAjaxLoader').show();\n        WHMCS.http.jqClient.post(\n            window.location.href,\n            {\n                modop: \"custom\",\n                ac: \"generate_csr\",\n                token: csrfToken\n            },\n            function (data) {\n                if (typeof data.body.csr !== \"undefined\" && data.body.csr !== false) {\n                    jQuery('#inputCsr').text(data.body.csr);\n                }\n                jQuery('#modalAjaxLoader').hide();\n            },\n            'json'\n        );\n    });\n});\n</script> ";
    }
    public function save($data)
    {
        $serverType = isset($data["servertype"]) ? trim($data["servertype"]) : "";
        $csr = isset($data["csr"]) ? trim($data["csr"]) : "";
        $serviceId = isset($data["serviceid"]) ? trim($data["serviceid"]) : "";
        $addonId = isset($data["addonid"]) ? trim($data["addonid"]) : "";
        if (!$serverType) {
            throw new \WHMCS\Exception("Web Server Type is required");
        }
        if (!$csr || $csr == "-----BEGIN CERTIFICATE REQUEST-----\r\n-----END CERTIFICATE REQUEST-----") {
            throw new \WHMCS\Exception("A Certificate Signing Request (CSR) is required");
        }
        $serverInterface = new \WHMCS\Module\Server();
        if ($addonId) {
            $serverInterface->loadByAddonId($addonId);
        } else {
            $serverInterface->loadByServiceID($serviceId);
        }
        $response = $serverInterface->call("SSLStepTwo", array("csr" => $csr));
        if (isset($response["error"]) && $response["error"]) {
            throw new \WHMCS\Exception($response["error"]);
        }
        $certConfig = array("serverType" => $serverType, "csr" => $csr, "domain" => $response["displaydata"]["Domain Name"]);
        $model = $addonId ? \WHMCS\Service\Addon::findOrFail($addonId) : \WHMCS\Service\Service::findOrFail($serviceId);
        $model->serviceProperties->save(array("domain" => $response["displaydata"]["Domain Name"]));
        \WHMCS\Session::setAndRelease("AdminCertConfiguration", $certConfig);
        return array("approvalmethods" => $response["approvalmethods"], "approveremails" => $response["approveremails"]);
    }
}

?>