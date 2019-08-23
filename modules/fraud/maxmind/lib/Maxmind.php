<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud\MaxMind;

class Maxmind extends \WHMCS\Module\Fraud\AbstractModule implements \WHMCS\Module\Fraud\ModuleInterface
{
    protected $fieldMapping = array("ip" => array("ip_address.city.names.en", "ip_address.city.confidence", "ip_address.subdivisions.0.iso_code", "ip_address.subdivisions.0.names.en", "ip_address.subdivisions.0.confidence", "ip_address.continent.code", "ip_address.continent.names.en", "ip_address.country.confidence", "ip_address.country.iso_code", "ip_address.country.names.en", "ip_address.location.accuracy_radius", "ip_address.location.latitude", "ip_address.location.longitude", "ip_address.location.time_zone", "ip_address.traits.user_type", "ip_address.traits.autonomous_system_number", "ip_address.traits.autonomous_system_organization", "ip_address.traits.isp", "ip_address.traits.is_anonymous", "ip_address.traits.is_anonymous_vpn"), "subscores" => array("subscores.billing_address", "subscores.billing_address_distance_to_ip_location", "subscores.browser", "subscores.country", "subscores.country_mismatch", "subscores.email_address", "subscores.email_domain", "subscores.order_amount", "subscores.phone_number", "subscores.time_of_day"), "billing" => array("billing_address.latitude", "billing_address.longitude", "billing_address.distance_to_ip_location", "billing_address.is_in_ip_country"), "credit_card" => array("credit_card.issuer.name", "credit_card.issuer.phone_number", "credit_card.brand", "credit_card.country", "credit_card.is_issued_in_billing_address_country", "credit_card.is_prepaid", "credit_card.is_virtual", "credit_card.type"), "email" => array("email.first_seen", "email.is_free", "email.is_high_risk"), "general" => array("id", "funds_remaining", "queries_remaining"));
    protected $meteredFields = array("ip_address.city.confidence", "ip_address.country.confidence", "ip_address.subdivisions.0.confidence", "subscores.billing_address", "subscores.billing_address_distance_to_ip_location", "subscores.browser", "subscores.country", "subscores.country_mismatch", "subscores.email_address", "subscores.email_domain", "subscores.order_amount", "subscores.phone_number", "subscores.time_of_day");
    protected $booleanFields = array("email.is_free", "email.is_high_risk", "billing_address.is_in_ip_country", "ip_address.traits.is_anonymous", "ip_address.traits.is_anonymous_vpn", "credit_card.is_issued_in_billing_address_country", "credit_card.is_prepaid", "credit_card.is_virtual");
    public function validateRules(array $params, \WHMCS\Module\Fraud\ResponseInterface $response)
    {
        $maxRiskScore = (int) $params["riskScore"];
        if (0 < $maxRiskScore && $maxRiskScore < $response->get("risk_score")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_highfraudriskscore"));
        }
        if ($response->get("disposition") && $response->get("disposition.action") != "accept") {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind.manualReview"));
        }
        if (!empty($params["rejectFreeEmail"]) && $response->get("email.is_free")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_rejectemail"));
        }
        if (!empty($params["rejectCountryMismatch"]) && !$response->get("billing_address.is_in_ip_country")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_countrymismatch"));
        }
        if (!empty($params["rejectAnonymousNetwork"]) && $response->get("ip_address.is_anonymous")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_anonproxy"));
        }
        if (!empty($params["rejectHighRiskCountry"]) && $response->get("ip_address.country.is_high_risk")) {
            throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_highriskcountry"));
        }
        if (empty($params["ignoreAddressValidation"]) || !$params["ignoreAddressValidation"]) {
            $warnings = $response->get("warnings");
            if (is_array($warnings)) {
                $warningCodes = collect($warnings)->pluck("code");
                if (!empty($params["clientsdetails"]["city"]) && $warningCodes->contains("BILLING_CITY_NOT_FOUND")) {
                    throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_addressinvalid"));
                }
                if (!empty($params["clientsdetails"]["postcode"]) && $warningCodes->contains("BILLING_POSTAL_NOT_FOUND")) {
                    throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_addressinvalid"));
                }
                if (!empty($params["clientsdetails"]["country"]) && $warningCodes->contains("BILLING_COUNTRY_NOT_FOUND")) {
                    throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_addressinvalid"));
                }
                if (empty($params["clientsdetails"]["country"]) && $warningCodes->contains("BILLING_COUNTRY_MISSING")) {
                    throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_addressinvalid"));
                }
                if ($warningCodes->contains("IP_ADDRESS_NOT_FOUND")) {
                    throw new \WHMCS\Exception\Fraud\FraudCheckException(\Lang::trans("maxmind_invalidip"));
                }
            }
        }
    }
    public function formatResponse(\WHMCS\Module\Fraud\ResponseInterface $response)
    {
        $panels = array();
        $fieldMapping = $this->fieldMapping;
        if (!$response->get("subscores")) {
            unset($fieldMapping["subscores"]);
        }
        if (!$response->get("credit_card")) {
            unset($fieldMapping["credit_card"]);
        }
        if ($response->get("disposition")) {
            $fieldMapping["custom"] = array("disposition.action", "disposition.reason");
        }
        foreach ($fieldMapping as $panelTitle => $panelElements) {
            $panelValues = array();
            foreach ($panelElements as $element) {
                $panelValues[$element] = $response->get($element);
            }
            $panels[$panelTitle] = $panelValues;
        }
        if ($warnings = $response->get("warnings")) {
            $panelValues = array();
            foreach ($warnings as $warning) {
                if (strpos($warning["warning"], "Encountered value at /billing/region") === false) {
                    $panelValues["warning"] .= $warning["warning"];
                }
            }
            $panels["warnings"] = $panelValues;
        }
        return $this->generateHtmlOutput($response, $panels);
    }
    protected function generateHtmlOutput(Response $response, array $panels)
    {
        $errorMessage = "";
        if (!$response->isSuccessful()) {
            switch ($response->get("code")) {
                case "LICENSE_KEY_REQUIRED":
                case "USER_ID_REQUIRED":
                    $errorMessage = \AdminLang::trans("maxmind.missingUser");
                    break;
                default:
                    $errorMessage = $response->get("error") . " (" . $response->get("code") . ")";
            }
        }
        $highRiskCountry = $highRiskEmail = $freeEmailAddress = "fa-times text-success";
        if ($response->get("ip_address.country.is_high_risk")) {
            $highRiskCountry = "fa-check text-danger";
        }
        if ($response->get("email.is_high_risk")) {
            $highRiskEmail = "fa-check text-danger";
        }
        if ($response->get("email.is_free")) {
            $freeEmailAddress = "fa-check text-danger";
        }
        $disabledPanels = array();
        if (is_null($response->get("email.is_high_risk"))) {
            $highRiskCountry = $highRiskEmail = $freeEmailAddress = "fa-question text-warning";
            $disabledPanels = array("ip", "billing", "email");
        }
        return view("admin.orders.fraud.results", array("errorMsg" => $errorMessage, "prePanelsOutput" => "\n<div style=\"margin:20px 0;\">\n    <div class=\"row\">\n        <div class=\"col-sm-3 text-center\">\n            <input type=\"text\" class=\"fraud-check-meter\" data-min=\"0\" data-max=\"100\" data-readOnly=\"true\" data-width=\"100\" data-height=\"80\" data-angleArc=\"230\" data-angleOffset=\"-115\" data-fgColor=\"#ecdc11\" value=\"" . $response->get("risk_score") . "\">\n            <br>" . \AdminLang::trans("maxmind.riskScore") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $highRiskCountry . "\"></i>\n            <br>" . \AdminLang::trans("maxmind.highRiskCountry") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $highRiskEmail . "\"></i>\n            <br>" . \AdminLang::trans("maxmind.highRiskEmailAddress") . "\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <i class=\"fas fa-5x " . $freeEmailAddress . "\"></i>\n            <br>" . \AdminLang::trans("maxmind.freeEmailAddress") . "\n        </div>\n    </div>\n</div>\n", "panels" => $panels, "meteredFields" => $this->meteredFields, "booleanFields" => $this->booleanFields, "postPanelsOutput" => "", "disabledPanels" => $disabledPanels));
    }
    public function legacyResultsFormatHandler($results)
    {
        $results = explode("\n", $results);
        $descArray = array();
        $descArray["distance"] = "Distance from IP address to Address";
        $descArray["countryMatch"] = "If Country of IP address matches Address";
        $descArray["countryCode"] = "Country Code of the IP address";
        $descArray["freeMail"] = "Whether e-mail is from free e-mail provider";
        $descArray["anonymousProxy"] = "Whether IP address is Anonymous Proxy";
        $descArray["score"] = "Old Fraud Risk Score";
        $descArray["proxyScore"] = "Likelihood of IP Address being an Open Proxy";
        $descArray["riskScore"] = "New Risk Score Rating";
        $descArray["ip_city"] = "Estimated City of the IP address";
        $descArray["ip_region"] = "Estimated State/Region of the IP address";
        $descArray["ip_latitude"] = "Estimated Latitude of the IP address";
        $descArray["ip_longitude"] = "Estimated Longitude of the IP address";
        $descArray["ip_isp"] = "ISP of the IP address";
        $descArray["ip_org"] = "Organization of the IP address";
        $descArray["custPhoneInBillingLoc"] = "Customer Phone in Billing Location";
        $descArray["highRiskCountry"] = "IP address or billing address in high risk country";
        $descArray["cityPostalMatch"] = "Whether billing city and state match zipcode";
        $descArray["carderEmail"] = "Whether e-mail is in database of high risk e-mails";
        $descArray["maxmindID"] = "MaxMind ID";
        $descArray["err"] = "MaxMind Error";
        $descArray["explanation"] = "Explanation";
        $values = array();
        foreach ($results as $value) {
            $result = explode(" => ", $value, 2);
            $result[1] = str_replace("http://www.maxmind.com/app/ccv2r_signup", "http://www.maxmind.com/app/ccfd_promo?promo=WHMCS4562", !empty($result[1]) ? $result[1] : "");
            $values[$result[0]] = $result[1];
        }
        $resultArray = array();
        $empty = true;
        foreach ($descArray as $k => $v) {
            if ($k == "riskScore" && $values[$k]) {
                $values[$k] .= "%";
            }
            $value = "";
            if (isset($values[$k]) && 0 < strlen($values[$k])) {
                $value = $values[$k];
                $empty = false;
            }
            $resultArray[$v] = $value;
        }
        if (!empty($values["curl_error"])) {
            $resultArray = array("Connection Error" => $values["curl_error"]);
        }
        if ($empty) {
            $resultArray = array();
        }
        return $resultArray;
    }
}

?>