<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Validate
{
    protected $optionalFields = array();
    protected $validated = array();
    protected $errors = array();
    protected $errorMessages = array();
    public function setOptionalFields($optionalFields)
    {
        if (!is_array($optionalFields)) {
            $optionalFields = explode(",", $optionalFields);
        }
        $this->optionalFields = array_merge($this->optionalFields, $optionalFields);
        return $this;
    }
    public function validate($rule, $field, $languageKey, $field2 = "", $value = NULL)
    {
        if (in_array($field, $this->optionalFields)) {
            return false;
        }
        $this->removePreviousValidations($field);
        if ($this->runRule($rule, $field, $field2, $value)) {
            $this->validated[] = $field;
            return true;
        }
        $this->errors[] = $field;
        if ($rule === "captcha" && $languageKey === "captchaverifyincorrect" && Config\Setting::getValue("CaptchaType") === "recaptcha") {
            $languageKey = "googleRecaptchaIncorrect";
        }
        $this->addError($languageKey);
        return false;
    }
    public function reverseValidate($rule, $field, $languageKey, $field2 = "", $value = NULL)
    {
        $this->removePreviousValidations($field);
        if (!$this->runRule($rule, $field, $field2, $value)) {
            $this->validated[] = $field;
            return true;
        }
        $this->errors[] = $field;
        $this->addError($languageKey);
        return false;
    }
    public function validateCustomFields($type, $relid, $order = false, $customFields = array())
    {
        $whmcs = Application::getInstance();
        $where = array("type" => $type, "adminonly" => "");
        if ($relid) {
            $where["relid"] = (int) $relid;
        }
        if ($order) {
            $where["showorder"] = "on";
        }
        $result = select_query("tblcustomfields", "id,fieldname,fieldtype,fieldoptions,required,regexpr", $where, "sortorder` ASC,`id", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $fieldId = $data["id"];
            $fieldName = $data["fieldname"];
            $fieldOptions = $data["fieldoptions"];
            $required = $data["required"];
            $regularExpression = $data["regexpr"];
            if (strpos($fieldName, "|")) {
                $fieldName = explode("|", $fieldName);
                $fieldName = trim($fieldName[1]);
            }
            $value = isset($customFields[$fieldName]) ? $customFields[$fieldName] : null;
            if (is_null($value)) {
                $value = isset($customFields[$fieldId]) ? $customFields[$fieldId] : null;
            }
            $optionalMarker = $required ? "" : "?";
            if ($required) {
                $thisFieldFailedValidation = !$this->validate("required", "customfield[" . $fieldId . "]", (string) $fieldName . " " . $whmcs->get_lang("clientareaerrorisrequired"), "", $value);
            } else {
                $thisFieldFailedValidation = false;
            }
            if (!$thisFieldFailedValidation) {
                switch ($data["fieldtype"]) {
                    case "link":
                        $this->validate("url" . $optionalMarker, "customfield[" . $fieldId . "]", (string) $fieldName . " is an Invalid URL", "", $value);
                        break;
                    case "dropdown":
                        $this->validate("inarray" . $optionalMarker, "customfield[" . $fieldId . "]", (string) $fieldName . " Invalid Select Option", explode(",", $fieldOptions), $value);
                        break;
                    case "tickbox":
                        $this->validate("inarray" . $optionalMarker, "customfield[" . $fieldId . "]", (string) $fieldName . " Invalid Value", array("on", "1", ""), $value);
                        break;
                }
            }
            if ($regularExpression && (trim($whmcs->get_req_var("customfield", $fieldId)) || $value)) {
                $this->validate("matchpattern" . $optionalMarker, "customfield[" . $fieldId . "]", (string) $fieldName . " " . $whmcs->get_lang("customfieldvalidationerror"), array($regularExpression), $value);
            }
        }
        return true;
    }
    protected function runRule($rule, $field, $field2, $val = NULL)
    {
        $whmcs = Application::getInstance();
        if (is_null($val)) {
            if (strpos($field, "[")) {
                $k1 = explode("[", $field);
                $k2 = explode("]", $k1[1]);
                $val = $whmcs->get_req_var($k1[0], $k2[0]);
            } else {
                $val = $whmcs->get_req_var($field);
            }
        }
        $val2 = is_array($field2) ? null : $whmcs->get_req_var($field2);
        if (in_array($field, $this->optionalFields)) {
            return true;
        }
        $rule = strtolower(trim($rule));
        $allowEmpty = false;
        if (substr($rule, -1, 1) == "?") {
            $allowEmpty = true;
            $rule = substr($rule, 0, -1);
        }
        switch ($rule) {
            case "required":
                return !trim($val) ? false : true;
            case "numeric":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return is_numeric($val);
            case "minimum_length":
                return $field2 <= strlen($val);
            case "decimal":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return (bool) preg_match("/^[\\d]+(\\.[\\d]{1,2})?\$/i", $val);
            case "match_value":
                if (is_array($field2)) {
                    return $field2[0] === $field2[1];
                }
                return $val === $val2;
            case "alphanumeric":
                $checkValue = preg_replace("/[^\\w\\-]/u", "", $val);
                return $checkValue === $val;
            case "hostname":
                $checkValue = preg_replace("/[^\\w\\-\\.]/u", "", $val);
                return $checkValue === $val;
            case "matchpattern":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return preg_match($field2[0], $val);
            case "email":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return filter_var($val, FILTER_VALIDATE_EMAIL);
            case "postcode":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return !preg_replace("/[a-zA-Z0-9 \\-]/", "", $val);
            case "phone":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                $generalFormatIsValid = preg_match("/^[0-9 \\.\\-\\(\\)\\+]+\$/", $val);
                $countryCodeIsValid = strpos($val, "+") === 0 ? preg_match("/^\\+[0-9]{1,5}\\.[0-9]+/", $val) : true;
                return $generalFormatIsValid && $countryCodeIsValid;
            case "country":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                if (preg_replace("/[A-Z]/", "", $val)) {
                    return false;
                }
                if (strlen($val) != 2) {
                    return false;
                }
                return true;
            case "url":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return preg_match("|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?\$|i", $val);
            case "inarray":
                if ($allowEmpty && $val == "") {
                    return true;
                }
                return in_array($val, $field2);
            case "banneddomain":
                if (strpos($val, "@")) {
                    $val = explode("@", $val, 2);
                    $val = $val[1];
                }
                return get_query_val("tblbannedemails", "COUNT(id)", array("domain" => $val)) ? false : true;
            case "uniqueemail":
                $where = array("email" => $val);
                if (is_array($field2) && 0 < $field2[0]) {
                    $where["id"] = array("sqltype" => "NEQ", "value" => $field2[0]);
                }
                $clientExists = get_query_val("tblclients", "COUNT(id)", $where);
                if ($clientExists) {
                    return false;
                }
                $where = array("subaccount" => "1", "email" => $val);
                if (is_array($field2) && 0 < $field2[1]) {
                    $where["id"] = array("sqltype" => "NEQ", "value" => $field2[1]);
                }
                $subAccountExists = get_query_val("tblcontacts", "COUNT(id)", $where);
                if ($subAccountExists) {
                    return false;
                }
                return true;
            case "pwstrength":
                $requiredPasswordStrength = $whmcs->get_config("RequiredPWStrength");
                if (!$requiredPasswordStrength) {
                    return true;
                }
                $passwordStrength = $this->calcPasswordStrength($val);
                if ($passwordStrength < $requiredPasswordStrength) {
                    return false;
                }
                return true;
            case "captcha":
                $captcha = $whmcs->get_config("CaptchaSetting");
                if (!$captcha) {
                    return true;
                }
                if ($captcha == "offloggedin" && Session::get("uid")) {
                    return true;
                }
                return $this->checkCaptchaInput($val);
            case "fileuploads":
                return $this->checkUploadExtensions($field);
            case "password_verify":
                $hasher = new Security\Hash\Password();
                if (is_array($field2)) {
                    return $hasher->verify($field2[0], $field2[1]);
                }
                return $hasher->verify($val, $val2);
            case "unique_service_domain":
                $count = Database\Capsule::table("tblhosting")->where("domain", $val)->whereNotIn("domainstatus", array("Cancelled", "Fraud", "Terminated"))->count("id");
                return $count === 0;
            case "unique_domain":
                $ok = true;
                if (Config\Setting::find("AllowDomainsTwice")) {
                    $ok = !cartCheckIfDomainAlreadyOrdered($val);
                }
                return $ok;
            case "tax_code":
                return Billing\Tax\Vat::validateNumber($val);
        }
        return false;
    }
    protected function checkUploadExtensions($field)
    {
        if ($_FILES[$field]["name"][0] == "") {
            return true;
        }
        $uploadsAreSafe = true;
        foreach ($_FILES[$field]["name"] as $filename) {
            $filename = trim($filename);
            if ($filename && !File\Upload::isExtensionAllowed($filename)) {
                $uploadsAreSafe = false;
            }
        }
        return $uploadsAreSafe;
    }
    protected function checkCaptchaInput($val)
    {
        $captchaType = Config\Setting::getValue("CaptchaType");
        $isRecaptcha = in_array($captchaType, array(Utility\Recaptcha::CAPTCHA_RECAPTCHA, Utility\Recaptcha::CAPTCHA_INVISIBLE));
        $recaptchaPrivateKey = Config\Setting::getValue("ReCAPTCHAPrivateKey");
        if ($isRecaptcha && $recaptchaPrivateKey) {
            if (\App::isInRequest("g-recaptcha-response")) {
                $reCaptcha = new \ReCaptcha\ReCaptcha($recaptchaPrivateKey, new \ReCaptcha\RequestMethod\CurlPost());
                return $reCaptcha->verify(\App::getFromRequest("g-recaptcha-response"), Utility\Environment\CurrentUser::getIP())->isSuccess();
            }
            if (!function_exists("recaptcha_check_answer")) {
                require ROOTDIR . "/includes/recaptchalib.php";
            }
            $resp = recaptcha_check_answer($recaptchaPrivateKey, Utility\Environment\CurrentUser::getIP(), \App::getFromRequest("recaptcha_challenge_field"), \App::getFromRequest("recaptcha_response_field"));
            if (!is_object($resp)) {
                return false;
            }
            if (!$resp->is_valid) {
                return false;
            }
        } else {
            if (Session::get("captchaValue") != md5(strtoupper($val))) {
                generateNewCaptchaCode();
                return false;
            }
        }
        generateNewCaptchaCode();
        return true;
    }
    protected function calcPasswordStrength($password)
    {
        $length = strlen($password);
        $calculatedLength = $length;
        if (5 < $length) {
            $calculatedLength = 5;
        }
        $numbers = preg_replace("/[^0-9]/", "", $password);
        $numericCount = strlen($numbers);
        if (3 < $numericCount) {
            $numericCount = 3;
        }
        $symbols = preg_replace("/[^A-Za-z0-9]/", "", $password);
        $symbolCount = $length - strlen($symbols);
        if ($symbolCount < 0) {
            $symbolCount = 0;
        }
        if (3 < $symbolCount) {
            $symbolCount = 3;
        }
        $uppercase = preg_replace("/[^A-Z]/", "", $password);
        $uppercaseCount = strlen($uppercase);
        if ($uppercaseCount < 0) {
            $uppercaseCount = 0;
        }
        if (3 < $uppercaseCount) {
            $uppercaseCount = 3;
        }
        $strength = $calculatedLength * 10 - 20 + $numericCount * 10 + $symbolCount * 15 + $uppercaseCount * 10;
        return $strength;
    }
    public function addError($var)
    {
        if ($var) {
            $replacement = array();
            if (is_array($var) && array_key_exists("key", $var)) {
                if (array_key_exists("replacements", $var)) {
                    $replacement = $var["replacements"];
                }
                $var = $var["key"];
            }
            if (defined("ADMINAREA")) {
                $error = $var;
                if (is_array($var)) {
                    $error = \AdminLang::trans(implode(".", $var), $replacement);
                }
            } else {
                $error = \Lang::trans($var, $replacement);
            }
            if (!in_array($error, $this->errorMessages)) {
                $this->errorMessages[] = $error;
            }
        }
        return true;
    }
    public function addErrors(array $errors = array())
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
        return true;
    }
    public function validated($field)
    {
        if ($field) {
            return in_array($field, $this->validated);
        }
        return $this->validated;
    }
    public function error($field)
    {
        if ($field) {
            return in_array($field, $this->errors);
        }
        return $this->errors;
    }
    public function getErrorFields()
    {
        return $this->errors;
    }
    public function getErrors()
    {
        return $this->errorMessages;
    }
    public function hasErrors()
    {
        return count($this->getErrors());
    }
    public function getHTMLErrorOutput()
    {
        $code = "";
        foreach ($this->getErrors() as $errorMessage) {
            $code .= "<li>" . $errorMessage . "</li>";
        }
        return $code;
    }
    protected function removePreviousValidations($field)
    {
        if ($this->validated) {
            $alreadyValidated = array_flip($this->validated);
            unset($alreadyValidated[$field]);
            $this->validated = array_flip($alreadyValidated);
        }
    }
}

?>