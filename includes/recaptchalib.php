<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("RECAPTCHA_API_SERVER")) {
    define("RECAPTCHA_API_SERVER", "http://www.google.com/recaptcha/api");
}
if (!defined("RECAPTCHA_API_SECURE_SERVER")) {
    define("RECAPTCHA_API_SECURE_SERVER", "https://www.google.com/recaptcha/api");
}
if (!defined("RECAPTCHA_VERIFY_SERVER")) {
    define("RECAPTCHA_VERIFY_SERVER", "www.google.com");
}
class ReCaptchaResponse
{
    public $is_valid = NULL;
    public $error = NULL;
}
function _recaptcha_qsencode($data)
{
    $req = "";
    foreach ($data as $key => $value) {
        $req .= $key . "=" . urlencode(stripslashes($value)) . "&";
    }
    $req = substr($req, 0, strlen($req) - 1);
    return $req;
}
function _recaptcha_http_post($host, $path, $data, $port = 80)
{
    $req = _recaptcha_qsencode($data);
    $http_request = "POST " . $path . " HTTP/1.0\r\n";
    $http_request .= "Host: " . $host . "\r\n";
    $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
    $http_request .= "Content-Length: " . strlen($req) . "\r\n";
    $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
    $http_request .= "\r\n";
    $http_request .= $req;
    $response = "";
    if (false == ($fs = @fsockopen($host, $port, $errno, $errstr, 10))) {
        exit("reCAPTCHA Error: Could not open socket");
    }
    fwrite($fs, $http_request);
    while (!feof($fs)) {
        $response .= fgets($fs, 1160);
    }
    fclose($fs);
    $response = explode("\r\n\r\n", $response, 2);
    return $response;
}
function recaptcha_get_html($pubkey, $error = NULL, $use_ssl = true)
{
    if ($pubkey == NULL || $pubkey == "") {
        return "Required reCAPTCHA Keys missing from Setup > General Settings > Security";
    }
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off") {
        $use_ssl = true;
    }
    if ($use_ssl) {
        $server = RECAPTCHA_API_SECURE_SERVER;
    } else {
        $server = RECAPTCHA_API_SERVER;
    }
    $errorpart = "";
    if ($error) {
        $errorpart = "&amp;error=" . $error;
    }
    return "<script type=\"text/javascript\" src=\"" . $server . "/challenge?k=" . $pubkey . $errorpart . "\"></script>\n    <noscript>\n        <iframe src=\"" . $server . "/noscript?k=" . $pubkey . $errorpart . "\" height=\"300\" width=\"500\" frameborder=\"0\"></iframe><br/>\n        <textarea name=\"recaptcha_challenge_field\" rows=\"3\" cols=\"40\"></textarea>\n        <input type=\"hidden\" name=\"recaptcha_response_field\" value=\"manual_challenge\"/>\n    </noscript>";
}
function recaptcha_check_answer($privkey, $remoteip, $challenge, $response, $extra_params = array())
{
    if ($privkey == NULL || $privkey == "") {
        return "Required reCAPTCHA Keys missing from Setup > General Settings > Security";
    }
    if ($remoteip == NULL || $remoteip == "") {
        return "For security reasons, you must pass the remote ip to reCAPTCHA";
    }
    if ($challenge == NULL || strlen($challenge) == 0 || $response == NULL || strlen($response) == 0) {
        $recaptcha_response = new ReCaptchaResponse();
        $recaptcha_response->is_valid = false;
        $recaptcha_response->error = "incorrect-captcha-sol";
        return $recaptcha_response;
    }
    $response = _recaptcha_http_post(RECAPTCHA_VERIFY_SERVER, "/recaptcha/api/verify", array("privatekey" => $privkey, "remoteip" => $remoteip, "challenge" => $challenge, "response" => $response) + $extra_params);
    $answers = explode("\n", $response[1]);
    $recaptcha_response = new ReCaptchaResponse();
    if (trim($answers[0]) == "true") {
        $recaptcha_response->is_valid = true;
    } else {
        $recaptcha_response->is_valid = false;
        $recaptcha_response->error = $answers[1];
    }
    return $recaptcha_response;
}
function recaptcha_get_signup_url($domain = NULL, $appname = NULL)
{
    return "https://www.google.com/recaptcha/admin/create?" . _recaptcha_qsencode(array("domains" => $domain, "app" => $appname));
}
function _recaptcha_aes_pad($val)
{
    $block_size = 16;
    $numpad = $block_size - strlen($val) % $block_size;
    return str_pad($val, strlen($val) + $numpad, chr($numpad));
}
function _recaptcha_aes_encrypt($val, $ky)
{
    if (!function_exists("mcrypt_encrypt")) {
        exit("reCAPTCHA Error: To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
    }
    $mode = MCRYPT_MODE_CBC;
    $enc = MCRYPT_RIJNDAEL_128;
    $val = _recaptcha_aes_pad($val);
    return mcrypt_encrypt($enc, $ky, $val, $mode, "");
}
function _recaptcha_mailhide_urlbase64($x)
{
    return strtr(base64_encode($x), "+/", "-_");
}
function recaptcha_mailhide_url($pubkey, $privkey, $email)
{
    if ($pubkey == "" || $pubkey == NULL || $privkey == "" || $privkey == NULL) {
        exit("reCAPTCHA Error: To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " . "you can do so at <a href='http://www.google.com/recaptcha/mailhide/apikey'>http://www.google.com/recaptcha/mailhide/apikey</a>");
    }
    $ky = pack("H*", $privkey);
    $cryptmail = _recaptcha_aes_encrypt($email, $ky);
    return "http://www.google.com/recaptcha/mailhide/d?k=" . $pubkey . "&c=" . _recaptcha_mailhide_urlbase64($cryptmail);
}
function _recaptcha_mailhide_email_parts($email)
{
    $arr = preg_split("/@/", $email);
    if (strlen($arr[0]) <= 4) {
        $arr[0] = substr($arr[0], 0, 1);
    } else {
        if (strlen($arr[0]) <= 6) {
            $arr[0] = substr($arr[0], 0, 3);
        } else {
            $arr[0] = substr($arr[0], 0, 4);
        }
    }
    return $arr;
}
function recaptcha_mailhide_html($pubkey, $privkey, $email)
{
    $emailparts = _recaptcha_mailhide_email_parts($email);
    $url = recaptcha_mailhide_url($pubkey, $privkey, $email);
    return htmlentities($emailparts[0]) . "<a href='" . htmlentities($url) . "' onclick=\"window.open('" . htmlentities($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">...</a>@" . htmlentities($emailparts[1]);
}

?>