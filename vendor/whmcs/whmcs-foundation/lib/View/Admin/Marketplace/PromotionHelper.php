<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Admin\Marketplace;

class PromotionHelper
{
    const PROMO_BASE_URL = "https://cdn.whmcs.com/promo/";
    public function hookIntoPage(\WHMCS\Admin $adminInterface)
    {
        $adminInterface->addHeadJsCode("\nvar activePartner = \"\";\nfunction showPromo(partner) {\n    activePartner = partner;\n    \$(\"#promoModal\").modal(\"show\");\n    promoRequest(partner, \"\");\n}\nfunction closePromo() {\n    \$(\"#promoModal\").modal(\"hide\");\n}\nfunction promoRequest(requestString, formName) {\n    if (formName !== 'undefined') {\n        requestString = requestString + \"&\" + \$(\"#\" + formName).serialize();\n    }\n    \$(\"#promoModalLoading\").removeClass(\"hidden\").show();\n    WHMCS.http.jqClient.post(\"" . \App::getPhpSelf() . "\", \"promofetch=1&partner=\" + activePartner + \"&\" + requestString, function(data) {\n        \$(\"#promoModalLoading\").hide();\n        if (jQuery.isEmptyObject(data)) {\n            \$(\"#promoModalContent\").hide();\n            \$(\"#promoModal .modal-title\").html(\"Oops!\");\n            \$(\"#promoModal .modal-tagline\").html(\"\");\n            \$(\"#promoModalContentError\").removeClass(\"hidden\").show();\n        } else {\n            \$(\"#promoModalContentError\").hide();\n            if (data.title) {\n                \$(\"#promoModal .modal-title\").html(data.title);\n            }\n            if (data.tagline) {\n                \$(\"#promoModal .modal-tagline\").html(data.tagline);\n            }\n            \$(\"#promoModalContent\").html(data.content);\n            \$(\"#promoModalContent\").show();\n        }\n    }, \"json\");\n}\n        ");
        $this->defineModalHtml();
        return $this;
    }
    protected function defineModalHtml()
    {
        add_hook("AdminAreaFooterOutput", 1, function () {
            return "<div class=\"modal fade partner-modal\" id=\"promoModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"myModalLabel\" aria-hidden=\"true\">\n  <div class=\"modal-dialog modal-lg\">\n    <div class=\"modal-content\">\n      <div class=\"modal-header\">\n        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>\n        <h4 class=\"modal-title\">Loading... Please Wait...</h4>\n        <div id=\"promoModalLoading\" class=\"loader-container\"><i class=\"fas fa-circle-notch fa-spin\"></i> Loading...</div>\n        <small class=\"modal-tagline\"></small>\n      </div>\n      <div class=\"modal-body\" id=\"promoModalContent\"></div>\n      <div class=\"modal-body text-center hidden\" id=\"promoModalContentError\">\n        Something went wrong. Please try again later.\n      </div>\n    </div>\n  </div>\n</div>";
        });
        return $this;
    }
    public function isPromoFetchRequest()
    {
        return (bool) \App::get_req_var("promofetch");
    }
    protected function getPromoUrl($partner)
    {
        return self::PROMO_BASE_URL . rawurlencode($partner) . "/";
    }
    public function fetchPromoContent($partner, $params = array())
    {
        $url = $this->getPromoUrl($partner);
        if (!is_array($params)) {
            $params = array();
        }
        $whmcs = \App::self();
        $licensing = $whmcs->getLicense();
        $params = $params + array("params" => array("licenseKey" => $whmcs->get_license_key(), "registeredTo" => $licensing->getKeyData("registeredname"), "version" => $whmcs->getVersion()->getCanonical(), "ssl" => $whmcs->in_ssl()));
        $response = $this->parseResponse($this->post($url, $params));
        return $this->generateResponse($response);
    }
    protected function post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $rawResponse = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            logActivity("Error trying to fetch partner promo: " . "CURL Error: " . curl_errno($ch) . " - " . curl_error($ch));
        } else {
            if ($responseCode != 200) {
                logActivity("Error trying to fetch partner promo: Non-200 response code received");
            }
        }
        curl_close($ch);
        return $rawResponse;
    }
    protected function generateResponse($response)
    {
        if ($response) {
            if (isset($response["activateModule"]) && $response["activateModule"]) {
                if ($response["moduleType"] == "registrar") {
                    $this->activateRegistrar($response["moduleName"], $response["moduleParameters"]);
                } else {
                    if ($response["moduleType"] == "gateway") {
                        $this->activateGateway($response["moduleName"], $response["moduleParameters"]);
                    }
                }
            }
            return array("title" => isset($response["outputTitle"]) && $response["outputTitle"] ? $response["outputTitle"] : "", "tagline" => isset($response["outputTagline"]) && $response["outputTagline"] ? $response["outputTagline"] : "", "content" => isset($response["outputContent"]) ? $response["outputContent"] : "");
        }
        return array();
    }
    protected function parseResponse($rawResponse)
    {
        $data = json_decode($rawResponse, true);
        if (!$data || !is_array($data) || json_last_error() != JSON_ERROR_NONE) {
            logActivity("Error trying to fetch partner promo: Invalid response received");
            return array();
        }
        return $data;
    }
    protected function activateRegistrar($moduleName, $parameters)
    {
        $alreadyActive = \Illuminate\Database\Capsule\Manager::table("tblregistrars")->where("registrar", $moduleName)->count("id");
        if (0 < $alreadyActive) {
            return false;
        }
        foreach ($parameters as $key => $value) {
            $key = \App::sanitize("a-z", $key);
            \Illuminate\Database\Capsule\Manager::table("tblregistrars")->insert(array("registrar" => $moduleName, "setting" => $key, "value" => encrypt($value)));
        }
        return true;
    }
    protected function activateGateway($moduleName, $parameters)
    {
        $alreadyActive = \Illuminate\Database\Capsule\Manager::table("tblpaymentgateways")->where("gateway", $moduleName)->count("id");
        if (0 < $alreadyActive) {
            return false;
        }
        foreach ($parameters as $key => $value) {
            $key = \App::sanitize("a-z", $key);
            \Illuminate\Database\Capsule\Manager::table("tblpaymentgateways")->insert(array("gateway" => $moduleName, "setting" => $key, "value" => $value, "order" => "0"));
        }
        return true;
    }
}

?>