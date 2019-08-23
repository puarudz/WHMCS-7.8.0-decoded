<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud\FraudLabs;

class FraudLabs extends \WHMCS\Module\Fraud\AbstractModule implements \WHMCS\Module\Fraud\ModuleInterface
{
    protected $fieldMapping = array("ip" => array("ip_country", "is_country_match", "ip_region", "ip_city", "ip_continent", "ip_latitude", "ip_longitude", "ip_timezone", "ip_domain", "ip_isp_name", "ip_usage_type", "is_proxy_ip_address", "is_ip_blacklist"), "billing" => array("is_high_risk_country", "is_phone_blacklist", "is_export_controlled_country", "user_order_id"), "email" => array("is_free_email", "is_disposable_email", "is_new_domain_name", "is_domain_exists", "is_email_blacklist"), "general" => array("fraudlabspro_id", "fraudlabspro_distribution", "fraudlabspro_status", "fraudlabspro_version", "fraudlabspro_credits"));
    protected $meteredFields = array("fraudlabspro_distribution");
    protected $booleanFields = array("is_country_match", "is_high_risk_country", "is_free_email", "is_disposable_email", "is_new_domain_name", "is_domain_exists", "is_proxy_ip_address", "is_ip_blacklist", "is_email_blacklist", "is_phone_blacklist", "is_export_controlled_country");
    public function validateRules(array $params, \WHMCS\Module\Fraud\ResponseInterface $response)
    {
        $maxRiskScore = (int) $params["riskScore"];
        if (0 < $maxRiskScore && $maxRiskScore < (int) $response->get("fraudlabspro_score")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("fraud.highFraudRiskScore"));
        }
        if ($response->get("fraudlabspro_status") && $response->get("fraudlabspro_status") != "APPROVE") {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("fraud.manualReview"));
        }
        if (!empty($params["rejectCountryMismatch"]) && $response->get("is_country_match") == "N") {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("fraud.countryMismatch"));
        }
        if (!empty($params["rejectAnonymousNetwork"]) && $response->get("is_proxy_ip_address") == "Y") {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("fraud.anonymousProxy"));
        }
        if (!empty($params["rejectHighRiskCountry"]) && $response->get("is_high_risk_country") == "Y") {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("fraud.highRiskCountry"));
        }
    }
    public function formatResponse(\WHMCS\Module\Fraud\ResponseInterface $response)
    {
        $panels = array();
        $fieldMapping = $this->fieldMapping;
        foreach ($fieldMapping as $panelTitle => $panelElements) {
            $panelValues = array();
            foreach ($panelElements as $element) {
                $panelValues[$element] = $response->get($element);
            }
            $panels[$panelTitle] = $panelValues;
        }
        $errorCode = $response->get("fraudlabspro_error_code");
        if ($errorCode) {
            $panelValues["general"]["error"] = $response->get("fraudlabspro_message");
        }
        return $this->generateHtmlOutput($response, $panels);
    }
    protected function generateHtmlOutput(Response $response, array $panels)
    {
        $errorMessage = "";
        if (!$response->isSuccessful()) {
            $errorMessage = $response->get("fraudlabspro_message") . " (" . $response->get("fraudlabspro_error_code") . ")";
        }
        $highRiskCountry = $newDomain = $freeEmailAddress = "fa-times text-success";
        if ($response->get("is_high_risk_country") == "Y") {
            $highRiskCountry = "fa-check text-danger";
        }
        if ($response->get("is_new_domain_name") == "Y") {
            $newDomain = "fa-check text-danger";
        }
        if ($response->get("is_free_email") == "Y") {
            $freeEmailAddress = "fa-check text-danger";
        }
        $score = $response->get("fraudlabspro_score");
        return view("admin.orders.fraudlabs.results", array("errorMsg" => $errorMessage, "prePanelsOutput" => "\n<div style=\"margin:20px 0;\">\n    <div class=\"row\">\n        <div class=\"col-sm-3 text-center\">\n            <input type=\"text\" class=\"fraud-check-meter\" data-min=\"0\" data-max=\"100\"\n                data-readOnly=\"true\" data-width=\"100\" data-height=\"80\" data-angleArc=\"230\"\n                data-angleOffset=\"-115\" data-fgColor=\"#ecdc11\" value=\"" . $score . "\"\n            >\n            <br>" . \AdminLang::trans("fraudlabs.riskScore") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $highRiskCountry . "\"></i>\n            <br>" . \AdminLang::trans("fraudlabs.highRiskCountry") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $newDomain . "\"></i>\n            <br>" . \AdminLang::trans("fraudlabs.newDomain") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $freeEmailAddress . "\"></i>\n            <br>" . \AdminLang::trans("fraudlabs.freeEmailAddress") . "\n        </div>\n    </div>\n</div>\n", "panels" => $panels, "meteredFields" => $this->meteredFields, "booleanFields" => $this->booleanFields, "postPanelsOutput" => ""));
    }
    public static function hash($string)
    {
        $hash = "fraudlabspro_" . $string;
        for ($i = 0; $i < 65536; $i++) {
            $hash = sha1("fraudlabspro_" . $hash);
        }
        return $hash;
    }
}

?>