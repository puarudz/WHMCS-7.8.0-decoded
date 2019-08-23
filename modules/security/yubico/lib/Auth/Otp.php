<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Copyright (c) 2007-2015 Yubico AB
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above
 *       copyright notice, this list of conditions and the following
 *       disclaimer in the documentation and/or other materials provided
 *       with the distribution.
 *
 *     * Neither the name of the Yubico AB nor the names of its
 *       contributors may be used to endorse or promote products derived
 *       from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace Yubico\Auth;

/**
 * Class for verifying Yubico One-Time-Passcodes
 *
 * @category    Auth
 * @package     Auth_Yubico
 * @author      Simon Josefsson <simon@yubico.com>, Olov Danielson <olov@yubico.com>
 * @copyright   2007-2015 Yubico AB
 * @license     https://opensource.org/licenses/bsd-license.php New BSD License
 * @version     2.0
 * @link        https://www.yubico.com/
 *
 * Updated by WHMCS:
 * [2018-12-21]
 * * Include copy of BSD license as provided in source library's COPYING file
 * * Format code for PSR-1 & PSR-2 conformity
 * * Rename, relocate, and namespace class for PSR-4 compatibility
 * * Remove reliance on PEAR; never returns a PEAR_ERROR instance
 * * Always throw exceptions for errors or failed verification
 * * Update class docblock (namely usage example; leave @ annotations as-is for now)
 * * Correct method docblock annotations; remove "@access" annotations
 * * Remove legacy constructor arg for HTTPS
 *
 * Class for verifying Yubico One-Time-Passcodes
 *
 * @example
 * <code>
 * require_once 'Auth/Yubico.php';
 * $otp = "ccbbddeertkrctjkkcglfndnlihhnvekchkcctif";
 *
 * # Generate a new id+key from https://api.yubico.com/get-api-key/
 * $yubi = new \Yubico\Auth\Otp('42', 'FOOBAR=');
 * try {
 *     $auth = $yubi->verify($otp);
 *     print "<p>You are authenticated!";
 * } catch (\Exception $e) {
 *     print "<p>Authentication failed: " . $e->getMessage();
 *     print "<p>Debug output from server: " . $yubi->getLastResponse();
 * }
 * </code>
 */
