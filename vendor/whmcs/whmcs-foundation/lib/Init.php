<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Init
{
    protected $input = array();
    protected $last_input = NULL;
    protected $clean_variables = array("int" => array("id", "userid", "kbcid", "invoiceid", "idkb", "currency", "currencyid"), "a-z" => array("systpl", "language"), "a-z_" => array("carttpl"));
    protected $license = "";
    protected $db_host = "";
    protected $db_username = "";
    protected $db_password = "";
    protected $db_name = "";
    protected $db_sqlcharset = "";
    protected $cc_hash = "";
    protected $templates_compiledir = "";
    protected $customadminpath = "";
    public $remote_ip = "";
    protected $protected_variables = array("whmcs", "smtp_debug", "attachments_dir", "downloads_dir", "customadminpath", "mysql_charset", "overidephptimelimit", "orderform", "smartyvalues", "usingsupportmodule", "copyrighttext", "adminorder", "revokelocallicense", "allow_idn_domains", "templatefile", "_LANG", "_ADMINLANG", "display_errors", "debug_output", "mysql_errors", "moduleparams", "errormessage", "where");
    public function init()
    {
        return $this;
    }
    public function load_function($name)
    {
        $name = $this->sanitize("a-z", $name);
        $path = ROOTDIR . "/includes/" . $name . "functions.php";
        $path2 = ROOTDIR . "/includes/" . $name . ".php";
        if (file_exists($path)) {
            include_once $path;
        } else {
            if (file_exists($path2)) {
                include_once $path2;
            }
        }
    }
    public function sanitize_input_vars($arr)
    {
        $cleandata = array();
        if (is_array($arr)) {
            if (isset($arr["sqltype"])) {
                throw new Exception("Invalid request input.");
            }
            foreach ($arr as $key => $val) {
                if (ctype_alnum(str_replace(array("_", "-", ".", " "), "", $key))) {
                    if (is_array($val)) {
                        $cleandata[$key] = $this->sanitize_input_vars($val);
                    } else {
                        $val = str_replace(chr(0), "", $val);
                        $cleandata[$key] = Input\Sanitize::encode($val);
                        if (@get_magic_quotes_gpc()) {
                            $cleandata[$key] = stripslashes($cleandata[$key]);
                        }
                    }
                }
            }
        } else {
            $arr = str_replace(chr(0), "", $arr);
            $cleandata = Input\Sanitize::encode($arr);
            if (@get_magic_quotes_gpc()) {
                $cleandata = stripslashes($cleandata);
            }
        }
        return $cleandata;
    }
    public function replace_input($array)
    {
        $this->last_input = $this->input;
        $this->input = $array;
        return true;
    }
    public function reset_input()
    {
        if (is_array($this->last_input)) {
            $this->input = $this->last_input;
            return true;
        }
        return false;
    }
    public function isInRequest($key, $key2 = "")
    {
        if ($key2) {
            return isset($this->input[$key][$key2]);
        }
        return isset($this->input[$key]);
    }
    public function getFromRequest($key, $key2 = "")
    {
        return $this->get_req_var($key, $key2);
    }
    public function get_req_var($k, $k2 = "")
    {
        if ($k2) {
            return isset($this->input[$k][$k2]) ? $this->input[$k][$k2] : "";
        }
        return isset($this->input[$k]) ? $this->input[$k] : "";
    }
    public function get_req_var_if($e, $key, $fallbackarray, $fallbackarraykey = "")
    {
        if ($e) {
            $var = $this->get_req_var($key);
        } else {
            if ($fallbackarraykey) {
                $key = $fallbackarraykey;
            }
            $var = array_key_exists($key, $fallbackarray) ? $fallbackarray[$key] : "";
        }
        return $var;
    }
    protected function load_input()
    {
        foreach ($_COOKIE as $k => $v) {
            unset($_REQUEST[$k]);
        }
        foreach ($_REQUEST as $k => $v) {
            $this->input[$k] = $v;
        }
    }
    public function clean_param_array(array $params)
    {
        foreach ($this->protected_variables as $var) {
            if (isset($params[$var])) {
                unset($params[$var]);
            }
        }
        foreach ($this->clean_variables as $type => $vars) {
            foreach ($vars as $var) {
                if (isset($params[$var])) {
                    $params[$var] = $this->sanitize($type, $params[$var]);
                }
            }
        }
        return $params;
    }
    protected function clean_input()
    {
        foreach ($this->clean_variables as $type => $vars) {
            foreach ($vars as $var) {
                if (isset($this->input[$var])) {
                    $this->input[$var] = $this->sanitize($type, $this->input[$var]);
                }
            }
        }
        foreach ($this->protected_variables as $var) {
            if (isset($this->input[$var])) {
                unset($this->input[$var]);
            }
            global ${$var};
            ${$var} = "";
        }
    }
    public function sanitize($type, $var)
    {
        if ($type == "int") {
            $var = (int) $var;
        } else {
            if ($type == "a-z") {
                $var = preg_replace("/[^0-9a-z-]/i", "", $var);
            } else {
                if ($type == "a-z_") {
                    $var = preg_replace("/[^0-9a-z-_]/i", "", $var);
                } else {
                    $var = preg_replace("/[^" . $type . "]/i", "", $var);
                }
            }
        }
        return $var;
    }
    public function get_license_key()
    {
        return $this->license;
    }
    public function set_config($key, $value)
    {
        Config\Setting::setValue($key, $value);
    }
    public function get_config($key)
    {
        $setting = Config\Setting::getValue($key);
        return is_null($setting) ? "" : $setting;
    }
    public function get_template_compiledir_name()
    {
        return $this->templates_compiledir;
    }
    public function check_template_cache_writeable()
    {
        $dir = $this->get_template_compiledir_name();
        if (!is_writeable($dir)) {
            return false;
        }
        return true;
    }
    public function get_admin_folder_name()
    {
        if (isValidforPath($this->customadminpath)) {
            return $this->customadminpath;
        }
        return Config\Application::DEFAULT_ADMIN_FOLDER;
    }
    public function get_filename()
    {
        $filename = $_SERVER["PHP_SELF"];
        $filename = substr($filename, strrpos($filename, "/"));
        $filename = str_replace(array("/", ".php"), "", $filename);
        return $filename;
    }
    public function get_hash()
    {
        return $this->cc_hash;
    }
    protected function validate_templates()
    {
        global $CONFIG;
        $carttpl = $this->get_config("OrderFormTemplate");
        if (isset($_SESSION["OrderFormTemplate"])) {
            $carttpl = $_SESSION["OrderFormTemplate"];
        }
        $carttpl = $this->sanitize("a-z_", $carttpl);
        if ($carttpl == "" || !is_dir(ROOTDIR . "/templates/orderforms/" . $carttpl . "/")) {
            $carttpl = "modern";
        }
        $CONFIG["OrderFormTemplate"] = $carttpl;
    }
    public function get_lang($var)
    {
        global $_LANG;
        return isset($_LANG[$var]) ? $_LANG[$var] : "Missing Language Var " . $var;
    }
    public function in_ssl()
    {
        return array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off" || array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https";
    }
    public function getCurrencyID()
    {
        global $currency;
        return (int) $currency["id"];
    }
    public function formatPostedPhoneNumber($field = "phonenumber")
    {
        $phoneNumber = $this->getFromRequest($field);
        if ($phoneNumber && $this->isInRequest("country-calling-code-" . $field)) {
            $phoneNumber = "+" . $this->getFromRequest("country-calling-code-" . $field) . "." . $phoneNumber;
        }
        return $phoneNumber;
    }
}

?>