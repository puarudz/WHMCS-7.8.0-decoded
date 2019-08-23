<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Tax;

class Vat
{
    const EU_COUNTRIES = array("AT" => 20, "BE" => 21, "BG" => 20, "CY" => 19, "CZ" => 21, "DE" => 19, "DK" => 25, "EE" => 20, "ES" => 21, "FI" => 24, "FR" => 20, "GB" => 20, "GR" => 24, "HR" => 25, "HU" => 27, "IE" => 23, "IT" => 22, "LT" => 21, "LU" => 17, "LV" => 21, "MT" => 18, "NL" => 21, "PL" => 23, "PT" => 23, "RO" => 19, "SE" => 25, "SI" => 22, "SK" => 20);
    public static function validateNumber($vatNumber = "")
    {
        if (class_exists("SoapClient") && \WHMCS\Config\Setting::getValue("TaxEUTaxValidation") && $vatNumber) {
            return self::sendValidateTaxNumber($vatNumber);
        }
        return true;
    }
    public static function setTaxExempt(\WHMCS\User\Client &$client)
    {
        $exempt = false;
        $taxId = $client->taxId;
        if (Vat::getFieldName() !== "tax_id") {
            $customFieldId = (int) \WHMCS\Config\Setting::getValue("TaxVatCustomFieldId");
            $taxId = $client->customFieldValues()->where("fieldid", $customFieldId)->value("value");
        }
        if (\WHMCS\Config\Setting::getValue("TaxEUTaxExempt") && $taxId) {
            $validNumber = self::sendValidateTaxNumber($taxId);
            if ($validNumber && in_array($client->country, array_keys(self::EU_COUNTRIES))) {
                $exempt = true;
                if (\WHMCS\Config\Setting::getValue("TaxEUHomeCountryNoExempt") && $client->country == \WHMCS\Config\Setting::getValue("TaxEUHomeCountry")) {
                    $exempt = false;
                }
            }
            $client->taxExempt = $exempt;
            self::removeSessionData($taxId);
        }
        return $exempt;
    }
    public static function resetNumbers()
    {
        $resetCustomNumbering = \WHMCS\Config\Setting::getValue("TaxAutoResetNumbering");
        $resetPaidNumbering = \WHMCS\Config\Setting::getValue("TaxAutoResetPaidNumbering");
        if ($resetCustomNumbering || $resetPaidNumbering) {
            $today = \WHMCS\Carbon::today()->endOfDay();
            if ($resetPaidNumbering == "monthly" || $resetCustomNumbering == "monthly") {
                $monthlyReset = \WHMCS\Carbon::today()->lastOfMonth()->endOfDay();
                if ($today->eq($monthlyReset)) {
                    if ($resetCustomNumbering == "monthly") {
                        \WHMCS\Config\Setting::setValue("TaxNextCustomInvoiceNumber", 1);
                    }
                    if ($resetPaidNumbering == "monthly") {
                        \WHMCS\Config\Setting::setValue("SequentialInvoiceNumberValue", 1);
                    }
                }
            }
            if ($resetPaidNumbering == "annually" || $resetCustomNumbering == "annually") {
                $annualReset = \WHMCS\Carbon::today()->lastOfYear()->endOfDay();
                if ($today->eq($annualReset)) {
                    if ($resetCustomNumbering == "annually") {
                        \WHMCS\Config\Setting::setValue("TaxNextCustomInvoiceNumber", 1);
                    }
                    if ($resetPaidNumbering == "annually") {
                        \WHMCS\Config\Setting::setValue("SequentialInvoiceNumberValue", 1);
                    }
                }
            }
        }
    }
    protected static function sendValidateTaxNumber($vatNumber)
    {
        $vatNumber = strtoupper($vatNumber);
        $vatNumber = preg_replace("/[^A-Z0-9]/", "", $vatNumber);
        $existingSessionValidation = \WHMCS\Session::get("TaxCodeValidation");
        $valid = 0;
        if ($existingSessionValidation) {
            $existingSessionValidation = json_decode(decrypt($existingSessionValidation), true);
            if (!is_array($existingSessionValidation)) {
                $existingSessionValidation = array();
            }
        }
        if (!array_key_exists($vatNumber, $existingSessionValidation)) {
            $vat_prefix = substr($vatNumber, 0, 2);
            $vat_num = substr($vatNumber, 2);
            try {
                $taxCheck = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl", array("connection_timeout" => 5));
                $taxValid = $taxCheck->checkVat(array("countryCode" => $vat_prefix, "vatNumber" => $vat_num));
                $existingSessionValidation[$vatNumber] = $taxValid->valid;
                $valid = $taxValid->valid;
            } catch (\Exception $e) {
                logActivity("Tax Code Check Failure - " . $vatNumber . " - " . $e->getMessage());
            }
            \WHMCS\Session::set("TaxCodeValidation", encrypt(json_encode($existingSessionValidation)));
        } else {
            $valid = $existingSessionValidation[$vatNumber];
        }
        return (bool) $valid;
    }
    protected static function removeSessionData($vatNumber)
    {
        $vatNumber = strtoupper($vatNumber);
        $vatNumber = preg_replace("/[^A-Z0-9]/", "", $vatNumber);
        $existingSessionValidation = \WHMCS\Session::get("TaxCodeValidation");
        if ($existingSessionValidation) {
            $existingSessionValidation = json_decode(decrypt($existingSessionValidation), true);
            if (!is_array($existingSessionValidation)) {
                $existingSessionValidation = array();
            }
        }
        if (array_key_exists($vatNumber, $existingSessionValidation)) {
            unset($existingSessionValidation[$vatNumber]);
        }
        \WHMCS\Session::set("TaxCodeValidation", encrypt(json_encode($existingSessionValidation)));
    }
    public static function getLabel($prefix = "tax")
    {
        $key = "taxLabel";
        if (\WHMCS\Config\Setting::getValue("TaxVATEnabled")) {
            $key = "vatLabel";
        }
        if ($prefix) {
            $key = $prefix . "." . $key;
        }
        return $key;
    }
    public static function getFieldName($contact = false)
    {
        $field = "tax_id";
        $customFieldId = (int) \WHMCS\Config\Setting::getValue("TaxVatCustomFieldId");
        if ($customFieldId && !$contact) {
            $field = "customfield[" . $customFieldId . "]";
        }
        return $field;
    }
    public static function isUsingNativeField($contact = false)
    {
        return self::isTaxEnabled() && self::isTaxIdEnabled() && self::getFieldName($contact) == "tax_id";
    }
    public static function isTaxIdEnabled()
    {
        $isTaxIDDisabled = \WHMCS\Config\Setting::getValue("TaxIDDisabled");
        if (is_null($isTaxIDDisabled)) {
            $isTaxIDDisabled = true;
        }
        return !$isTaxIDDisabled;
    }
    public static function isTaxIdDisabled()
    {
        $isTaxIDDisabled = \WHMCS\Config\Setting::getValue("TaxIDDisabled");
        if (is_null($isTaxIDDisabled)) {
            $isTaxIDDisabled = true;
        }
        return $isTaxIDDisabled;
    }
    public static function isTaxEnabled()
    {
        return (bool) \WHMCS\Config\Setting::getValue("TaxEnabled");
    }
}

?>