class Otp
{
    /**
     * Status response values
     *
     * @link https://developers.yubico.com/OTP/Specifications/OTP_validation_protocol.html
     */
    const STATUS_REPLAYED_OTP = 'REPLAYED_OTP';
    const STATUS_OK = 'OK';
    const STATUS_BAD_OTP = 'BAD_OTP';
    const STATUS_BAD_SIGNATURE = 'BAD_SIGNATURE';
    const STATUS_MISSING_PARAMETER = 'MISSING_PARAMETER';
    const STATUS_NO_SUCH_CLIENT = 'NO_SUCH_CLIENT';
    const STATUS_OPERATION_NOT_ALLOWED = 'OPERATION_NOT_ALLOWED';
    const STATUS_BACKEND_ERROR = 'BACKEND_ERROR';
    const STATUS_NOT_ENOUGH_ANSWERS = 'NOT_ENOUGH_ANSWERS';
    const STATUS_REPLAYED_REQUEST = 'REPLAYED_REQUEST';
    /**
     * Yubico client ID
     * @type string
     */
    private $id;
    /**
     * Yubico client key
     * @type string
     */
    private $key;
    /**
     * URL part of validation server
     * @type string
     */
    private $url;
    /**
     * List with URL part of validation servers
     * @type array
     */
    private $url_list;
    /**
     * index to url_list
     * @type int
     */
    private $url_index;
    /**
     * Last query to server
     * @type string
     */
    private $lastquery;
    /**
     * Response from server
     * @type string
     */
    private $response;
    /**
     * Flag whether to verify HTTPS server certificates or not.
     * @type boolean
     */
    private $httpsverify;
    /**
     * Constructor
     *
     * Sets up the object
     * @param    string $id The client identity
     * @param    string $key The client MAC key (optional)
     * @param    boolean $httpsverify Flag whether to use verify HTTPS
     *                                 server certificates (optional,
     *                                 default true)
     */
    public function __construct($id, $key = '', $httpsverify = true)
    {
        $this->id = $id;
        $this->key = base64_decode($key);
        $this->httpsverify = $httpsverify;
    }
    /**
     * @param string $message
     * @return mixed
     */
    protected function raiseError($message)
    {
        throw new \RuntimeException($message);
    }
    /**
     * Specify to use a different URL part for verification.
     * The default is "api.yubico.com/wsapi/verify".
     *
     * @param  string $url New server URL part to use
     */
    public function setURLpart($url)
    {
        $this->url = $url;
    }
    /**
     * Get next URL part from list to use for validation.
     *
     * @return mixed string with URL part of false if no more URLs in list
     */
    public function getNextURLpart()
    {
        if ($this->url_list) {
            $url_list = $this->url_list;
        } else {
            $url_list = array('https://api.yubico.com/wsapi/2.0/verify', 'https://api2.yubico.com/wsapi/2.0/verify', 'https://api3.yubico.com/wsapi/2.0/verify', 'https://api4.yubico.com/wsapi/2.0/verify', 'https://api5.yubico.com/wsapi/2.0/verify');
        }
        if ($this->url_index >= count($url_list)) {
            return false;
        } else {
            return $url_list[$this->url_index++];
        }
    }
    /**
     * Resets index to URL list
     */
    public function URLreset()
    {
        $this->url_index = 0;
    }
    /**
     * Add another URLpart.
     *
     * @param string $URLpart
     */
    public function addURLpart($URLpart)
    {
        $this->url_list[] = $URLpart;
    }
    /**
     * Return the last query sent to the server, if any.
     *
     * @return string  Request to server
     */
    public function getLastQuery()
    {
        return $this->lastquery;
    }
    /**
     * Return the last data received from the server, if any.
     *
     * @return string  Output from server
     */
    public function getLastResponse()
    {
        return $this->response;
    }
    /**
     * Parse input string into password, yubikey prefix,
     * ciphertext, and OTP.
     *
     * @param  string    Input string to parse
     * @param  string    Optional delimiter re-class, default is '[:]'
     * @return array|false Keyed array with fields
     */
    public static function parsePasswordOTP($str, $delim = '[:]')
    {
        if (!preg_match("/^((.*)" . $delim . ")?" . "(([cbdefghijklnrtuv]{0,16})" . "([cbdefghijklnrtuv]{32}))\$/i", $str, $matches)) {
            /* Dvorak? */
            if (!preg_match("/^((.*)" . $delim . ")?" . "(([jxe\\.uidchtnbpygk]{0,16})" . "([jxe\\.uidchtnbpygk]{32}))\$/i", $str, $matches)) {
                return false;
            } else {
                $ret['otp'] = strtr($matches[3], "jxe.uidchtnbpygk", "cbdefghijklnrtuv");
            }
        } else {
            $ret['otp'] = $matches[3];
        }
        $ret['password'] = $matches[2];
        $ret['prefix'] = $matches[4];
        $ret['ciphertext'] = $matches[5];
        return $ret;
    }
    /**
     * Parse parameters from last response
     *
     * example: getParameters("timestamp", "sessioncounter", "sessionuse");
     *
     * @param  array @parameters  Array with strings representing
     *                            parameters to parse
     * @return array  parameter array from last response
     */
    public function getParameters($parameters)
    {
        if ($parameters == null) {
            $parameters = array('timestamp', 'sessioncounter', 'sessionuse');
        }
        $param_array = array();
        foreach ($parameters as $param) {
            if (!preg_match("/" . $param . "=([0-9]+)/", $this->response, $out)) {
                return $this->raiseError('Could not parse parameter ' . $param . ' from response');
            }
            $param_array[$param] = $out[1];
        }
        return $param_array;
    }
    /**
     * Verify Yubico OTP against multiple URLs
     * Protocol specification 2.0 is used to construct validation requests
     *
     * @param string $token Yubico OTP
     * @param int $use_timestamp 1=>send request with &timestamp=1 to
     *                             get timestamp and session information
     *                             in the response
     * @param boolean $wait_for_all If true, wait until all
     *                               servers responds (for debugging)
     * @param string $sl Sync level in percentage between 0
     *                             and 100 or "fast" or "secure".
     * @param int $timeout Max number of seconds to wait
     *                             for responses
     * @return mixed               PEAR error on error, true otherwise
     */
    public function verify($token, $use_timestamp = null, $wait_for_all = false, $sl = null, $timeout = null)
    {
        /* Construct parameters string */
        $ret = $this->parsePasswordOTP($token);
        if (!$ret) {
            return $this->raiseError('Could not parse Yubikey OTP');
        }
        $params = array('id' => $this->id, 'otp' => $ret['otp'], 'nonce' => md5(uniqid(rand())));
        /* Take care of protocol version 2 parameters */
        if ($use_timestamp) {
            $params['timestamp'] = 1;
        }
        if ($sl) {
            $params['sl'] = $sl;
        }
        if ($timeout) {
            $params['timeout'] = $timeout;
        }
        ksort($params);
        $parameters = '';
        foreach ($params as $p => $v) {
            $parameters .= "&" . $p . "=" . $v;
        }
        $parameters = ltrim($parameters, "&");
        /* Generate signature. */
        if ($this->key != "") {
            $signature = base64_encode(hash_hmac('sha1', $parameters, $this->key, true));
            $signature = preg_replace('/\\+/', '%2B', $signature);
            $parameters .= '&h=' . $signature;
        }
        /* Generate and prepare request. */
        $this->lastquery = null;
        $this->URLreset();
        $mh = curl_multi_init();
        $ch = array();
        while ($URLpart = $this->getNextURLpart()) {
            $query = $URLpart . "?" . $parameters;
            if ($this->lastquery) {
                $this->lastquery .= " ";
            }
            $this->lastquery .= $query;
            $handle = curl_init($query);
            curl_setopt($handle, CURLOPT_USERAGENT, "PEAR Auth_Yubico");
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            if (!$this->httpsverify) {
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
            }
            curl_setopt($handle, CURLOPT_FAILONERROR, true);
            /* If timeout is set, we better apply it here as well
                  in case the validation server fails to follow it.
               */
            if ($timeout) {
                curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
            }
            curl_multi_add_handle($mh, $handle);
            $ch[(int) $handle] = $handle;
        }
        /* Execute and read request. */
        $this->response = null;
        $replay = false;
        $valid = false;
        do {
            /* Let curl do its work. */
            while (($mrc = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM) {
            }
            while ($info = curl_multi_info_read($mh)) {
                if ($info['result'] == CURLE_OK) {
                    /* We have a complete response from one server. */
                    $str = curl_multi_getcontent($info['handle']);
                    $cinfo = curl_getinfo($info['handle']);
                    if ($wait_for_all) {
                        # Better debug info
                        $this->response .= 'URL=' . $cinfo['url'] . "\n" . $str . "\n";
                    }
                    if (preg_match("/status=([a-zA-Z0-9_]+)/", $str, $out)) {
                        $status = $out[1];
                        /*
                         * There are 3 cases.
                         *
                         * 1. OTP or Nonce values doesn't match - ignore
                         * response.
                         *
                         * 2. We have a HMAC key.  If signature is invalid -
                         * ignore response.  Return if status=OK or
                         * status=REPLAYED_OTP.
                         *
                         * 3. Return if status=OK or status=REPLAYED_OTP.
                         */
                        if (!preg_match("/otp=" . $params['otp'] . "/", $str) || !preg_match("/nonce=" . $params['nonce'] . "/", $str)) {
                            /* Case 1. Ignore response. */
                        } elseif ($this->key != "") {
                            /* Case 2. Verify signature first */
                            $rows = explode("\r\n", trim($str));
                            $response = array();
                            foreach ($rows as $key => $val) {
                                /**
                                 * the equal symbol is a BASE64 character so
                                 * only replace the first = by # (hash), which
                                 * is not used in BASE64
                                 */
                                $val = preg_replace('/=/', '#', $val, 1);
                                $row = explode("#", $val);
                                $response[$row[0]] = $row[1];
                            }
                            $parameters = array('nonce', 'otp', 'sessioncounter', 'sessionuse', 'sl', 'status', 't', 'timeout', 'timestamp');
                            sort($parameters);
                            $check = null;
                            foreach ($parameters as $param) {
                                if (array_key_exists($param, $response)) {
                                    if ($check) {
                                        $check = $check . '&';
                                    }
                                    $check = $check . $param . '=' . $response[$param];
                                }
                            }
                            $checksignature = base64_encode(hash_hmac('sha1', utf8_encode($check), $this->key, true));
                            if ($response['h'] == $checksignature) {
                                if ($status == 'REPLAYED_OTP') {
                                    if (!$wait_for_all) {
                                        $this->response = $str;
                                    }
                                    $replay = true;
                                }
                                if ($status == 'OK') {
                                    if (!$wait_for_all) {
                                        $this->response = $str;
                                    }
                                    $valid = true;
                                }
                            }
                        } else {
                            /* Case 3. We check the status directly */
                            if ($status == 'REPLAYED_OTP') {
                                if (!$wait_for_all) {
                                    $this->response = $str;
                                }
                                $replay = true;
                            }
                            if ($status == 'OK') {
                                if (!$wait_for_all) {
                                    $this->response = $str;
                                }
                                $valid = true;
                            }
                        }
                    }
                    if (!$wait_for_all && ($valid || $replay)) {
                        /* We have status=OK or status=REPLAYED_OTP, return. */
                        foreach ($ch as $h) {
                            curl_multi_remove_handle($mh, $h);
                            curl_close($h);
                        }
                        curl_multi_close($mh);
                        if ($replay) {
                            return $this->raiseError('REPLAYED_OTP');
                        }
                        if ($valid) {
                            return true;
                        }
                        return $this->raiseError($status);
                    }
                    curl_multi_remove_handle($mh, $info['handle']);
                    curl_close($info['handle']);
                    unset($ch[(int) $info['handle']]);
                }
                curl_multi_select($mh);
            }
        } while ($active);
        /* Typically this is only reached for wait_for_all=true or
         * when the timeout is reached and there is no
         * OK/REPLAYED_REQUEST answer (think firewall).
         */
        foreach ($ch as $h) {
            curl_multi_remove_handle($mh, $h);
            curl_close($h);
        }
        curl_multi_close($mh);
        if ($replay) {
            return $this->raiseError('REPLAYED_OTP');
        }
        if ($valid) {
            return true;
        }
        return $this->raiseError('NO_VALID_ANSWER');
    }
}

?>