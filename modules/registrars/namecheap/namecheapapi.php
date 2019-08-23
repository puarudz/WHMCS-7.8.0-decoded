<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * NamecheapRegistrarApi
 */
if (!class_exists('NamecheapRegistrarApi')) {
    class NamecheapRegistrarApi
    {
        public static $url = "https://api.namecheap.com/xml.response";
        public static $testUrl = "https://api.sandbox.namecheap.com/xml.response";
        private static $_phoneCountryCodes = array(1, 7, 20, 27, 30, 31, 32, 33, 34, 36, 39, 40, 41, 43, 44, 45, 46, 47, 48, 49, 51, 52, 54, 55, 56, 57, 58, 60, 61, 62, 63, 64, 65, 66, 81, 82, 84, 86, 90, 91, 92, 93, 94, 95, 98, 212, 213, 216, 220, 221, 222, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242, 243, 244, 245, 246, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 290, 291, 297, 298, 299, 340, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 370, 371, 372, 373, 374, 375, 376, 377, 378, 380, 381, 382, 385, 386, 387, 389, 420, 421, 423, 500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 590, 591, 592, 593, 594, 595, 596, 597, 598, 599, 618, 670, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684, 686, 687, 688, 689, 690, 691, 692, 850, 852, 853, 855, 856, 872, 880, 886, 960, 961, 962, 963, 965, 966, 967, 968, 970, 971, 972, 973, 974, 975, 976, 977, 992, 993, 994, 995, 996, 998);
        private $_apiUser;
        private $_apiKey;
        private $_testMode = true;
        private $_requestUrl;
        private $_requestParams;
        private $_response;
        public function __construct($apiUser, $apiKey, $testMode = true)
        {
            $this->_apiUser = $apiUser;
            $this->_apiKey = $apiKey;
            $this->setTestMode($testMode);
        }
        /**
         * getLastUrl
         * @return string
         */
        public function getLastUrl()
        {
            return $this->_requestUrl;
        }
        /**
         * getLastParams
         * @return string
         */
        public function getLastParams()
        {
            return $this->_requestParams;
        }
        /**
         * getLastResponse
         * @return string
         */
        public function getLastResponse()
        {
            return $this->_response;
        }
        /**
         * Parse the response into an array.
         *
         * @throws NamecheapRegistrarApiException
         *
         * @param string $response
         *
         * @return array
         */
        public function parseResponse($response)
        {
            if (false === ($xml = simplexml_load_string($response))) {
                throw new NamecheapRegistrarApiException("Unable to parse response");
            }
            $result = $this->_xml2Array($xml);
            if ("ERROR" == $result['@attributes']['Status']) {
                $errors = isset($result['Errors']['Error'][0]) ? $result['Errors']['Error'] : array($result['Errors']['Error']);
                $msg = '';
                //$err = $errors[count($errors) - 1];
                foreach ($errors as $err) {
                    $err_msg = sprintf("[%s] %s", $err['@attributes']['Number'], $err['@value']);
                    $msg .= $err_msg;
                }
                //throw new NamecheapRegistrarApiException($err_msg, $err['@attributes']['Number']);
                throw new NamecheapRegistrarApiException($msg, $err['@attributes']['Number']);
            }
            return $result['CommandResponse'];
        }
        /**
         * Send the request to the API
         *
         * @throws NamecheapRegistrarApiException
         *
         * @param string $command
         * @param array $params
         *
         * @return string
         */
        public function request($command, array $params)
        {
            $this->_requestUrl = $this->_getApiUrl($command, $params);
            $this->_requestParams = $this->_getApiParams($command, $params);
            $curl_error = false;
            if (extension_loaded("curl") && ($ch = curl_init()) !== false) {
                curl_setopt($ch, CURLOPT_URL, $this->_requestUrl);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
                // we set peer verification of namecheap server to false - else the process will fail
                // if the host server doesn't have an accurate ca bundle.
                // can turn this on, when you place an up to date ca bundle at your host server.
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                $this->_response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);
            }
            // if we didn't get a response from curl, or curl had encountered an error, do it through fsockopen.
            if (!$this->_response || $curl_error) {
                $this->_response = @file_get_contents($this->_requestUrl);
            }
            if (function_exists('logModuleCall')) {
                $logRequestParams = $this->_requestParams;
                logModuleCall('namecheap', $command, $logRequestParams, $this->_response . "\n" . $curl_error, $this->_response . "\n" . $curl_error, array($logRequestParams['ApiKey']));
            }
            if (!$this->_response) {
                throw new NamecheapRegistrarApiException($curl_error ? $curl_error : "Unable to request data from " . $this->_requestUrl);
            }
            return $this->_response;
        }
        /**
         * setTestMode
         * @param boolean $flag
         */
        public function setTestMode($flag)
        {
            $this->_testMode = (bool) $flag;
        }
        // private methods
        /**
         * formatPhone
         * @throws Exception
         *
         * @param $phone
         *
         * @return mixed
         */
        private function _formatPhone($phone)
        {
            /**
             * Namecheap API phone format requirement is +NNN.NNNNNNNNNN
             */
            // strip all non-digit characters
            $phone = preg_replace('/[^\\d]/', '', $phone);
            // check country code
            $phone_code = "";
            foreach (self::$_phoneCountryCodes as $v) {
                if (preg_match("/^{$v}\\d+\$/", $phone)) {
                    $phone_code = $v;
                    break;
                }
            }
            if (!$phone_code) {
                throw new Exception("Invalid phone number or phone country code: {$phone}");
            }
            // add '+' and dot to result phone number
            $phone = preg_replace("/^{$phone_code}/", "+{$phone_code}.", $phone);
            return $phone;
        }
        /**
         * _getApiParams
         * @param string $command
         * @param array $params
         * @return array
         */
        private function _getApiParams($command, array $params)
        {
            $params['Command'] = $command;
            $params['ApiUser'] = $this->_apiUser;
            $params['ApiKey'] = $this->_apiKey;
            if (!array_key_exists('UserName', $params) || !strlen($params['UserName'])) {
                $params['UserName'] = $params['ApiUser'];
            }
            if (!array_key_exists('ClientIp', $params)) {
                $params['ClientIp'] = $this->_getClientIp();
            }
            // format phone/fax fields
            foreach ($params as $k => &$v) {
                if (preg_match('/(Phone|Fax)/i', $k)) {
                    $v = trim($v);
                    if (!empty($v)) {
                        $v = $this->_formatPhone($v);
                    }
                }
            }
            // force EPPCode to be base64 encoded
            if (array_key_exists('EPPCode', $params)) {
                $params['EPPCode'] = htmlspecialchars_decode($params['EPPCode'], ENT_QUOTES);
                $params['EPPCode'] = "base64:" . base64_encode($params['EPPCode']);
            }
            return $params;
        }
        public function parseResultSyncHelper($items, $domainNameKey = "DomainName")
        {
            $result = array();
            if (isset($items['@attributes'])) {
                // single result was returned
                $attr = $items['@attributes'];
                $result[strtolower($attr[$domainNameKey])] = $attr;
            } else {
                // multiple results - iterate through those
                foreach ($items as $item) {
                    $attr = $item['@attributes'];
                    $result[strtolower($attr[$domainNameKey])] = $attr;
                }
            }
            return $result;
        }
        /**
         * _getApiUrl
         * @param string $command
         * @param array $params
         * @return string
         */
        private function _getApiUrl($command, array $params)
        {
            return ($this->_testMode ? self::$testUrl : self::$url) . '?' . http_build_query($this->_getApiParams($command, $params), '', '&');
        }
        /**
         * _getClientIp
         * @return string
         */
        private function _getClientIp()
        {
            $clientip = $_SERVER['REMOTE_ADDR'];
            return $clientip && $clientip != '::1' ? $clientip : "10.11.12.13";
        }
        /**
         * _xml2Array
         * @throws NamecheapRegistrarApiException
         *
         * @param $xml
         *
         * @return array|string
         */
        private function _xml2Array($xml)
        {
            if (!$xml instanceof SimpleXMLElement) {
                throw new NamecheapRegistrarApiException("Not a SimpleXMLElement object");
            }
            $result = array();
            foreach ($xml->attributes() as $attrName => $attr) {
                $result['@attributes'][$attrName] = (string) $attr;
            }
            foreach ($xml->children() as $childName => $child) {
                if (array_key_exists($childName, $result)) {
                    if (!is_array($result[$childName]) || !isset($result[$childName][1])) {
                        $result[$childName] = array($result[$childName]);
                    }
                    $result[$childName][] = $this->_xml2Array($child);
                } else {
                    $result[$childName] = $this->_xml2Array($child);
                }
            }
            $value = trim((string) $xml);
            if (array_keys($result)) {
                if ($value) {
                    $result['@value'] = $value;
                }
            } else {
                $result = $value;
            }
            return $result;
        }
    }
}
/**
 * NamecheapRegistrarApiException
 */
if (!class_exists('NamecheapRegistrarApiException')) {
    class NamecheapRegistrarApiException extends Exception
    {
    }
}
if (!class_exists('NamecheapRegistrarIDNA')) {
    class NamecheapRegistrarIDNA
    {
        private static $_codeList = array('Afrikaans' => 'afr', 'Albanian' => 'alb', 'Arabic' => 'ara', 'Aragonese' => 'arg', 'Armenian' => 'arm', 'Assamese' => 'asm', 'Asturian' => 'ast', 'Avestan' => 'ave', 'Awadhi' => 'awa', 'Azerbaijani' => 'aze', 'Balinese' => 'ban', 'Baluchi' => 'bal', 'Basa' => 'bas', 'Bashkir' => 'bak', 'Basque' => 'baq', 'Belarusian' => 'bel', 'Bengali' => 'ben', 'Bhojpuri' => 'bho', 'Bosnian' => 'bos', 'Bulgarian' => 'bul', 'Burmese' => 'bur', 'Carib' => 'car', 'Catalan' => 'cat', 'Chechen' => 'che', 'Chinese' => 'chi', 'Chuvash' => 'chv', 'Coptic' => 'cop', 'Corsican' => 'cos', 'Croatian' => 'scr', 'Czech' => 'cze', 'Danish' => 'dan', 'Divehi' => 'div', 'Dogri' => 'doi', 'Dutch' => 'dut', 'English' => 'eng', 'Estonian' => 'est', 'Faroese' => 'fao', 'Fijian' => 'fij', 'Finnish' => 'fin', 'French' => 'fre', 'Frisian' => 'fry', 'Gaelic' => 'gla', 'Georgian' => 'geo', 'German' => 'ger', 'Gondi' => 'gon', 'Greek' => 'gre', 'Gujarati' => 'guj', 'Hebrew' => 'heb', 'Hindi' => 'hin', 'Hungarian' => 'hun', 'Icelandic' => 'ice', 'Indic' => 'inc', 'Indonesian' => 'ind', 'Ingush' => 'inh', 'Irish' => 'gle', 'Italian' => 'ita', 'Japanese' => 'jpn', 'Javanese' => 'jav', 'Kashmiri' => 'kas', 'Kazakh' => 'kaz', 'Khmer' => 'khm', 'Kirghiz' => 'kir', 'Korean' => 'kor', 'Kurdish' => 'kur', 'Lao' => 'lao', 'Latvian' => 'lav', 'Lithuanian' => 'lit', 'Luxembourgish' => 'ltz', 'Macedonian' => 'mac', 'Malay' => 'may', 'Malayalam' => 'mal', 'Maltese' => 'mlt', 'Maori' => 'mao', 'Moldavian' => 'mol', 'Mongolian' => 'mon', 'Nepali' => 'nep', 'Norwegian' => 'nor', 'Oriya' => 'ori', 'Ossetian' => 'oss', 'Panjabi' => 'pan', 'Persian' => 'per', 'Polish' => 'pol', 'Portuguese' => 'por', 'Pushto' => 'pus', 'Rajasthani' => 'raj', 'Romanian' => 'rum', 'Russian' => 'rus', 'Samoan' => 'smo', 'Sanskrit' => 'san', 'Sardinian' => 'srd', 'Serbian' => 'scc', 'Sindhi' => 'snd', 'Sinhalese' => 'sin', 'Slovak' => 'slo', 'Slovenian' => 'slv', 'Somali' => 'som', 'Spanish' => 'spa', 'Swahili' => 'swa', 'Swedish' => 'swe', 'Syriac' => 'syr', 'Tajik' => 'tgk', 'Tamil' => 'tam', 'Telugu' => 'tel', 'Thai' => 'tha', 'Tibetan' => 'tib', 'Turkish' => 'tur', 'Ukrainian' => 'ukr', 'Urdu' => 'urd', 'Uzbek' => 'uzb', 'Vietnamese' => 'vie', 'Welsh' => 'wel', 'Yiddish' => 'yid');
        private static $_tldList = array('com', 'net');
        private static $_defaultIdnCode = 'eng';
        private $_sld;
        private $_tld;
        private $_sldWasEncoded = false;
        private $_tldInList = false;
        private $_encodedSld;
        public function __construct($sld, $tld)
        {
            $this->_sld = $sld;
            $this->_tld = $tld;
            if (in_array($tld, self::$_tldList)) {
                $this->_tldInList = true;
                $idna2 = new Net_IDNA2();
                try {
                    $this->_encodedSld = $idna2->encode($sld);
                } catch (Exception $e) {
                    $this->_encodedSld = $sld;
                }
                if ($sld != $this->_encodedSld) {
                    $this->_sldWasEncoded = true;
                }
            } else {
                $this->_encodedSld = $sld;
            }
            return $this;
        }
        public function getIdnCode($param)
        {
            return !empty(self::$_codeList[$param]) ? self::$_codeList[$param] : self::$_defaultIdnCode;
        }
        public function sldWasEncoded()
        {
            return $this->_sldWasEncoded;
        }
        public function getEncodedSld()
        {
            return $this->_encodedSld;
        }
        public function getCodeOptions()
        {
            return self::$_codeList;
        }
        public function getTldList()
        {
            return self::$_tldList;
        }
    }
}
/**
 * Encode/decode Internationalized Domain Names.
 *
 * The class allows to convert internationalized domain names
 * (see RFC 3490 for details) as they can be used with various registries worldwide
 * to be translated between their original (localized) form and their encoded form
 * as it will be used in the DNS (Domain Name System).
 *
 * The class provides two public methods, encode() and decode(), which do exactly
 * what you would expect them to do. You are allowed to use complete domain names,
 * simple strings and complete email addresses as well. That means, that you might
 * use any of the following notations:
 *
 * - www.n�rgler.com
 * - xn--nrgler-wxa
 * - xn--brse-5qa.xn--knrz-1ra.info
 *
 * Unicode input might be given as either UTF-8 string, UCS-4 string or UCS-4
 * array. Unicode output is available in the same formats.
 * You can select your preferred format via {@link set_paramter()}.
 *
 * ACE input and output is always expected to be ASCII.
 *
 * @package Net
 * @author  Markus Nix <mnix@docuverse.de>
 * @author  Matthias Sommerfeld <mso@phlylabs.de>
 * @author  Stefan Neufeind <pear.neufeind@speedpartner.de>
 * @version $Id: IDNA2.php 305344 2010-11-14 23:52:42Z neufeind $
 */
if (!class_exists('Net_IDNA2')) {
    class Net_IDNA2
    {
        // {{{ npdata
        /**
         * These Unicode codepoints are
         * mapped to nothing, See RFC3454 for details
         *
         * @static
         * @var array
         * @access private
         */
        private static $_np_map_nothing = array(0xad, 0x34f, 0x1806, 0x180b, 0x180c, 0x180d, 0x200b, 0x200c, 0x200d, 0x2060, 0xfe00, 0xfe01, 0xfe02, 0xfe03, 0xfe04, 0xfe05, 0xfe06, 0xfe07, 0xfe08, 0xfe09, 0xfe0a, 0xfe0b, 0xfe0c, 0xfe0d, 0xfe0e, 0xfe0f, 0xfeff);
        /**
         * Prohibited codepints
         *
         * @static
         * @var array
         * @access private
         */
        private static $_general_prohibited = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0xa, 0xb, 0xc, 0xd, 0xe, 0xf, 0x10, 0x11, 0x12, 0x13, 0x14, 0x15, 0x16, 0x17, 0x18, 0x19, 0x1a, 0x1b, 0x1c, 0x1d, 0x1e, 0x1f, 0x20, 0x21, 0x22, 0x23, 0x24, 0x25, 0x26, 0x27, 0x28, 0x29, 0x2a, 0x2b, 0x2c, 0x2f, 0x3b, 0x3c, 0x3d, 0x3e, 0x3f, 0x40, 0x5b, 0x5c, 0x5d, 0x5e, 0x5f, 0x60, 0x7b, 0x7c, 0x7d, 0x7e, 0x7f, 0x3002);
        /**
         * Codepints prohibited by Nameprep
         * @static
         * @var array
         * @access private
         */
        private static $_np_prohibit = array(0xa0, 0x1680, 0x2000, 0x2001, 0x2002, 0x2003, 0x2004, 0x2005, 0x2006, 0x2007, 0x2008, 0x2009, 0x200a, 0x200b, 0x202f, 0x205f, 0x3000, 0x6dd, 0x70f, 0x180e, 0x200c, 0x200d, 0x2028, 0x2029, 0xfeff, 0xfff9, 0xfffa, 0xfffb, 0xfffc, 0xfffe, 0xffff, 0x1fffe, 0x1ffff, 0x2fffe, 0x2ffff, 0x3fffe, 0x3ffff, 0x4fffe, 0x4ffff, 0x5fffe, 0x5ffff, 0x6fffe, 0x6ffff, 0x7fffe, 0x7ffff, 0x8fffe, 0x8ffff, 0x9fffe, 0x9ffff, 0xafffe, 0xaffff, 0xbfffe, 0xbffff, 0xcfffe, 0xcffff, 0xdfffe, 0xdffff, 0xefffe, 0xeffff, 0xffffe, 0xfffff, 0x10fffe, 0x10ffff, 0xfff9, 0xfffa, 0xfffb, 0xfffc, 0xfffd, 0x340, 0x341, 0x200e, 0x200f, 0x202a, 0x202b, 0x202c, 0x202d, 0x202e, 0x206a, 0x206b, 0x206c, 0x206d, 0x206e, 0x206f, 0xe0001);
        /**
         * Codepoint ranges prohibited by nameprep
         *
         * @static
         * @var array
         * @access private
         */
        private static $_np_prohibit_ranges = array(array(0x80, 0x9f), array(0x2060, 0x206f), array(0x1d173, 0x1d17a), array(0xe000, 0xf8ff), array(0xf0000, 0xffffd), array(0x100000, 0x10fffd), array(0xfdd0, 0xfdef), array(0xd800, 0xdfff), array(0x2ff0, 0x2ffb), array(0xe0020, 0xe007f));
        /**
         * Replacement mappings (casemapping, replacement sequences, ...)
         *
         * @static
         * @var array
         * @access private
         */
        private static $_np_replacemaps = array(
            0x41 => array(0x61),
            0x42 => array(0x62),
            0x43 => array(0x63),
            0x44 => array(0x64),
            0x45 => array(0x65),
            0x46 => array(0x66),
            0x47 => array(0x67),
            0x48 => array(0x68),
            0x49 => array(0x69),
            0x4a => array(0x6a),
            0x4b => array(0x6b),
            0x4c => array(0x6c),
            0x4d => array(0x6d),
            0x4e => array(0x6e),
            0x4f => array(0x6f),
            0x50 => array(0x70),
            0x51 => array(0x71),
            0x52 => array(0x72),
            0x53 => array(0x73),
            0x54 => array(0x74),
            0x55 => array(0x75),
            0x56 => array(0x76),
            0x57 => array(0x77),
            0x58 => array(0x78),
            0x59 => array(0x79),
            0x5a => array(0x7a),
            0xb5 => array(0x3bc),
            0xc0 => array(0xe0),
            0xc1 => array(0xe1),
            0xc2 => array(0xe2),
            0xc3 => array(0xe3),
            0xc4 => array(0xe4),
            0xc5 => array(0xe5),
            0xc6 => array(0xe6),
            0xc7 => array(0xe7),
            0xc8 => array(0xe8),
            0xc9 => array(0xe9),
            0xca => array(0xea),
            0xcb => array(0xeb),
            0xcc => array(0xec),
            0xcd => array(0xed),
            0xce => array(0xee),
            0xcf => array(0xef),
            0xd0 => array(0xf0),
            0xd1 => array(0xf1),
            0xd2 => array(0xf2),
            0xd3 => array(0xf3),
            0xd4 => array(0xf4),
            0xd5 => array(0xf5),
            0xd6 => array(0xf6),
            0xd8 => array(0xf8),
            0xd9 => array(0xf9),
            0xda => array(0xfa),
            0xdb => array(0xfb),
            0xdc => array(0xfc),
            0xdd => array(0xfd),
            0xde => array(0xfe),
            0xdf => array(0x73, 0x73),
            0x100 => array(0x101),
            0x102 => array(0x103),
            0x104 => array(0x105),
            0x106 => array(0x107),
            0x108 => array(0x109),
            0x10a => array(0x10b),
            0x10c => array(0x10d),
            0x10e => array(0x10f),
            0x110 => array(0x111),
            0x112 => array(0x113),
            0x114 => array(0x115),
            0x116 => array(0x117),
            0x118 => array(0x119),
            0x11a => array(0x11b),
            0x11c => array(0x11d),
            0x11e => array(0x11f),
            0x120 => array(0x121),
            0x122 => array(0x123),
            0x124 => array(0x125),
            0x126 => array(0x127),
            0x128 => array(0x129),
            0x12a => array(0x12b),
            0x12c => array(0x12d),
            0x12e => array(0x12f),
            0x130 => array(0x69, 0x307),
            0x132 => array(0x133),
            0x134 => array(0x135),
            0x136 => array(0x137),
            0x139 => array(0x13a),
            0x13b => array(0x13c),
            0x13d => array(0x13e),
            0x13f => array(0x140),
            0x141 => array(0x142),
            0x143 => array(0x144),
            0x145 => array(0x146),
            0x147 => array(0x148),
            0x149 => array(0x2bc, 0x6e),
            0x14a => array(0x14b),
            0x14c => array(0x14d),
            0x14e => array(0x14f),
            0x150 => array(0x151),
            0x152 => array(0x153),
            0x154 => array(0x155),
            0x156 => array(0x157),
            0x158 => array(0x159),
            0x15a => array(0x15b),
            0x15c => array(0x15d),
            0x15e => array(0x15f),
            0x160 => array(0x161),
            0x162 => array(0x163),
            0x164 => array(0x165),
            0x166 => array(0x167),
            0x168 => array(0x169),
            0x16a => array(0x16b),
            0x16c => array(0x16d),
            0x16e => array(0x16f),
            0x170 => array(0x171),
            0x172 => array(0x173),
            0x174 => array(0x175),
            0x176 => array(0x177),
            0x178 => array(0xff),
            0x179 => array(0x17a),
            0x17b => array(0x17c),
            0x17d => array(0x17e),
            0x17f => array(0x73),
            0x181 => array(0x253),
            0x182 => array(0x183),
            0x184 => array(0x185),
            0x186 => array(0x254),
            0x187 => array(0x188),
            0x189 => array(0x256),
            0x18a => array(0x257),
            0x18b => array(0x18c),
            0x18e => array(0x1dd),
            0x18f => array(0x259),
            0x190 => array(0x25b),
            0x191 => array(0x192),
            0x193 => array(0x260),
            0x194 => array(0x263),
            0x196 => array(0x269),
            0x197 => array(0x268),
            0x198 => array(0x199),
            0x19c => array(0x26f),
            0x19d => array(0x272),
            0x19f => array(0x275),
            0x1a0 => array(0x1a1),
            0x1a2 => array(0x1a3),
            0x1a4 => array(0x1a5),
            0x1a6 => array(0x280),
            0x1a7 => array(0x1a8),
            0x1a9 => array(0x283),
            0x1ac => array(0x1ad),
            0x1ae => array(0x288),
            0x1af => array(0x1b0),
            0x1b1 => array(0x28a),
            0x1b2 => array(0x28b),
            0x1b3 => array(0x1b4),
            0x1b5 => array(0x1b6),
            0x1b7 => array(0x292),
            0x1b8 => array(0x1b9),
            0x1bc => array(0x1bd),
            0x1c4 => array(0x1c6),
            0x1c5 => array(0x1c6),
            0x1c7 => array(0x1c9),
            0x1c8 => array(0x1c9),
            0x1ca => array(0x1cc),
            0x1cb => array(0x1cc),
            0x1cd => array(0x1ce),
            0x1cf => array(0x1d0),
            0x1d1 => array(0x1d2),
            0x1d3 => array(0x1d4),
            0x1d5 => array(0x1d6),
            0x1d7 => array(0x1d8),
            0x1d9 => array(0x1da),
            0x1db => array(0x1dc),
            0x1de => array(0x1df),
            0x1e0 => array(0x1e1),
            0x1e2 => array(0x1e3),
            0x1e4 => array(0x1e5),
            0x1e6 => array(0x1e7),
            0x1e8 => array(0x1e9),
            0x1ea => array(0x1eb),
            0x1ec => array(0x1ed),
            0x1ee => array(0x1ef),
            0x1f0 => array(0x6a, 0x30c),
            0x1f1 => array(0x1f3),
            0x1f2 => array(0x1f3),
            0x1f4 => array(0x1f5),
            0x1f6 => array(0x195),
            0x1f7 => array(0x1bf),
            0x1f8 => array(0x1f9),
            0x1fa => array(0x1fb),
            0x1fc => array(0x1fd),
            0x1fe => array(0x1ff),
            0x200 => array(0x201),
            0x202 => array(0x203),
            0x204 => array(0x205),
            0x206 => array(0x207),
            0x208 => array(0x209),
            0x20a => array(0x20b),
            0x20c => array(0x20d),
            0x20e => array(0x20f),
            0x210 => array(0x211),
            0x212 => array(0x213),
            0x214 => array(0x215),
            0x216 => array(0x217),
            0x218 => array(0x219),
            0x21a => array(0x21b),
            0x21c => array(0x21d),
            0x21e => array(0x21f),
            0x220 => array(0x19e),
            0x222 => array(0x223),
            0x224 => array(0x225),
            0x226 => array(0x227),
            0x228 => array(0x229),
            0x22a => array(0x22b),
            0x22c => array(0x22d),
            0x22e => array(0x22f),
            0x230 => array(0x231),
            0x232 => array(0x233),
            0x345 => array(0x3b9),
            0x37a => array(0x20, 0x3b9),
            0x386 => array(0x3ac),
            0x388 => array(0x3ad),
            0x389 => array(0x3ae),
            0x38a => array(0x3af),
            0x38c => array(0x3cc),
            0x38e => array(0x3cd),
            0x38f => array(0x3ce),
            0x390 => array(0x3b9, 0x308, 0x301),
            0x391 => array(0x3b1),
            0x392 => array(0x3b2),
            0x393 => array(0x3b3),
            0x394 => array(0x3b4),
            0x395 => array(0x3b5),
            0x396 => array(0x3b6),
            0x397 => array(0x3b7),
            0x398 => array(0x3b8),
            0x399 => array(0x3b9),
            0x39a => array(0x3ba),
            0x39b => array(0x3bb),
            0x39c => array(0x3bc),
            0x39d => array(0x3bd),
            0x39e => array(0x3be),
            0x39f => array(0x3bf),
            0x3a0 => array(0x3c0),
            0x3a1 => array(0x3c1),
            0x3a3 => array(0x3c3),
            0x3a4 => array(0x3c4),
            0x3a5 => array(0x3c5),
            0x3a6 => array(0x3c6),
            0x3a7 => array(0x3c7),
            0x3a8 => array(0x3c8),
            0x3a9 => array(0x3c9),
            0x3aa => array(0x3ca),
            0x3ab => array(0x3cb),
            0x3b0 => array(0x3c5, 0x308, 0x301),
            0x3c2 => array(0x3c3),
            0x3d0 => array(0x3b2),
            0x3d1 => array(0x3b8),
            0x3d2 => array(0x3c5),
            0x3d3 => array(0x3cd),
            0x3d4 => array(0x3cb),
            0x3d5 => array(0x3c6),
            0x3d6 => array(0x3c0),
            0x3d8 => array(0x3d9),
            0x3da => array(0x3db),
            0x3dc => array(0x3dd),
            0x3de => array(0x3df),
            0x3e0 => array(0x3e1),
            0x3e2 => array(0x3e3),
            0x3e4 => array(0x3e5),
            0x3e6 => array(0x3e7),
            0x3e8 => array(0x3e9),
            0x3ea => array(0x3eb),
            0x3ec => array(0x3ed),
            0x3ee => array(0x3ef),
            0x3f0 => array(0x3ba),
            0x3f1 => array(0x3c1),
            0x3f2 => array(0x3c3),
            0x3f4 => array(0x3b8),
            0x3f5 => array(0x3b5),
            0x400 => array(0x450),
            0x401 => array(0x451),
            0x402 => array(0x452),
            0x403 => array(0x453),
            0x404 => array(0x454),
            0x405 => array(0x455),
            0x406 => array(0x456),
            0x407 => array(0x457),
            0x408 => array(0x458),
            0x409 => array(0x459),
            0x40a => array(0x45a),
            0x40b => array(0x45b),
            0x40c => array(0x45c),
            0x40d => array(0x45d),
            0x40e => array(0x45e),
            0x40f => array(0x45f),
            0x410 => array(0x430),
            0x411 => array(0x431),
            0x412 => array(0x432),
            0x413 => array(0x433),
            0x414 => array(0x434),
            0x415 => array(0x435),
            0x416 => array(0x436),
            0x417 => array(0x437),
            0x418 => array(0x438),
            0x419 => array(0x439),
            0x41a => array(0x43a),
            0x41b => array(0x43b),
            0x41c => array(0x43c),
            0x41d => array(0x43d),
            0x41e => array(0x43e),
            0x41f => array(0x43f),
            0x420 => array(0x440),
            0x421 => array(0x441),
            0x422 => array(0x442),
            0x423 => array(0x443),
            0x424 => array(0x444),
            0x425 => array(0x445),
            0x426 => array(0x446),
            0x427 => array(0x447),
            0x428 => array(0x448),
            0x429 => array(0x449),
            0x42a => array(0x44a),
            0x42b => array(0x44b),
            0x42c => array(0x44c),
            0x42d => array(0x44d),
            0x42e => array(0x44e),
            0x42f => array(0x44f),
            0x460 => array(0x461),
            0x462 => array(0x463),
            0x464 => array(0x465),
            0x466 => array(0x467),
            0x468 => array(0x469),
            0x46a => array(0x46b),
            0x46c => array(0x46d),
            0x46e => array(0x46f),
            0x470 => array(0x471),
            0x472 => array(0x473),
            0x474 => array(0x475),
            0x476 => array(0x477),
            0x478 => array(0x479),
            0x47a => array(0x47b),
            0x47c => array(0x47d),
            0x47e => array(0x47f),
            0x480 => array(0x481),
            0x48a => array(0x48b),
            0x48c => array(0x48d),
            0x48e => array(0x48f),
            0x490 => array(0x491),
            0x492 => array(0x493),
            0x494 => array(0x495),
            0x496 => array(0x497),
            0x498 => array(0x499),
            0x49a => array(0x49b),
            0x49c => array(0x49d),
            0x49e => array(0x49f),
            0x4a0 => array(0x4a1),
            0x4a2 => array(0x4a3),
            0x4a4 => array(0x4a5),
            0x4a6 => array(0x4a7),
            0x4a8 => array(0x4a9),
            0x4aa => array(0x4ab),
            0x4ac => array(0x4ad),
            0x4ae => array(0x4af),
            0x4b0 => array(0x4b1),
            0x4b2 => array(0x4b3),
            0x4b4 => array(0x4b5),
            0x4b6 => array(0x4b7),
            0x4b8 => array(0x4b9),
            0x4ba => array(0x4bb),
            0x4bc => array(0x4bd),
            0x4be => array(0x4bf),
            0x4c1 => array(0x4c2),
            0x4c3 => array(0x4c4),
            0x4c5 => array(0x4c6),
            0x4c7 => array(0x4c8),
            0x4c9 => array(0x4ca),
            0x4cb => array(0x4cc),
            0x4cd => array(0x4ce),
            0x4d0 => array(0x4d1),
            0x4d2 => array(0x4d3),
            0x4d4 => array(0x4d5),
            0x4d6 => array(0x4d7),
            0x4d8 => array(0x4d9),
            0x4da => array(0x4db),
            0x4dc => array(0x4dd),
            0x4de => array(0x4df),
            0x4e0 => array(0x4e1),
            0x4e2 => array(0x4e3),
            0x4e4 => array(0x4e5),
            0x4e6 => array(0x4e7),
            0x4e8 => array(0x4e9),
            0x4ea => array(0x4eb),
            0x4ec => array(0x4ed),
            0x4ee => array(0x4ef),
            0x4f0 => array(0x4f1),
            0x4f2 => array(0x4f3),
            0x4f4 => array(0x4f5),
            0x4f8 => array(0x4f9),
            0x500 => array(0x501),
            0x502 => array(0x503),
            0x504 => array(0x505),
            0x506 => array(0x507),
            0x508 => array(0x509),
            0x50a => array(0x50b),
            0x50c => array(0x50d),
            0x50e => array(0x50f),
            0x531 => array(0x561),
            0x532 => array(0x562),
            0x533 => array(0x563),
            0x534 => array(0x564),
            0x535 => array(0x565),
            0x536 => array(0x566),
            0x537 => array(0x567),
            0x538 => array(0x568),
            0x539 => array(0x569),
            0x53a => array(0x56a),
            0x53b => array(0x56b),
            0x53c => array(0x56c),
            0x53d => array(0x56d),
            0x53e => array(0x56e),
            0x53f => array(0x56f),
            0x540 => array(0x570),
            0x541 => array(0x571),
            0x542 => array(0x572),
            0x543 => array(0x573),
            0x544 => array(0x574),
            0x545 => array(0x575),
            0x546 => array(0x576),
            0x547 => array(0x577),
            0x548 => array(0x578),
            0x549 => array(0x579),
            0x54a => array(0x57a),
            0x54b => array(0x57b),
            0x54c => array(0x57c),
            0x54d => array(0x57d),
            0x54e => array(0x57e),
            0x54f => array(0x57f),
            0x550 => array(0x580),
            0x551 => array(0x581),
            0x552 => array(0x582),
            0x553 => array(0x583),
            0x554 => array(0x584),
            0x555 => array(0x585),
            0x556 => array(0x586),
            0x587 => array(0x565, 0x582),
            0x1e00 => array(0x1e01),
            0x1e02 => array(0x1e03),
            0x1e04 => array(0x1e05),
            0x1e06 => array(0x1e07),
            0x1e08 => array(0x1e09),
            0x1e0a => array(0x1e0b),
            0x1e0c => array(0x1e0d),
            0x1e0e => array(0x1e0f),
            0x1e10 => array(0x1e11),
            0x1e12 => array(0x1e13),
            0x1e14 => array(0x1e15),
            0x1e16 => array(0x1e17),
            0x1e18 => array(0x1e19),
            0x1e1a => array(0x1e1b),
            0x1e1c => array(0x1e1d),
            0x1e1e => array(0x1e1f),
            0x1e20 => array(0x1e21),
            0x1e22 => array(0x1e23),
            0x1e24 => array(0x1e25),
            0x1e26 => array(0x1e27),
            0x1e28 => array(0x1e29),
            0x1e2a => array(0x1e2b),
            0x1e2c => array(0x1e2d),
            0x1e2e => array(0x1e2f),
            0x1e30 => array(0x1e31),
            0x1e32 => array(0x1e33),
            0x1e34 => array(0x1e35),
            0x1e36 => array(0x1e37),
            0x1e38 => array(0x1e39),
            0x1e3a => array(0x1e3b),
            0x1e3c => array(0x1e3d),
            0x1e3e => array(0x1e3f),
            0x1e40 => array(0x1e41),
            0x1e42 => array(0x1e43),
            0x1e44 => array(0x1e45),
            0x1e46 => array(0x1e47),
            0x1e48 => array(0x1e49),
            0x1e4a => array(0x1e4b),
            0x1e4c => array(0x1e4d),
            0x1e4e => array(0x1e4f),
            0x1e50 => array(0x1e51),
            0x1e52 => array(0x1e53),
            0x1e54 => array(0x1e55),
            0x1e56 => array(0x1e57),
            0x1e58 => array(0x1e59),
            0x1e5a => array(0x1e5b),
            0x1e5c => array(0x1e5d),
            0x1e5e => array(0x1e5f),
            0x1e60 => array(0x1e61),
            0x1e62 => array(0x1e63),
            0x1e64 => array(0x1e65),
            0x1e66 => array(0x1e67),
            0x1e68 => array(0x1e69),
            0x1e6a => array(0x1e6b),
            0x1e6c => array(0x1e6d),
            0x1e6e => array(0x1e6f),
            0x1e70 => array(0x1e71),
            0x1e72 => array(0x1e73),
            0x1e74 => array(0x1e75),
            0x1e76 => array(0x1e77),
            0x1e78 => array(0x1e79),
            0x1e7a => array(0x1e7b),
            0x1e7c => array(0x1e7d),
            0x1e7e => array(0x1e7f),
            0x1e80 => array(0x1e81),
            0x1e82 => array(0x1e83),
            0x1e84 => array(0x1e85),
            0x1e86 => array(0x1e87),
            0x1e88 => array(0x1e89),
            0x1e8a => array(0x1e8b),
            0x1e8c => array(0x1e8d),
            0x1e8e => array(0x1e8f),
            0x1e90 => array(0x1e91),
            0x1e92 => array(0x1e93),
            0x1e94 => array(0x1e95),
            0x1e96 => array(0x68, 0x331),
            0x1e97 => array(0x74, 0x308),
            0x1e98 => array(0x77, 0x30a),
            0x1e99 => array(0x79, 0x30a),
            0x1e9a => array(0x61, 0x2be),
            0x1e9b => array(0x1e61),
            0x1ea0 => array(0x1ea1),
            0x1ea2 => array(0x1ea3),
            0x1ea4 => array(0x1ea5),
            0x1ea6 => array(0x1ea7),
            0x1ea8 => array(0x1ea9),
            0x1eaa => array(0x1eab),
            0x1eac => array(0x1ead),
            0x1eae => array(0x1eaf),
            0x1eb0 => array(0x1eb1),
            0x1eb2 => array(0x1eb3),
            0x1eb4 => array(0x1eb5),
            0x1eb6 => array(0x1eb7),
            0x1eb8 => array(0x1eb9),
            0x1eba => array(0x1ebb),
            0x1ebc => array(0x1ebd),
            0x1ebe => array(0x1ebf),
            0x1ec0 => array(0x1ec1),
            0x1ec2 => array(0x1ec3),
            0x1ec4 => array(0x1ec5),
            0x1ec6 => array(0x1ec7),
            0x1ec8 => array(0x1ec9),
            0x1eca => array(0x1ecb),
            0x1ecc => array(0x1ecd),
            0x1ece => array(0x1ecf),
            0x1ed0 => array(0x1ed1),
            0x1ed2 => array(0x1ed3),
            0x1ed4 => array(0x1ed5),
            0x1ed6 => array(0x1ed7),
            0x1ed8 => array(0x1ed9),
            0x1eda => array(0x1edb),
            0x1edc => array(0x1edd),
            0x1ede => array(0x1edf),
            0x1ee0 => array(0x1ee1),
            0x1ee2 => array(0x1ee3),
            0x1ee4 => array(0x1ee5),
            0x1ee6 => array(0x1ee7),
            0x1ee8 => array(0x1ee9),
            0x1eea => array(0x1eeb),
            0x1eec => array(0x1eed),
            0x1eee => array(0x1eef),
            0x1ef0 => array(0x1ef1),
            0x1ef2 => array(0x1ef3),
            0x1ef4 => array(0x1ef5),
            0x1ef6 => array(0x1ef7),
            0x1ef8 => array(0x1ef9),
            0x1f08 => array(0x1f00),
            0x1f09 => array(0x1f01),
            0x1f0a => array(0x1f02),
            0x1f0b => array(0x1f03),
            0x1f0c => array(0x1f04),
            0x1f0d => array(0x1f05),
            0x1f0e => array(0x1f06),
            0x1f0f => array(0x1f07),
            0x1f18 => array(0x1f10),
            0x1f19 => array(0x1f11),
            0x1f1a => array(0x1f12),
            0x1f1b => array(0x1f13),
            0x1f1c => array(0x1f14),
            0x1f1d => array(0x1f15),
            0x1f28 => array(0x1f20),
            0x1f29 => array(0x1f21),
            0x1f2a => array(0x1f22),
            0x1f2b => array(0x1f23),
            0x1f2c => array(0x1f24),
            0x1f2d => array(0x1f25),
            0x1f2e => array(0x1f26),
            0x1f2f => array(0x1f27),
            0x1f38 => array(0x1f30),
            0x1f39 => array(0x1f31),
            0x1f3a => array(0x1f32),
            0x1f3b => array(0x1f33),
            0x1f3c => array(0x1f34),
            0x1f3d => array(0x1f35),
            0x1f3e => array(0x1f36),
            0x1f3f => array(0x1f37),
            0x1f48 => array(0x1f40),
            0x1f49 => array(0x1f41),
            0x1f4a => array(0x1f42),
            0x1f4b => array(0x1f43),
            0x1f4c => array(0x1f44),
            0x1f4d => array(0x1f45),
            0x1f50 => array(0x3c5, 0x313),
            0x1f52 => array(0x3c5, 0x313, 0x300),
            0x1f54 => array(0x3c5, 0x313, 0x301),
            0x1f56 => array(0x3c5, 0x313, 0x342),
            0x1f59 => array(0x1f51),
            0x1f5b => array(0x1f53),
            0x1f5d => array(0x1f55),
            0x1f5f => array(0x1f57),
            0x1f68 => array(0x1f60),
            0x1f69 => array(0x1f61),
            0x1f6a => array(0x1f62),
            0x1f6b => array(0x1f63),
            0x1f6c => array(0x1f64),
            0x1f6d => array(0x1f65),
            0x1f6e => array(0x1f66),
            0x1f6f => array(0x1f67),
            0x1f80 => array(0x1f00, 0x3b9),
            0x1f81 => array(0x1f01, 0x3b9),
            0x1f82 => array(0x1f02, 0x3b9),
            0x1f83 => array(0x1f03, 0x3b9),
            0x1f84 => array(0x1f04, 0x3b9),
            0x1f85 => array(0x1f05, 0x3b9),
            0x1f86 => array(0x1f06, 0x3b9),
            0x1f87 => array(0x1f07, 0x3b9),
            0x1f88 => array(0x1f00, 0x3b9),
            0x1f89 => array(0x1f01, 0x3b9),
            0x1f8a => array(0x1f02, 0x3b9),
            0x1f8b => array(0x1f03, 0x3b9),
            0x1f8c => array(0x1f04, 0x3b9),
            0x1f8d => array(0x1f05, 0x3b9),
            0x1f8e => array(0x1f06, 0x3b9),
            0x1f8f => array(0x1f07, 0x3b9),
            0x1f90 => array(0x1f20, 0x3b9),
            0x1f91 => array(0x1f21, 0x3b9),
            0x1f92 => array(0x1f22, 0x3b9),
            0x1f93 => array(0x1f23, 0x3b9),
            0x1f94 => array(0x1f24, 0x3b9),
            0x1f95 => array(0x1f25, 0x3b9),
            0x1f96 => array(0x1f26, 0x3b9),
            0x1f97 => array(0x1f27, 0x3b9),
            0x1f98 => array(0x1f20, 0x3b9),
            0x1f99 => array(0x1f21, 0x3b9),
            0x1f9a => array(0x1f22, 0x3b9),
            0x1f9b => array(0x1f23, 0x3b9),
            0x1f9c => array(0x1f24, 0x3b9),
            0x1f9d => array(0x1f25, 0x3b9),
            0x1f9e => array(0x1f26, 0x3b9),
            0x1f9f => array(0x1f27, 0x3b9),
            0x1fa0 => array(0x1f60, 0x3b9),
            0x1fa1 => array(0x1f61, 0x3b9),
            0x1fa2 => array(0x1f62, 0x3b9),
            0x1fa3 => array(0x1f63, 0x3b9),
            0x1fa4 => array(0x1f64, 0x3b9),
            0x1fa5 => array(0x1f65, 0x3b9),
            0x1fa6 => array(0x1f66, 0x3b9),
            0x1fa7 => array(0x1f67, 0x3b9),
            0x1fa8 => array(0x1f60, 0x3b9),
            0x1fa9 => array(0x1f61, 0x3b9),
            0x1faa => array(0x1f62, 0x3b9),
            0x1fab => array(0x1f63, 0x3b9),
            0x1fac => array(0x1f64, 0x3b9),
            0x1fad => array(0x1f65, 0x3b9),
            0x1fae => array(0x1f66, 0x3b9),
            0x1faf => array(0x1f67, 0x3b9),
            0x1fb2 => array(0x1f70, 0x3b9),
            0x1fb3 => array(0x3b1, 0x3b9),
            0x1fb4 => array(0x3ac, 0x3b9),
            0x1fb6 => array(0x3b1, 0x342),
            0x1fb7 => array(0x3b1, 0x342, 0x3b9),
            0x1fb8 => array(0x1fb0),
            0x1fb9 => array(0x1fb1),
            0x1fba => array(0x1f70),
            0x1fbb => array(0x1f71),
            0x1fbc => array(0x3b1, 0x3b9),
            0x1fbe => array(0x3b9),
            0x1fc2 => array(0x1f74, 0x3b9),
            0x1fc3 => array(0x3b7, 0x3b9),
            0x1fc4 => array(0x3ae, 0x3b9),
            0x1fc6 => array(0x3b7, 0x342),
            0x1fc7 => array(0x3b7, 0x342, 0x3b9),
            0x1fc8 => array(0x1f72),
            0x1fc9 => array(0x1f73),
            0x1fca => array(0x1f74),
            0x1fcb => array(0x1f75),
            0x1fcc => array(0x3b7, 0x3b9),
            0x1fd2 => array(0x3b9, 0x308, 0x300),
            0x1fd3 => array(0x3b9, 0x308, 0x301),
            0x1fd6 => array(0x3b9, 0x342),
            0x1fd7 => array(0x3b9, 0x308, 0x342),
            0x1fd8 => array(0x1fd0),
            0x1fd9 => array(0x1fd1),
            0x1fda => array(0x1f76),
            0x1fdb => array(0x1f77),
            0x1fe2 => array(0x3c5, 0x308, 0x300),
            0x1fe3 => array(0x3c5, 0x308, 0x301),
            0x1fe4 => array(0x3c1, 0x313),
            0x1fe6 => array(0x3c5, 0x342),
            0x1fe7 => array(0x3c5, 0x308, 0x342),
            0x1fe8 => array(0x1fe0),
            0x1fe9 => array(0x1fe1),
            0x1fea => array(0x1f7a),
            0x1feb => array(0x1f7b),
            0x1fec => array(0x1fe5),
            0x1ff2 => array(0x1f7c, 0x3b9),
            0x1ff3 => array(0x3c9, 0x3b9),
            0x1ff4 => array(0x3ce, 0x3b9),
            0x1ff6 => array(0x3c9, 0x342),
            0x1ff7 => array(0x3c9, 0x342, 0x3b9),
            0x1ff8 => array(0x1f78),
            0x1ff9 => array(0x1f79),
            0x1ffa => array(0x1f7c),
            0x1ffb => array(0x1f7d),
            0x1ffc => array(0x3c9, 0x3b9),
            0x20a8 => array(0x72, 0x73),
            0x2102 => array(0x63),
            0x2103 => array(0xb0, 0x63),
            0x2107 => array(0x25b),
            0x2109 => array(0xb0, 0x66),
            0x210b => array(0x68),
            0x210c => array(0x68),
            0x210d => array(0x68),
            0x2110 => array(0x69),
            0x2111 => array(0x69),
            0x2112 => array(0x6c),
            0x2115 => array(0x6e),
            0x2116 => array(0x6e, 0x6f),
            0x2119 => array(0x70),
            0x211a => array(0x71),
            0x211b => array(0x72),
            0x211c => array(0x72),
            0x211d => array(0x72),
            0x2120 => array(0x73, 0x6d),
            0x2121 => array(0x74, 0x65, 0x6c),
            0x2122 => array(0x74, 0x6d),
            0x2124 => array(0x7a),
            0x2126 => array(0x3c9),
            0x2128 => array(0x7a),
            0x212a => array(0x6b),
            0x212b => array(0xe5),
            0x212c => array(0x62),
            0x212d => array(0x63),
            0x2130 => array(0x65),
            0x2131 => array(0x66),
            0x2133 => array(0x6d),
            0x213e => array(0x3b3),
            0x213f => array(0x3c0),
            0x2145 => array(0x64),
            0x2160 => array(0x2170),
            0x2161 => array(0x2171),
            0x2162 => array(0x2172),
            0x2163 => array(0x2173),
            0x2164 => array(0x2174),
            0x2165 => array(0x2175),
            0x2166 => array(0x2176),
            0x2167 => array(0x2177),
            0x2168 => array(0x2178),
            0x2169 => array(0x2179),
            0x216a => array(0x217a),
            0x216b => array(0x217b),
            0x216c => array(0x217c),
            0x216d => array(0x217d),
            0x216e => array(0x217e),
            0x216f => array(0x217f),
            0x24b6 => array(0x24d0),
            0x24b7 => array(0x24d1),
            0x24b8 => array(0x24d2),
            0x24b9 => array(0x24d3),
            0x24ba => array(0x24d4),
            0x24bb => array(0x24d5),
            0x24bc => array(0x24d6),
            0x24bd => array(0x24d7),
            0x24be => array(0x24d8),
            0x24bf => array(0x24d9),
            0x24c0 => array(0x24da),
            0x24c1 => array(0x24db),
            0x24c2 => array(0x24dc),
            0x24c3 => array(0x24dd),
            0x24c4 => array(0x24de),
            0x24c5 => array(0x24df),
            0x24c6 => array(0x24e0),
            0x24c7 => array(0x24e1),
            0x24c8 => array(0x24e2),
            0x24c9 => array(0x24e3),
            0x24ca => array(0x24e4),
            0x24cb => array(0x24e5),
            0x24cc => array(0x24e6),
            0x24cd => array(0x24e7),
            0x24ce => array(0x24e8),
            0x24cf => array(0x24e9),
            0x3371 => array(0x68, 0x70, 0x61),
            0x3373 => array(0x61, 0x75),
            0x3375 => array(0x6f, 0x76),
            0x3380 => array(0x70, 0x61),
            0x3381 => array(0x6e, 0x61),
            0x3382 => array(0x3bc, 0x61),
            0x3383 => array(0x6d, 0x61),
            0x3384 => array(0x6b, 0x61),
            0x3385 => array(0x6b, 0x62),
            0x3386 => array(0x6d, 0x62),
            0x3387 => array(0x67, 0x62),
            0x338a => array(0x70, 0x66),
            0x338b => array(0x6e, 0x66),
            0x338c => array(0x3bc, 0x66),
            0x3390 => array(0x68, 0x7a),
            0x3391 => array(0x6b, 0x68, 0x7a),
            0x3392 => array(0x6d, 0x68, 0x7a),
            0x3393 => array(0x67, 0x68, 0x7a),
            0x3394 => array(0x74, 0x68, 0x7a),
            0x33a9 => array(0x70, 0x61),
            0x33aa => array(0x6b, 0x70, 0x61),
            0x33ab => array(0x6d, 0x70, 0x61),
            0x33ac => array(0x67, 0x70, 0x61),
            0x33b4 => array(0x70, 0x76),
            0x33b5 => array(0x6e, 0x76),
            0x33b6 => array(0x3bc, 0x76),
            0x33b7 => array(0x6d, 0x76),
            0x33b8 => array(0x6b, 0x76),
            0x33b9 => array(0x6d, 0x76),
            0x33ba => array(0x70, 0x77),
            0x33bb => array(0x6e, 0x77),
            0x33bc => array(0x3bc, 0x77),
            0x33bd => array(0x6d, 0x77),
            0x33be => array(0x6b, 0x77),
            0x33bf => array(0x6d, 0x77),
            0x33c0 => array(0x6b, 0x3c9),
            0x33c1 => array(0x6d, 0x3c9),
            /* 0x33C2  => array(0x61, 0x2E, 0x6D, 0x2E), */
            0x33c3 => array(0x62, 0x71),
            0x33c6 => array(0x63, 0x2215, 0x6b, 0x67),
            0x33c7 => array(0x63, 0x6f, 0x2e),
            0x33c8 => array(0x64, 0x62),
            0x33c9 => array(0x67, 0x79),
            0x33cb => array(0x68, 0x70),
            0x33cd => array(0x6b, 0x6b),
            0x33ce => array(0x6b, 0x6d),
            0x33d7 => array(0x70, 0x68),
            0x33d9 => array(0x70, 0x70, 0x6d),
            0x33da => array(0x70, 0x72),
            0x33dc => array(0x73, 0x76),
            0x33dd => array(0x77, 0x62),
            0xfb00 => array(0x66, 0x66),
            0xfb01 => array(0x66, 0x69),
            0xfb02 => array(0x66, 0x6c),
            0xfb03 => array(0x66, 0x66, 0x69),
            0xfb04 => array(0x66, 0x66, 0x6c),
            0xfb05 => array(0x73, 0x74),
            0xfb06 => array(0x73, 0x74),
            0xfb13 => array(0x574, 0x576),
            0xfb14 => array(0x574, 0x565),
            0xfb15 => array(0x574, 0x56b),
            0xfb16 => array(0x57e, 0x576),
            0xfb17 => array(0x574, 0x56d),
            0xff21 => array(0xff41),
            0xff22 => array(0xff42),
            0xff23 => array(0xff43),
            0xff24 => array(0xff44),
            0xff25 => array(0xff45),
            0xff26 => array(0xff46),
            0xff27 => array(0xff47),
            0xff28 => array(0xff48),
            0xff29 => array(0xff49),
            0xff2a => array(0xff4a),
            0xff2b => array(0xff4b),
            0xff2c => array(0xff4c),
            0xff2d => array(0xff4d),
            0xff2e => array(0xff4e),
            0xff2f => array(0xff4f),
            0xff30 => array(0xff50),
            0xff31 => array(0xff51),
            0xff32 => array(0xff52),
            0xff33 => array(0xff53),
            0xff34 => array(0xff54),
            0xff35 => array(0xff55),
            0xff36 => array(0xff56),
            0xff37 => array(0xff57),
            0xff38 => array(0xff58),
            0xff39 => array(0xff59),
            0xff3a => array(0xff5a),
            0x10400 => array(0x10428),
            0x10401 => array(0x10429),
            0x10402 => array(0x1042a),
            0x10403 => array(0x1042b),
            0x10404 => array(0x1042c),
            0x10405 => array(0x1042d),
            0x10406 => array(0x1042e),
            0x10407 => array(0x1042f),
            0x10408 => array(0x10430),
            0x10409 => array(0x10431),
            0x1040a => array(0x10432),
            0x1040b => array(0x10433),
            0x1040c => array(0x10434),
            0x1040d => array(0x10435),
            0x1040e => array(0x10436),
            0x1040f => array(0x10437),
            0x10410 => array(0x10438),
            0x10411 => array(0x10439),
            0x10412 => array(0x1043a),
            0x10413 => array(0x1043b),
            0x10414 => array(0x1043c),
            0x10415 => array(0x1043d),
            0x10416 => array(0x1043e),
            0x10417 => array(0x1043f),
            0x10418 => array(0x10440),
            0x10419 => array(0x10441),
            0x1041a => array(0x10442),
            0x1041b => array(0x10443),
            0x1041c => array(0x10444),
            0x1041d => array(0x10445),
            0x1041e => array(0x10446),
            0x1041f => array(0x10447),
            0x10420 => array(0x10448),
            0x10421 => array(0x10449),
            0x10422 => array(0x1044a),
            0x10423 => array(0x1044b),
            0x10424 => array(0x1044c),
            0x10425 => array(0x1044d),
            0x1d400 => array(0x61),
            0x1d401 => array(0x62),
            0x1d402 => array(0x63),
            0x1d403 => array(0x64),
            0x1d404 => array(0x65),
            0x1d405 => array(0x66),
            0x1d406 => array(0x67),
            0x1d407 => array(0x68),
            0x1d408 => array(0x69),
            0x1d409 => array(0x6a),
            0x1d40a => array(0x6b),
            0x1d40b => array(0x6c),
            0x1d40c => array(0x6d),
            0x1d40d => array(0x6e),
            0x1d40e => array(0x6f),
            0x1d40f => array(0x70),
            0x1d410 => array(0x71),
            0x1d411 => array(0x72),
            0x1d412 => array(0x73),
            0x1d413 => array(0x74),
            0x1d414 => array(0x75),
            0x1d415 => array(0x76),
            0x1d416 => array(0x77),
            0x1d417 => array(0x78),
            0x1d418 => array(0x79),
            0x1d419 => array(0x7a),
            0x1d434 => array(0x61),
            0x1d435 => array(0x62),
            0x1d436 => array(0x63),
            0x1d437 => array(0x64),
            0x1d438 => array(0x65),
            0x1d439 => array(0x66),
            0x1d43a => array(0x67),
            0x1d43b => array(0x68),
            0x1d43c => array(0x69),
            0x1d43d => array(0x6a),
            0x1d43e => array(0x6b),
            0x1d43f => array(0x6c),
            0x1d440 => array(0x6d),
            0x1d441 => array(0x6e),
            0x1d442 => array(0x6f),
            0x1d443 => array(0x70),
            0x1d444 => array(0x71),
            0x1d445 => array(0x72),
            0x1d446 => array(0x73),
            0x1d447 => array(0x74),
            0x1d448 => array(0x75),
            0x1d449 => array(0x76),
            0x1d44a => array(0x77),
            0x1d44b => array(0x78),
            0x1d44c => array(0x79),
            0x1d44d => array(0x7a),
            0x1d468 => array(0x61),
            0x1d469 => array(0x62),
            0x1d46a => array(0x63),
            0x1d46b => array(0x64),
            0x1d46c => array(0x65),
            0x1d46d => array(0x66),
            0x1d46e => array(0x67),
            0x1d46f => array(0x68),
            0x1d470 => array(0x69),
            0x1d471 => array(0x6a),
            0x1d472 => array(0x6b),
            0x1d473 => array(0x6c),
            0x1d474 => array(0x6d),
            0x1d475 => array(0x6e),
            0x1d476 => array(0x6f),
            0x1d477 => array(0x70),
            0x1d478 => array(0x71),
            0x1d479 => array(0x72),
            0x1d47a => array(0x73),
            0x1d47b => array(0x74),
            0x1d47c => array(0x75),
            0x1d47d => array(0x76),
            0x1d47e => array(0x77),
            0x1d47f => array(0x78),
            0x1d480 => array(0x79),
            0x1d481 => array(0x7a),
            0x1d49c => array(0x61),
            0x1d49e => array(0x63),
            0x1d49f => array(0x64),
            0x1d4a2 => array(0x67),
            0x1d4a5 => array(0x6a),
            0x1d4a6 => array(0x6b),
            0x1d4a9 => array(0x6e),
            0x1d4aa => array(0x6f),
            0x1d4ab => array(0x70),
            0x1d4ac => array(0x71),
            0x1d4ae => array(0x73),
            0x1d4af => array(0x74),
            0x1d4b0 => array(0x75),
            0x1d4b1 => array(0x76),
            0x1d4b2 => array(0x77),
            0x1d4b3 => array(0x78),
            0x1d4b4 => array(0x79),
            0x1d4b5 => array(0x7a),
            0x1d4d0 => array(0x61),
            0x1d4d1 => array(0x62),
            0x1d4d2 => array(0x63),
            0x1d4d3 => array(0x64),
            0x1d4d4 => array(0x65),
            0x1d4d5 => array(0x66),
            0x1d4d6 => array(0x67),
            0x1d4d7 => array(0x68),
            0x1d4d8 => array(0x69),
            0x1d4d9 => array(0x6a),
            0x1d4da => array(0x6b),
            0x1d4db => array(0x6c),
            0x1d4dc => array(0x6d),
            0x1d4dd => array(0x6e),
            0x1d4de => array(0x6f),
            0x1d4df => array(0x70),
            0x1d4e0 => array(0x71),
            0x1d4e1 => array(0x72),
            0x1d4e2 => array(0x73),
            0x1d4e3 => array(0x74),
            0x1d4e4 => array(0x75),
            0x1d4e5 => array(0x76),
            0x1d4e6 => array(0x77),
            0x1d4e7 => array(0x78),
            0x1d4e8 => array(0x79),
            0x1d4e9 => array(0x7a),
            0x1d504 => array(0x61),
            0x1d505 => array(0x62),
            0x1d507 => array(0x64),
            0x1d508 => array(0x65),
            0x1d509 => array(0x66),
            0x1d50a => array(0x67),
            0x1d50d => array(0x6a),
            0x1d50e => array(0x6b),
            0x1d50f => array(0x6c),
            0x1d510 => array(0x6d),
            0x1d511 => array(0x6e),
            0x1d512 => array(0x6f),
            0x1d513 => array(0x70),
            0x1d514 => array(0x71),
            0x1d516 => array(0x73),
            0x1d517 => array(0x74),
            0x1d518 => array(0x75),
            0x1d519 => array(0x76),
            0x1d51a => array(0x77),
            0x1d51b => array(0x78),
            0x1d51c => array(0x79),
            0x1d538 => array(0x61),
            0x1d539 => array(0x62),
            0x1d53b => array(0x64),
            0x1d53c => array(0x65),
            0x1d53d => array(0x66),
            0x1d53e => array(0x67),
            0x1d540 => array(0x69),
            0x1d541 => array(0x6a),
            0x1d542 => array(0x6b),
            0x1d543 => array(0x6c),
            0x1d544 => array(0x6d),
            0x1d546 => array(0x6f),
            0x1d54a => array(0x73),
            0x1d54b => array(0x74),
            0x1d54c => array(0x75),
            0x1d54d => array(0x76),
            0x1d54e => array(0x77),
            0x1d54f => array(0x78),
            0x1d550 => array(0x79),
            0x1d56c => array(0x61),
            0x1d56d => array(0x62),
            0x1d56e => array(0x63),
            0x1d56f => array(0x64),
            0x1d570 => array(0x65),
            0x1d571 => array(0x66),
            0x1d572 => array(0x67),
            0x1d573 => array(0x68),
            0x1d574 => array(0x69),
            0x1d575 => array(0x6a),
            0x1d576 => array(0x6b),
            0x1d577 => array(0x6c),
            0x1d578 => array(0x6d),
            0x1d579 => array(0x6e),
            0x1d57a => array(0x6f),
            0x1d57b => array(0x70),
            0x1d57c => array(0x71),
            0x1d57d => array(0x72),
            0x1d57e => array(0x73),
            0x1d57f => array(0x74),
            0x1d580 => array(0x75),
            0x1d581 => array(0x76),
            0x1d582 => array(0x77),
            0x1d583 => array(0x78),
            0x1d584 => array(0x79),
            0x1d585 => array(0x7a),
            0x1d5a0 => array(0x61),
            0x1d5a1 => array(0x62),
            0x1d5a2 => array(0x63),
            0x1d5a3 => array(0x64),
            0x1d5a4 => array(0x65),
            0x1d5a5 => array(0x66),
            0x1d5a6 => array(0x67),
            0x1d5a7 => array(0x68),
            0x1d5a8 => array(0x69),
            0x1d5a9 => array(0x6a),
            0x1d5aa => array(0x6b),
            0x1d5ab => array(0x6c),
            0x1d5ac => array(0x6d),
            0x1d5ad => array(0x6e),
            0x1d5ae => array(0x6f),
            0x1d5af => array(0x70),
            0x1d5b0 => array(0x71),
            0x1d5b1 => array(0x72),
            0x1d5b2 => array(0x73),
            0x1d5b3 => array(0x74),
            0x1d5b4 => array(0x75),
            0x1d5b5 => array(0x76),
            0x1d5b6 => array(0x77),
            0x1d5b7 => array(0x78),
            0x1d5b8 => array(0x79),
            0x1d5b9 => array(0x7a),
            0x1d5d4 => array(0x61),
            0x1d5d5 => array(0x62),
            0x1d5d6 => array(0x63),
            0x1d5d7 => array(0x64),
            0x1d5d8 => array(0x65),
            0x1d5d9 => array(0x66),
            0x1d5da => array(0x67),
            0x1d5db => array(0x68),
            0x1d5dc => array(0x69),
            0x1d5dd => array(0x6a),
            0x1d5de => array(0x6b),
            0x1d5df => array(0x6c),
            0x1d5e0 => array(0x6d),
            0x1d5e1 => array(0x6e),
            0x1d5e2 => array(0x6f),
            0x1d5e3 => array(0x70),
            0x1d5e4 => array(0x71),
            0x1d5e5 => array(0x72),
            0x1d5e6 => array(0x73),
            0x1d5e7 => array(0x74),
            0x1d5e8 => array(0x75),
            0x1d5e9 => array(0x76),
            0x1d5ea => array(0x77),
            0x1d5eb => array(0x78),
            0x1d5ec => array(0x79),
            0x1d5ed => array(0x7a),
            0x1d608 => array(0x61),
            0x1d609 => array(0x62),
            0x1d60a => array(0x63),
            0x1d60b => array(0x64),
            0x1d60c => array(0x65),
            0x1d60d => array(0x66),
            0x1d60e => array(0x67),
            0x1d60f => array(0x68),
            0x1d610 => array(0x69),
            0x1d611 => array(0x6a),
            0x1d612 => array(0x6b),
            0x1d613 => array(0x6c),
            0x1d614 => array(0x6d),
            0x1d615 => array(0x6e),
            0x1d616 => array(0x6f),
            0x1d617 => array(0x70),
            0x1d618 => array(0x71),
            0x1d619 => array(0x72),
            0x1d61a => array(0x73),
            0x1d61b => array(0x74),
            0x1d61c => array(0x75),
            0x1d61d => array(0x76),
            0x1d61e => array(0x77),
            0x1d61f => array(0x78),
            0x1d620 => array(0x79),
            0x1d621 => array(0x7a),
            0x1d63c => array(0x61),
            0x1d63d => array(0x62),
            0x1d63e => array(0x63),
            0x1d63f => array(0x64),
            0x1d640 => array(0x65),
            0x1d641 => array(0x66),
            0x1d642 => array(0x67),
            0x1d643 => array(0x68),
            0x1d644 => array(0x69),
            0x1d645 => array(0x6a),
            0x1d646 => array(0x6b),
            0x1d647 => array(0x6c),
            0x1d648 => array(0x6d),
            0x1d649 => array(0x6e),
            0x1d64a => array(0x6f),
            0x1d64b => array(0x70),
            0x1d64c => array(0x71),
            0x1d64d => array(0x72),
            0x1d64e => array(0x73),
            0x1d64f => array(0x74),
            0x1d650 => array(0x75),
            0x1d651 => array(0x76),
            0x1d652 => array(0x77),
            0x1d653 => array(0x78),
            0x1d654 => array(0x79),
            0x1d655 => array(0x7a),
            0x1d670 => array(0x61),
            0x1d671 => array(0x62),
            0x1d672 => array(0x63),
            0x1d673 => array(0x64),
            0x1d674 => array(0x65),
            0x1d675 => array(0x66),
            0x1d676 => array(0x67),
            0x1d677 => array(0x68),
            0x1d678 => array(0x69),
            0x1d679 => array(0x6a),
            0x1d67a => array(0x6b),
            0x1d67b => array(0x6c),
            0x1d67c => array(0x6d),
            0x1d67d => array(0x6e),
            0x1d67e => array(0x6f),
            0x1d67f => array(0x70),
            0x1d680 => array(0x71),
            0x1d681 => array(0x72),
            0x1d682 => array(0x73),
            0x1d683 => array(0x74),
            0x1d684 => array(0x75),
            0x1d685 => array(0x76),
            0x1d686 => array(0x77),
            0x1d687 => array(0x78),
            0x1d688 => array(0x79),
            0x1d689 => array(0x7a),
            0x1d6a8 => array(0x3b1),
            0x1d6a9 => array(0x3b2),
            0x1d6aa => array(0x3b3),
            0x1d6ab => array(0x3b4),
            0x1d6ac => array(0x3b5),
            0x1d6ad => array(0x3b6),
            0x1d6ae => array(0x3b7),
            0x1d6af => array(0x3b8),
            0x1d6b0 => array(0x3b9),
            0x1d6b1 => array(0x3ba),
            0x1d6b2 => array(0x3bb),
            0x1d6b3 => array(0x3bc),
            0x1d6b4 => array(0x3bd),
            0x1d6b5 => array(0x3be),
            0x1d6b6 => array(0x3bf),
            0x1d6b7 => array(0x3c0),
            0x1d6b8 => array(0x3c1),
            0x1d6b9 => array(0x3b8),
            0x1d6ba => array(0x3c3),
            0x1d6bb => array(0x3c4),
            0x1d6bc => array(0x3c5),
            0x1d6bd => array(0x3c6),
            0x1d6be => array(0x3c7),
            0x1d6bf => array(0x3c8),
            0x1d6c0 => array(0x3c9),
            0x1d6d3 => array(0x3c3),
            0x1d6e2 => array(0x3b1),
            0x1d6e3 => array(0x3b2),
            0x1d6e4 => array(0x3b3),
            0x1d6e5 => array(0x3b4),
            0x1d6e6 => array(0x3b5),
            0x1d6e7 => array(0x3b6),
            0x1d6e8 => array(0x3b7),
            0x1d6e9 => array(0x3b8),
            0x1d6ea => array(0x3b9),
            0x1d6eb => array(0x3ba),
            0x1d6ec => array(0x3bb),
            0x1d6ed => array(0x3bc),
            0x1d6ee => array(0x3bd),
            0x1d6ef => array(0x3be),
            0x1d6f0 => array(0x3bf),
            0x1d6f1 => array(0x3c0),
            0x1d6f2 => array(0x3c1),
            0x1d6f3 => array(0x3b8),
            0x1d6f4 => array(0x3c3),
            0x1d6f5 => array(0x3c4),
            0x1d6f6 => array(0x3c5),
            0x1d6f7 => array(0x3c6),
            0x1d6f8 => array(0x3c7),
            0x1d6f9 => array(0x3c8),
            0x1d6fa => array(0x3c9),
            0x1d70d => array(0x3c3),
            0x1d71c => array(0x3b1),
            0x1d71d => array(0x3b2),
            0x1d71e => array(0x3b3),
            0x1d71f => array(0x3b4),
            0x1d720 => array(0x3b5),
            0x1d721 => array(0x3b6),
            0x1d722 => array(0x3b7),
            0x1d723 => array(0x3b8),
            0x1d724 => array(0x3b9),
            0x1d725 => array(0x3ba),
            0x1d726 => array(0x3bb),
            0x1d727 => array(0x3bc),
            0x1d728 => array(0x3bd),
            0x1d729 => array(0x3be),
            0x1d72a => array(0x3bf),
            0x1d72b => array(0x3c0),
            0x1d72c => array(0x3c1),
            0x1d72d => array(0x3b8),
            0x1d72e => array(0x3c3),
            0x1d72f => array(0x3c4),
            0x1d730 => array(0x3c5),
            0x1d731 => array(0x3c6),
            0x1d732 => array(0x3c7),
            0x1d733 => array(0x3c8),
            0x1d734 => array(0x3c9),
            0x1d747 => array(0x3c3),
            0x1d756 => array(0x3b1),
            0x1d757 => array(0x3b2),
            0x1d758 => array(0x3b3),
            0x1d759 => array(0x3b4),
            0x1d75a => array(0x3b5),
            0x1d75b => array(0x3b6),
            0x1d75c => array(0x3b7),
            0x1d75d => array(0x3b8),
            0x1d75e => array(0x3b9),
            0x1d75f => array(0x3ba),
            0x1d760 => array(0x3bb),
            0x1d761 => array(0x3bc),
            0x1d762 => array(0x3bd),
            0x1d763 => array(0x3be),
            0x1d764 => array(0x3bf),
            0x1d765 => array(0x3c0),
            0x1d766 => array(0x3c1),
            0x1d767 => array(0x3b8),
            0x1d768 => array(0x3c3),
            0x1d769 => array(0x3c4),
            0x1d76a => array(0x3c5),
            0x1d76b => array(0x3c6),
            0x1d76c => array(0x3c7),
            0x1d76d => array(0x3c8),
            0x1d76e => array(0x3c9),
            0x1d781 => array(0x3c3),
            0x1d790 => array(0x3b1),
            0x1d791 => array(0x3b2),
            0x1d792 => array(0x3b3),
            0x1d793 => array(0x3b4),
            0x1d794 => array(0x3b5),
            0x1d795 => array(0x3b6),
            0x1d796 => array(0x3b7),
            0x1d797 => array(0x3b8),
            0x1d798 => array(0x3b9),
            0x1d799 => array(0x3ba),
            0x1d79a => array(0x3bb),
            0x1d79b => array(0x3bc),
            0x1d79c => array(0x3bd),
            0x1d79d => array(0x3be),
            0x1d79e => array(0x3bf),
            0x1d79f => array(0x3c0),
            0x1d7a0 => array(0x3c1),
            0x1d7a1 => array(0x3b8),
            0x1d7a2 => array(0x3c3),
            0x1d7a3 => array(0x3c4),
            0x1d7a4 => array(0x3c5),
            0x1d7a5 => array(0x3c6),
            0x1d7a6 => array(0x3c7),
            0x1d7a7 => array(0x3c8),
            0x1d7a8 => array(0x3c9),
            0x1d7bb => array(0x3c3),
            0x3f9 => array(0x3c3),
            0x1d2c => array(0x61),
            0x1d2d => array(0xe6),
            0x1d2e => array(0x62),
            0x1d30 => array(0x64),
            0x1d31 => array(0x65),
            0x1d32 => array(0x1dd),
            0x1d33 => array(0x67),
            0x1d34 => array(0x68),
            0x1d35 => array(0x69),
            0x1d36 => array(0x6a),
            0x1d37 => array(0x6b),
            0x1d38 => array(0x6c),
            0x1d39 => array(0x6d),
            0x1d3a => array(0x6e),
            0x1d3c => array(0x6f),
            0x1d3d => array(0x223),
            0x1d3e => array(0x70),
            0x1d3f => array(0x72),
            0x1d40 => array(0x74),
            0x1d41 => array(0x75),
            0x1d42 => array(0x77),
            0x213b => array(0x66, 0x61, 0x78),
            0x3250 => array(0x70, 0x74, 0x65),
            0x32cc => array(0x68, 0x67),
            0x32ce => array(0x65, 0x76),
            0x32cf => array(0x6c, 0x74, 0x64),
            0x337a => array(0x69, 0x75),
            0x33de => array(0x76, 0x2215, 0x6d),
            0x33df => array(0x61, 0x2215, 0x6d),
        );
        /**
         * Normalization Combining Classes; Code Points not listed
         * got Combining Class 0.
         *
         * @static
         * @var array
         * @access private
         */
        private static $_np_norm_combcls = array(0x334 => 1, 0x335 => 1, 0x336 => 1, 0x337 => 1, 0x338 => 1, 0x93c => 7, 0x9bc => 7, 0xa3c => 7, 0xabc => 7, 0xb3c => 7, 0xcbc => 7, 0x1037 => 7, 0x3099 => 8, 0x309a => 8, 0x94d => 9, 0x9cd => 9, 0xa4d => 9, 0xacd => 9, 0xb4d => 9, 0xbcd => 9, 0xc4d => 9, 0xccd => 9, 0xd4d => 9, 0xdca => 9, 0xe3a => 9, 0xf84 => 9, 0x1039 => 9, 0x1714 => 9, 0x1734 => 9, 0x17d2 => 9, 0x5b0 => 10, 0x5b1 => 11, 0x5b2 => 12, 0x5b3 => 13, 0x5b4 => 14, 0x5b5 => 15, 0x5b6 => 16, 0x5b7 => 17, 0x5b8 => 18, 0x5b9 => 19, 0x5bb => 20, 0x5bc => 21, 0x5bd => 22, 0x5bf => 23, 0x5c1 => 24, 0x5c2 => 25, 0xfb1e => 26, 0x64b => 27, 0x64c => 28, 0x64d => 29, 0x64e => 30, 0x64f => 31, 0x650 => 32, 0x651 => 33, 0x652 => 34, 0x670 => 35, 0x711 => 36, 0xc55 => 84, 0xc56 => 91, 0xe38 => 103, 0xe39 => 103, 0xe48 => 107, 0xe49 => 107, 0xe4a => 107, 0xe4b => 107, 0xeb8 => 118, 0xeb9 => 118, 0xec8 => 122, 0xec9 => 122, 0xeca => 122, 0xecb => 122, 0xf71 => 129, 0xf72 => 130, 0xf7a => 130, 0xf7b => 130, 0xf7c => 130, 0xf7d => 130, 0xf80 => 130, 0xf74 => 132, 0x321 => 202, 0x322 => 202, 0x327 => 202, 0x328 => 202, 0x31b => 216, 0xf39 => 216, 0x1d165 => 216, 0x1d166 => 216, 0x1d16e => 216, 0x1d16f => 216, 0x1d170 => 216, 0x1d171 => 216, 0x1d172 => 216, 0x302a => 218, 0x316 => 220, 0x317 => 220, 0x318 => 220, 0x319 => 220, 0x31c => 220, 0x31d => 220, 0x31e => 220, 0x31f => 220, 0x320 => 220, 0x323 => 220, 0x324 => 220, 0x325 => 220, 0x326 => 220, 0x329 => 220, 0x32a => 220, 0x32b => 220, 0x32c => 220, 0x32d => 220, 0x32e => 220, 0x32f => 220, 0x330 => 220, 0x331 => 220, 0x332 => 220, 0x333 => 220, 0x339 => 220, 0x33a => 220, 0x33b => 220, 0x33c => 220, 0x347 => 220, 0x348 => 220, 0x349 => 220, 0x34d => 220, 0x34e => 220, 0x353 => 220, 0x354 => 220, 0x355 => 220, 0x356 => 220, 0x591 => 220, 0x596 => 220, 0x59b => 220, 0x5a3 => 220, 0x5a4 => 220, 0x5a5 => 220, 0x5a6 => 220, 0x5a7 => 220, 0x5aa => 220, 0x655 => 220, 0x656 => 220, 0x6e3 => 220, 0x6ea => 220, 0x6ed => 220, 0x731 => 220, 0x734 => 220, 0x737 => 220, 0x738 => 220, 0x739 => 220, 0x73b => 220, 0x73c => 220, 0x73e => 220, 0x742 => 220, 0x744 => 220, 0x746 => 220, 0x748 => 220, 0x952 => 220, 0xf18 => 220, 0xf19 => 220, 0xf35 => 220, 0xf37 => 220, 0xfc6 => 220, 0x193b => 220, 0x20e8 => 220, 0x1d17b => 220, 0x1d17c => 220, 0x1d17d => 220, 0x1d17e => 220, 0x1d17f => 220, 0x1d180 => 220, 0x1d181 => 220, 0x1d182 => 220, 0x1d18a => 220, 0x1d18b => 220, 0x59a => 222, 0x5ad => 222, 0x1929 => 222, 0x302d => 222, 0x302e => 224, 0x302f => 224, 0x1d16d => 226, 0x5ae => 228, 0x18a9 => 228, 0x302b => 228, 0x300 => 230, 0x301 => 230, 0x302 => 230, 0x303 => 230, 0x304 => 230, 0x305 => 230, 0x306 => 230, 0x307 => 230, 0x308 => 230, 0x309 => 230, 0x30a => 230, 0x30b => 230, 0x30c => 230, 0x30d => 230, 0x30e => 230, 0x30f => 230, 0x310 => 230, 0x311 => 230, 0x312 => 230, 0x313 => 230, 0x314 => 230, 0x33d => 230, 0x33e => 230, 0x33f => 230, 0x340 => 230, 0x341 => 230, 0x342 => 230, 0x343 => 230, 0x344 => 230, 0x346 => 230, 0x34a => 230, 0x34b => 230, 0x34c => 230, 0x350 => 230, 0x351 => 230, 0x352 => 230, 0x357 => 230, 0x363 => 230, 0x364 => 230, 0x365 => 230, 0x366 => 230, 0x367 => 230, 0x368 => 230, 0x369 => 230, 0x36a => 230, 0x36b => 230, 0x36c => 230, 0x36d => 230, 0x36e => 230, 0x36f => 230, 0x483 => 230, 0x484 => 230, 0x485 => 230, 0x486 => 230, 0x592 => 230, 0x593 => 230, 0x594 => 230, 0x595 => 230, 0x597 => 230, 0x598 => 230, 0x599 => 230, 0x59c => 230, 0x59d => 230, 0x59e => 230, 0x59f => 230, 0x5a0 => 230, 0x5a1 => 230, 0x5a8 => 230, 0x5a9 => 230, 0x5ab => 230, 0x5ac => 230, 0x5af => 230, 0x5c4 => 230, 0x610 => 230, 0x611 => 230, 0x612 => 230, 0x613 => 230, 0x614 => 230, 0x615 => 230, 0x653 => 230, 0x654 => 230, 0x657 => 230, 0x658 => 230, 0x6d6 => 230, 0x6d7 => 230, 0x6d8 => 230, 0x6d9 => 230, 0x6da => 230, 0x6db => 230, 0x6dc => 230, 0x6df => 230, 0x6e0 => 230, 0x6e1 => 230, 0x6e2 => 230, 0x6e4 => 230, 0x6e7 => 230, 0x6e8 => 230, 0x6eb => 230, 0x6ec => 230, 0x730 => 230, 0x732 => 230, 0x733 => 230, 0x735 => 230, 0x736 => 230, 0x73a => 230, 0x73d => 230, 0x73f => 230, 0x740 => 230, 0x741 => 230, 0x743 => 230, 0x745 => 230, 0x747 => 230, 0x749 => 230, 0x74a => 230, 0x951 => 230, 0x953 => 230, 0x954 => 230, 0xf82 => 230, 0xf83 => 230, 0xf86 => 230, 0xf87 => 230, 0x170d => 230, 0x193a => 230, 0x20d0 => 230, 0x20d1 => 230, 0x20d4 => 230, 0x20d5 => 230, 0x20d6 => 230, 0x20d7 => 230, 0x20db => 230, 0x20dc => 230, 0x20e1 => 230, 0x20e7 => 230, 0x20e9 => 230, 0xfe20 => 230, 0xfe21 => 230, 0xfe22 => 230, 0xfe23 => 230, 0x1d185 => 230, 0x1d186 => 230, 0x1d187 => 230, 0x1d189 => 230, 0x1d188 => 230, 0x1d1aa => 230, 0x1d1ab => 230, 0x1d1ac => 230, 0x1d1ad => 230, 0x315 => 232, 0x31a => 232, 0x302c => 232, 0x35f => 233, 0x362 => 233, 0x35d => 234, 0x35e => 234, 0x360 => 234, 0x361 => 234, 0x345 => 240);
        // }}}
        // {{{ properties
        /**
         * @var string
         * @access private
         */
        private $_punycode_prefix = 'xn--';
        /**
         * @access private
         */
        private $_invalid_ucs = 2147483648.0;
        /**
         * @access private
         */
        private $_max_ucs = 0x10ffff;
        /**
         * @var int
         * @access private
         */
        private $_base = 36;
        /**
         * @var int
         * @access private
         */
        private $_tmin = 1;
        /**
         * @var int
         * @access private
         */
        private $_tmax = 26;
        /**
         * @var int
         * @access private
         */
        private $_skew = 38;
        /**
         * @var int
         * @access private
         */
        private $_damp = 700;
        /**
         * @var int
         * @access private
         */
        private $_initial_bias = 72;
        /**
         * @var int
         * @access private
         */
        private $_initial_n = 0x80;
        /**
         * @var int
         * @access private
         */
        private $_slast;
        /**
         * @access private
         */
        private $_sbase = 0xac00;
        /**
         * @access private
         */
        private $_lbase = 0x1100;
        /**
         * @access private
         */
        private $_vbase = 0x1161;
        /**
         * @access private
         */
        private $_tbase = 0x11a7;
        /**
         * @var int
         * @access private
         */
        private $_lcount = 19;
        /**
         * @var int
         * @access private
         */
        private $_vcount = 21;
        /**
         * @var int
         * @access private
         */
        private $_tcount = 28;
        /**
         * vcount * tcount
         *
         * @var int
         * @access private
         */
        private $_ncount = 588;
        /**
         * lcount * tcount * vcount
         *
         * @var int
         * @access private
         */
        private $_scount = 11172;
        /**
         * Default encoding for encode()'s input and decode()'s output is UTF-8;
         * Other possible encodings are ucs4_string and ucs4_array
         * See {@link setParams()} for how to select these
         *
         * @var bool
         * @access private
         */
        private $_api_encoding = 'utf8';
        /**
         * Overlong UTF-8 encodings are forbidden
         *
         * @var bool
         * @access private
         */
        private $_allow_overlong = false;
        /**
         * Behave strict or not
         *
         * @var bool
         * @access private
         */
        private $_strict_mode = false;
        /**
         * IDNA-version to use
         *
         * Values are "2003" and "2008".
         * Defaults to "2003", since that was the original version and for
         * compatibility with previous versions of this library.
         * If you need to encode "new" characters like the German "Eszett",
         * please switch to 2008 first before encoding.
         *
         * @var bool
         * @access private
         */
        private $_version = '2003';
        /**
         * Cached value indicating whether or not mbstring function overloading is
         * on for strlen
         *
         * This is cached for optimal performance.
         *
         * @var boolean
         * @see Net_IDNA2::_byteLength()
         */
        private static $_mb_string_overload = null;
        // }}}
        // {{{ constructor
        /**
         * Constructor
         *
         * @param array $options Options to initialise the object with
         *
         * @access public
         * @see    setParams()
         */
        public function __construct($options = null)
        {
            $this->_slast = $this->_sbase + $this->_lcount * $this->_vcount * $this->_tcount;
            if (is_array($options)) {
                $this->setParams($options);
            }
            // populate mbstring overloading cache if not set
            if (self::$_mb_string_overload === null) {
                self::$_mb_string_overload = extension_loaded('mbstring') && (ini_get('mbstring.func_overload') & 0x2) === 0x2;
            }
        }
        // }}}
        /**
         * Sets a new option value. Available options and values:
         *
         * [utf8 -     Use either UTF-8 or ISO-8859-1 as input (true for UTF-8, false
         *             otherwise); The output is always UTF-8]
         * [overlong - Unicode does not allow unnecessarily long encodings of chars,
         *             to allow this, set this parameter to true, else to false;
         *             default is false.]
         * [strict -   true: strict mode, good for registration purposes - Causes errors
         *             on failures; false: loose mode, ideal for "wildlife" applications
         *             by silently ignoring errors and returning the original input instead]
         *
         * @throws InvalidArgumentException
         *
         * @param string|array  $option Parameter to set (string: single parameter; array of Parameter => Value pairs)
         * @param boolean $value  Value to use (if parameter 1 is a string)
         *
         * @return boolean       true on success, false otherwise
         * @access public
         */
        public function setParams($option, $value = false)
        {
            if (!is_array($option)) {
                $option = array($option => $value);
            }
            foreach ($option as $k => $v) {
                switch ($k) {
                    case 'encoding':
                        switch ($v) {
                            case 'utf8':
                            case 'ucs4_string':
                            case 'ucs4_array':
                                $this->_api_encoding = $v;
                                break;
                            default:
                                throw new InvalidArgumentException('Set Parameter: Unknown parameter ' . $v . ' for option ' . $k);
                        }
                        break;
                    case 'overlong':
                        $this->_allow_overlong = $v ? true : false;
                        break;
                    case 'strict':
                        $this->_strict_mode = $v ? true : false;
                        break;
                    case 'version':
                        if (in_array($v, array('2003', '2008'))) {
                            $this->_version = $v;
                        } else {
                            throw new InvalidArgumentException('Set Parameter: Invalid parameter ' . $v . ' for option ' . $k);
                        }
                        break;
                    default:
                        return false;
                }
            }
            return true;
        }
        /**
         * Encode a given UTF-8 domain name.
         *
         * @param string $decoded           Domain name (UTF-8 or UCS-4)
         * @param boolean $one_time_encoding Desired input encoding, see {@link set_parameter}
         *                                  If not given will use default-encoding
         *
         * @return string Encoded Domain name (ACE string)
         * @return mixed  processed string
         * @throws InvalidArgumentException
         * @access public
         */
        public function encode($decoded, $one_time_encoding = false)
        {
            // Forcing conversion of input to UCS4 array
            // If one time encoding is given, use this, else the objects property
            switch ($one_time_encoding ? $one_time_encoding : $this->_api_encoding) {
                case 'utf8':
                    $decoded = $this->_utf8_to_ucs4($decoded);
                    break;
                case 'ucs4_string':
                    $decoded = $this->_ucs4_string_to_ucs4($decoded);
                case 'ucs4_array':
                    // No break; before this line. Catch case, but do nothing
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported input format');
            }
            // No input, no output, what else did you expect?
            if (empty($decoded)) {
                return '';
            }
            // Anchors for iteration
            $last_begin = 0;
            // Output string
            $output = '';
            foreach ($decoded as $k => $v) {
                // Make sure to use just the plain dot
                switch ($v) {
                    case 0x3002:
                    case 0xff0e:
                    case 0xff61:
                        $decoded[$k] = 0x2e;
                    // It's right, no break here
                    // The codepoints above have to be converted to dots anyway
                    // Stumbling across an anchoring character
                    case 0x2e:
                    case 0x2f:
                    case 0x3a:
                    case 0x3f:
                    case 0x40:
                        // Neither email addresses nor URLs allowed in strict mode
                        if ($this->_strict_mode) {
                            throw new InvalidArgumentException('Neither email addresses nor URLs are allowed in strict mode.');
                        }
                        // Skip first char
                        if ($k) {
                            $encoded = '';
                            $encoded = $this->_encode(array_slice($decoded, $last_begin, $k - $last_begin));
                            if ($encoded) {
                                $output .= $encoded;
                            } else {
                                $output .= $this->_ucs4_to_utf8(array_slice($decoded, $last_begin, $k - $last_begin));
                            }
                            $output .= chr($decoded[$k]);
                        }
                        $last_begin = $k + 1;
                }
            }
            // Catch the rest of the string
            if ($last_begin) {
                $inp_len = sizeof($decoded);
                $encoded = '';
                $encoded = $this->_encode(array_slice($decoded, $last_begin, $inp_len - $last_begin));
                if ($encoded) {
                    $output .= $encoded;
                } else {
                    $output .= $this->_ucs4_to_utf8(array_slice($decoded, $last_begin, $inp_len - $last_begin));
                }
                return $output;
            }
            if ($output = $this->_encode($decoded)) {
                return $output;
            }
            return $this->_ucs4_to_utf8($decoded);
        }
        /**
         * Decode a given ACE domain name.
         *
         * @param string $input             Domain name (ACE string)
         * @param bool $one_time_encoding Desired output encoding, see {@link set_parameter}
         *
         * @return array|string                   Decoded Domain name (UTF-8 or UCS-4)
         * @throws InvalidArgumentException
         * @access public
         */
        public function decode($input, $one_time_encoding = false)
        {
            // Optionally set
            if ($one_time_encoding) {
                switch ($one_time_encoding) {
                    case 'utf8':
                    case 'ucs4_string':
                    case 'ucs4_array':
                        break;
                    default:
                        throw new InvalidArgumentException('Unknown encoding ' . $one_time_encoding);
                }
            }
            // Make sure to drop any newline characters around
            $input = trim($input);
            // Negotiate input and try to determine, wether it is a plain string,
            // an email address or something like a complete URL
            if (strpos($input, '@')) {
                // Maybe it is an email address
                // No no in strict mode
                if ($this->_strict_mode) {
                    throw new InvalidArgumentException('Only simple domain name parts can be handled in strict mode');
                }
                list($email_pref, $input) = explode('@', $input, 2);
                $arr = explode('.', $input);
                foreach ($arr as $k => $v) {
                    $conv = $this->_decode($v);
                    if ($conv) {
                        $arr[$k] = $conv;
                    }
                }
                $return = $email_pref . '@' . join('.', $arr);
            } elseif (preg_match('![:\\./]!', $input)) {
                // Or a complete domain name (with or without paths / parameters)
                // No no in strict mode
                if ($this->_strict_mode) {
                    throw new InvalidArgumentException('Only simple domain name parts can be handled in strict mode');
                }
                $parsed = parse_url($input);
                if (isset($parsed['host'])) {
                    $arr = explode('.', $parsed['host']);
                    foreach ($arr as $k => $v) {
                        $conv = $this->_decode($v);
                        if ($conv) {
                            $arr[$k] = $conv;
                        }
                    }
                    $parsed['host'] = join('.', $arr);
                    if (isset($parsed['scheme'])) {
                        $parsed['scheme'] .= strtolower($parsed['scheme']) == 'mailto' ? ':' : '://';
                    }
                    $return = $this->_unparse_url($parsed);
                } else {
                    // parse_url seems to have failed, try without it
                    $arr = explode('.', $input);
                    foreach ($arr as $k => $v) {
                        $conv = $this->_decode($v);
                        if ($conv) {
                            $arr[$k] = $conv;
                        }
                    }
                    $return = join('.', $arr);
                }
            } else {
                // Otherwise we consider it being a pure domain name string
                $return = $this->_decode($input);
            }
            // The output is UTF-8 by default, other output formats need conversion here
            // If one time encoding is given, use this, else the objects property
            switch ($one_time_encoding ? $one_time_encoding : $this->_api_encoding) {
                case 'utf8':
                    return $return;
                    break;
                case 'ucs4_string':
                    return $this->_ucs4_to_ucs4_string($this->_utf8_to_ucs4($return));
                    break;
                case 'ucs4_array':
                    return $this->_utf8_to_ucs4($return);
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported output format');
            }
        }
        // {{{ private
        /**
         * Opposite function to parse_url()
         *
         * Inspired by code from comments of php.net-documentation for parse_url()
         *
         * @param array $parts_arr parts (strings) as returned by parse_url()
         *
         * @return string
         * @access private
         */
        private function _unparse_url($parts_arr)
        {
            if (!empty($parts_arr['scheme'])) {
                $ret_url = $parts_arr['scheme'];
            }
            if (!empty($parts_arr['user'])) {
                $ret_url .= $parts_arr['user'];
                if (!empty($parts_arr['pass'])) {
                    $ret_url .= ':' . $parts_arr['pass'];
                }
                $ret_url .= '@';
            }
            $ret_url .= $parts_arr['host'];
            if (!empty($parts_arr['port'])) {
                $ret_url .= ':' . $parts_arr['port'];
            }
            $ret_url .= $parts_arr['path'];
            if (!empty($parts_arr['query'])) {
                $ret_url .= '?' . $parts_arr['query'];
            }
            if (!empty($parts_arr['fragment'])) {
                $ret_url .= '#' . $parts_arr['fragment'];
            }
            return $ret_url;
        }
        /**
         * The actual encoding algorithm.
         *
         * @param string $decoded Decoded string which should be encoded
         *
         * @return string         Encoded string
         * @throws Exception
         * @access private
         */
        private function _encode($decoded)
        {
            // We cannot encode a domain name containing the Punycode prefix
            $extract = self::_byteLength($this->_punycode_prefix);
            $check_pref = $this->_utf8_to_ucs4($this->_punycode_prefix);
            $check_deco = array_slice($decoded, 0, $extract);
            if ($check_pref == $check_deco) {
                throw new InvalidArgumentException('This is already a punycode string');
            }
            // We will not try to encode strings consisting of basic code points only
            $encodable = false;
            foreach ($decoded as $k => $v) {
                if ($v > 0x7a) {
                    $encodable = true;
                    break;
                }
            }
            if (!$encodable) {
                if ($this->_strict_mode) {
                    throw new InvalidArgumentException('The given string does not contain encodable chars');
                }
                return false;
            }
            // Do NAMEPREP
            $decoded = $this->_nameprep($decoded);
            $deco_len = count($decoded);
            // Empty array
            if (!$deco_len) {
                return false;
            }
            // How many chars have been consumed
            $codecount = 0;
            // Start with the prefix; copy it to output
            $encoded = $this->_punycode_prefix;
            $encoded = '';
            // Copy all basic code points to output
            for ($i = 0; $i < $deco_len; ++$i) {
                $test = $decoded[$i];
                // Will match [0-9a-zA-Z-]
                if (0x2f < $test && $test < 0x40 || 0x40 < $test && $test < 0x5b || 0x60 < $test && $test <= 0x7b || 0x2d == $test) {
                    $encoded .= chr($decoded[$i]);
                    $codecount++;
                }
            }
            // All codepoints were basic ones
            if ($codecount == $deco_len) {
                return $encoded;
            }
            // Start with the prefix; copy it to output
            $encoded = $this->_punycode_prefix . $encoded;
            // If we have basic code points in output, add an hyphen to the end
            if ($codecount) {
                $encoded .= '-';
            }
            // Now find and encode all non-basic code points
            $is_first = true;
            $cur_code = $this->_initial_n;
            $bias = $this->_initial_bias;
            $delta = 0;
            while ($codecount < $deco_len) {
                // Find the smallest code point >= the current code point and
                // remember the last ouccrence of it in the input
                for ($i = 0, $next_code = $this->_max_ucs; $i < $deco_len; $i++) {
                    if ($decoded[$i] >= $cur_code && $decoded[$i] <= $next_code) {
                        $next_code = $decoded[$i];
                    }
                }
                $delta += ($next_code - $cur_code) * ($codecount + 1);
                $cur_code = $next_code;
                // Scan input again and encode all characters whose code point is $cur_code
                for ($i = 0; $i < $deco_len; $i++) {
                    if ($decoded[$i] < $cur_code) {
                        $delta++;
                    } else {
                        if ($decoded[$i] == $cur_code) {
                            for ($q = $delta, $k = $this->_base; 1; $k += $this->_base) {
                                $t = $k <= $bias ? $this->_tmin : ($k >= $bias + $this->_tmax ? $this->_tmax : $k - $bias);
                                if ($q < $t) {
                                    break;
                                }
                                $encoded .= $this->_encodeDigit(ceil($t + ($q - $t) % ($this->_base - $t)));
                                $q = ($q - $t) / ($this->_base - $t);
                            }
                            $encoded .= $this->_encodeDigit($q);
                            $bias = $this->_adapt($delta, $codecount + 1, $is_first);
                            $codecount++;
                            $delta = 0;
                            $is_first = false;
                        }
                    }
                }
                $delta++;
                $cur_code++;
            }
            return $encoded;
        }
        /**
         * The actual decoding algorithm.
         *
         * @param string $encoded Encoded string which should be decoded
         *
         * @return string         Decoded string
         * @throws Exception
         * @access private
         */
        private function _decode($encoded)
        {
            // We do need to find the Punycode prefix
            if (!preg_match('!^' . preg_quote($this->_punycode_prefix, '!') . '!', $encoded)) {
                return false;
            }
            $encode_test = preg_replace('!^' . preg_quote($this->_punycode_prefix, '!') . '!', '', $encoded);
            // If nothing left after removing the prefix, it is hopeless
            if (!$encode_test) {
                return false;
            }
            // Find last occurence of the delimiter
            $delim_pos = strrpos($encoded, '-');
            if ($delim_pos > self::_byteLength($this->_punycode_prefix)) {
                for ($k = self::_byteLength($this->_punycode_prefix); $k < $delim_pos; ++$k) {
                    $decoded[] = ord($encoded[$k]);
                }
            } else {
                $decoded = array();
            }
            $deco_len = count($decoded);
            $enco_len = self::_byteLength($encoded);
            // Wandering through the strings; init
            $is_first = true;
            $bias = $this->_initial_bias;
            $idx = 0;
            $char = $this->_initial_n;
            for ($enco_idx = $delim_pos ? $delim_pos + 1 : 0; $enco_idx < $enco_len; ++$deco_len) {
                for ($old_idx = $idx, $w = 1, $k = $this->_base; 1; $k += $this->_base) {
                    $digit = $this->_decodeDigit($encoded[$enco_idx++]);
                    $idx += $digit * $w;
                    $t = $k <= $bias ? $this->_tmin : ($k >= $bias + $this->_tmax ? $this->_tmax : $k - $bias);
                    if ($digit < $t) {
                        break;
                    }
                    $w = (int) ($w * ($this->_base - $t));
                }
                $bias = $this->_adapt($idx - $old_idx, $deco_len + 1, $is_first);
                $is_first = false;
                $char += (int) ($idx / ($deco_len + 1));
                $idx %= $deco_len + 1;
                if ($deco_len > 0) {
                    // Make room for the decoded char
                    for ($i = $deco_len; $i > $idx; $i--) {
                        $decoded[$i] = $decoded[$i - 1];
                    }
                }
                $decoded[$idx++] = $char;
            }
            return $this->_ucs4_to_utf8($decoded);
        }
        /**
         * Adapt the bias according to the current code point and position.
         *
         * @param int     $delta    ...
         * @param int     $npoints  ...
         * @param boolean $is_first ...
         *
         * @return int
         * @access private
         */
        private function _adapt($delta, $npoints, $is_first)
        {
            $delta = (int) ($is_first ? $delta / $this->_damp : $delta / 2);
            $delta += (int) ($delta / $npoints);
            for ($k = 0; $delta > ($this->_base - $this->_tmin) * $this->_tmax / 2; $k += $this->_base) {
                $delta = (int) ($delta / ($this->_base - $this->_tmin));
            }
            return (int) ($k + ($this->_base - $this->_tmin + 1) * $delta / ($delta + $this->_skew));
        }
        /**
         * Encoding a certain digit.
         *
         * @param int $d One digit to encode
         *
         * @return char  Encoded digit
         * @access private
         */
        private function _encodeDigit($d)
        {
            return chr($d + 22 + 75 * ($d < 26));
        }
        /**
         * Decode a certain digit.
         *
         * @param char $cp One digit (character) to decode
         *
         * @return int     Decoded digit
         * @access private
         */
        private function _decodeDigit($cp)
        {
            $cp = ord($cp);
            return $cp - 48 < 10 ? $cp - 22 : ($cp - 65 < 26 ? $cp - 65 : ($cp - 97 < 26 ? $cp - 97 : $this->_base));
        }
        /**
         * Do Nameprep according to RFC3491 and RFC3454.
         *
         * @param array $input Unicode Characters
         *
         * @return string      Unicode Characters, Nameprep'd
         * @throws Net_IDNA2_Exception_Nameprep
         * @access private
         */
        private function _nameprep($input)
        {
            $output = array();
            // Walking through the input array, performing the required steps on each of
            // the input chars and putting the result into the output array
            // While mapping required chars we apply the cannonical ordering
            foreach ($input as $v) {
                // Map to nothing == skip that code point
                if (in_array($v, self::$_np_map_nothing)) {
                    continue;
                }
                // Try to find prohibited input
                if (in_array($v, self::$_np_prohibit) || in_array($v, self::$_general_prohibited)) {
                    throw new Net_IDNA2_Exception_Nameprep('Prohibited input U+' . sprintf('%08X', $v));
                }
                foreach (self::$_np_prohibit_ranges as $range) {
                    if ($range[0] <= $v && $v <= $range[1]) {
                        throw new Net_IDNA2_Exception_Nameprep('Prohibited input U+' . sprintf('%08X', $v));
                    }
                }
                // Hangul syllable decomposition
                if (0xac00 <= $v && $v <= 0xd7af) {
                    foreach ($this->_hangulDecompose($v) as $out) {
                        $output[] = $out;
                    }
                } else {
                    if ($this->_version == '2003' && isset(self::$_np_replacemaps[$v])) {
                        // There's a decomposition mapping for that code point
                        // Decompositions only in version 2003 (original) of IDNA
                        foreach ($this->_applyCannonicalOrdering(self::$_np_replacemaps[$v]) as $out) {
                            $output[] = $out;
                        }
                    } else {
                        $output[] = $v;
                    }
                }
            }
            // Combine code points
            $last_class = 0;
            $last_starter = 0;
            $out_len = count($output);
            for ($i = 0; $i < $out_len; ++$i) {
                $class = $this->_getCombiningClass($output[$i]);
                if ((!$last_class || $last_class != $class) && $class) {
                    // Try to match
                    $seq_len = $i - $last_starter;
                    $out = $this->_combine(array_slice($output, $last_starter, $seq_len));
                    // On match: Replace the last starter with the composed character and remove
                    // the now redundant non-starter(s)
                    if ($out) {
                        $output[$last_starter] = $out;
                        if (count($out) != $seq_len) {
                            for ($j = $i + 1; $j < $out_len; ++$j) {
                                $output[$j - 1] = $output[$j];
                            }
                            unset($output[$out_len]);
                        }
                        // Rewind the for loop by one, since there can be more possible compositions
                        $i--;
                        $out_len--;
                        $last_class = $i == $last_starter ? 0 : $this->_getCombiningClass($output[$i - 1]);
                        continue;
                    }
                }
                // The current class is 0
                if (!$class) {
                    $last_starter = $i;
                }
                $last_class = $class;
            }
            return $output;
        }
        /**
         * Decomposes a Hangul syllable
         * (see http://www.unicode.org/unicode/reports/tr15/#Hangul).
         *
         * @param integer $char 32bit UCS4 code point
         *
         * @return array        Either Hangul Syllable decomposed or original 32bit
         *                      value as one value array
         * @access private
         */
        private function _hangulDecompose($char)
        {
            $sindex = $char - $this->_sbase;
            if ($sindex < 0 || $sindex >= $this->_scount) {
                return array($char);
            }
            $result = array();
            $T = $this->_tbase + $sindex % $this->_tcount;
            $result[] = (int) ($this->_lbase + $sindex / $this->_ncount);
            $result[] = (int) ($this->_vbase + $sindex % $this->_ncount / $this->_tcount);
            if ($T != $this->_tbase) {
                $result[] = $T;
            }
            return $result;
        }
        /**
         * Ccomposes a Hangul syllable
         * (see http://www.unicode.org/unicode/reports/tr15/#Hangul).
         *
         * @param array $input Decomposed UCS4 sequence
         *
         * @return array       UCS4 sequence with syllables composed
         * @access private
         */
        private function _hangulCompose($input)
        {
            $inp_len = count($input);
            if (!$inp_len) {
                return array();
            }
            $result = array();
            $last = $input[0];
            $result[] = $last;
            // copy first char from input to output
            for ($i = 1; $i < $inp_len; ++$i) {
                $char = $input[$i];
                // Find out, wether two current characters from L and V
                $lindex = $last - $this->_lbase;
                if (0 <= $lindex && $lindex < $this->_lcount) {
                    $vindex = $char - $this->_vbase;
                    if (0 <= $vindex && $vindex < $this->_vcount) {
                        // create syllable of form LV
                        $last = $this->_sbase + ($lindex * $this->_vcount + $vindex) * $this->_tcount;
                        $out_off = count($result) - 1;
                        $result[$out_off] = $last;
                        // reset last
                        // discard char
                        continue;
                    }
                }
                // Find out, wether two current characters are LV and T
                $sindex = $last - $this->_sbase;
                if (0 <= $sindex && $sindex < $this->_scount && $sindex % $this->_tcount == 0) {
                    $tindex = $char - $this->_tbase;
                    if (0 <= $tindex && $tindex <= $this->_tcount) {
                        // create syllable of form LVT
                        $last += $tindex;
                        $out_off = count($result) - 1;
                        $result[$out_off] = $last;
                        // reset last
                        // discard char
                        continue;
                    }
                }
                // if neither case was true, just add the character
                $last = $char;
                $result[] = $char;
            }
            return $result;
        }
        /**
         * Returns the combining class of a certain wide char.
         *
         * @param integer $char Wide char to check (32bit integer)
         *
         * @return integer      Combining class if found, else 0
         * @access private
         */
        private function _getCombiningClass($char)
        {
            return isset(self::$_np_norm_combcls[$char]) ? self::$_np_norm_combcls[$char] : 0;
        }
        /**
         * Apllies the cannonical ordering of a decomposed UCS4 sequence.
         *
         * @param array $input Decomposed UCS4 sequence
         *
         * @return array       Ordered USC4 sequence
         * @access private
         */
        private function _applyCannonicalOrdering($input)
        {
            $swap = true;
            $size = count($input);
            while ($swap) {
                $swap = false;
                $last = $this->_getCombiningClass($input[0]);
                for ($i = 0; $i < $size - 1; ++$i) {
                    $next = $this->_getCombiningClass($input[$i + 1]);
                    if ($next != 0 && $last > $next) {
                        // Move item leftward until it fits
                        for ($j = $i + 1; $j > 0; --$j) {
                            if ($this->_getCombiningClass($input[$j - 1]) <= $next) {
                                break;
                            }
                            $t = $input[$j];
                            $input[$j] = $input[$j - 1];
                            $input[$j - 1] = $t;
                            $swap = 1;
                        }
                        // Reentering the loop looking at the old character again
                        $next = $last;
                    }
                    $last = $next;
                }
            }
            return $input;
        }
        /**
         * Do composition of a sequence of starter and non-starter.
         *
         * @param array $input UCS4 Decomposed sequence
         *
         * @return array       Ordered USC4 sequence
         * @access private
         */
        private function _combine($input)
        {
            $inp_len = count($input);
            // Is it a Hangul syllable?
            if (1 != $inp_len) {
                $hangul = $this->_hangulCompose($input);
                // This place is probably wrong
                if (count($hangul) != $inp_len) {
                    return $hangul;
                }
            }
            foreach (self::$_np_replacemaps as $np_src => $np_target) {
                if ($np_target[0] != $input[0]) {
                    continue;
                }
                if (count($np_target) != $inp_len) {
                    continue;
                }
                $hit = false;
                foreach ($input as $k2 => $v2) {
                    if ($v2 == $np_target[$k2]) {
                        $hit = true;
                    } else {
                        $hit = false;
                        break;
                    }
                }
                if ($hit) {
                    return $np_src;
                }
            }
            return false;
        }
        /**
         * This converts an UTF-8 encoded string to its UCS-4 (array) representation
         * By talking about UCS-4 we mean arrays of 32bit integers representing
         * each of the "chars". This is due to PHP not being able to handle strings with
         * bit depth different from 8. This applies to the reverse method _ucs4_to_utf8(), too.
         * The following UTF-8 encodings are supported:
         *
         * bytes bits  representation
         * 1        7  0xxxxxxx
         * 2       11  110xxxxx 10xxxxxx
         * 3       16  1110xxxx 10xxxxxx 10xxxxxx
         * 4       21  11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
         * 5       26  111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
         * 6       31  1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
         *
         * Each x represents a bit that can be used to store character data.
         *
         * @param string $input utf8-encoded string
         *
         * @return array        ucs4-encoded array
         * @throws Exception
         * @access private
         */
        private function _utf8_to_ucs4($input)
        {
            $output = array();
            $out_len = 0;
            $inp_len = self::_byteLength($input, '8bit');
            $mode = 'next';
            $test = 'none';
            for ($k = 0; $k < $inp_len; ++$k) {
                $v = ord($input[$k]);
                // Extract byte from input string
                if ($v < 128) {
                    // We found an ASCII char - put into stirng as is
                    $output[$out_len] = $v;
                    ++$out_len;
                    if ('add' == $mode) {
                        throw new UnexpectedValueException('Conversion from UTF-8 to UCS-4 failed: malformed input at byte ' . $k);
                    }
                    continue;
                }
                if ('next' == $mode) {
                    // Try to find the next start byte; determine the width of the Unicode char
                    $start_byte = $v;
                    $mode = 'add';
                    $test = 'range';
                    if ($v >> 5 == 6) {
                        // &110xxxxx 10xxxxx
                        $next_byte = 0;
                        // Tells, how many times subsequent bitmasks must rotate 6bits to the left
                        $v = $v - 192 << 6;
                    } elseif ($v >> 4 == 14) {
                        // &1110xxxx 10xxxxxx 10xxxxxx
                        $next_byte = 1;
                        $v = $v - 224 << 12;
                    } elseif ($v >> 3 == 30) {
                        // &11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
                        $next_byte = 2;
                        $v = $v - 240 << 18;
                    } elseif ($v >> 2 == 62) {
                        // &111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                        $next_byte = 3;
                        $v = $v - 248 << 24;
                    } elseif ($v >> 1 == 126) {
                        // &1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                        $next_byte = 4;
                        $v = $v - 252 << 30;
                    } else {
                        throw new UnexpectedValueException('This might be UTF-8, but I don\'t understand it at byte ' . $k);
                    }
                    if ('add' == $mode) {
                        $output[$out_len] = (int) $v;
                        ++$out_len;
                        continue;
                    }
                }
                if ('add' == $mode) {
                    if (!$this->_allow_overlong && $test == 'range') {
                        $test = 'none';
                        if ($v < 0xa0 && $start_byte == 0xe0 || $v < 0x90 && $start_byte == 0xf0 || $v > 0x8f && $start_byte == 0xf4) {
                            throw new OutOfRangeException('Bogus UTF-8 character detected (out of legal range) at byte ' . $k);
                        }
                    }
                    if ($v >> 6 == 2) {
                        // Bit mask must be 10xxxxxx
                        $v = $v - 128 << $next_byte * 6;
                        $output[$out_len - 1] += $v;
                        --$next_byte;
                    } else {
                        throw new UnexpectedValueException('Conversion from UTF-8 to UCS-4 failed: malformed input at byte ' . $k);
                    }
                    if ($next_byte < 0) {
                        $mode = 'next';
                    }
                }
            }
            // for
            return $output;
        }
        /**
         * Convert UCS-4 array into UTF-8 string
         *
         * @param array $input ucs4-encoded array
         *
         * @return string      utf8-encoded string
         * @throws Exception
         * @access private
         */
        private function _ucs4_to_utf8($input)
        {
            $output = '';
            foreach ($input as $v) {
                // $v = ord($v);
                if ($v < 128) {
                    // 7bit are transferred literally
                    $output .= chr($v);
                } else {
                    if ($v < 1 << 11) {
                        // 2 bytes
                        $output .= chr(192 + ($v >> 6)) . chr(128 + ($v & 63));
                    } else {
                        if ($v < 1 << 16) {
                            // 3 bytes
                            $output .= chr(224 + ($v >> 12)) . chr(128 + ($v >> 6 & 63)) . chr(128 + ($v & 63));
                        } else {
                            if ($v < 1 << 21) {
                                // 4 bytes
                                $output .= chr(240 + ($v >> 18)) . chr(128 + ($v >> 12 & 63)) . chr(128 + ($v >> 6 & 63)) . chr(128 + ($v & 63));
                            } else {
                                if ($v < 1 << 26) {
                                    // 5 bytes
                                    $output .= chr(248 + ($v >> 24)) . chr(128 + ($v >> 18 & 63)) . chr(128 + ($v >> 12 & 63)) . chr(128 + ($v >> 6 & 63)) . chr(128 + ($v & 63));
                                } else {
                                    if ($v < 1 << 31) {
                                        // 6 bytes
                                        $output .= chr(252 + ($v >> 30)) . chr(128 + ($v >> 24 & 63)) . chr(128 + ($v >> 18 & 63)) . chr(128 + ($v >> 12 & 63)) . chr(128 + ($v >> 6 & 63)) . chr(128 + ($v & 63));
                                    } else {
                                        throw new UnexpectedValueException('Conversion from UCS-4 to UTF-8 failed: malformed input');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $output;
        }
        /**
         * Convert UCS-4 array into UCS-4 string
         *
         * @param array $input ucs4-encoded array
         *
         * @return string      ucs4-encoded string
         * @throws Exception
         * @access private
         */
        private function _ucs4_to_ucs4_string($input)
        {
            $output = '';
            // Take array values and split output to 4 bytes per value
            // The bit mask is 255, which reads &11111111
            foreach ($input as $v) {
                $output .= ($v & 255 << 24 >> 24) . ($v & 255 << 16 >> 16) . ($v & 255 << 8 >> 8) . ($v & 255);
            }
            return $output;
        }
        /**
         * Convert UCS-4 string into UCS-4 array
         *
         * @param string $input ucs4-encoded string
         *
         * @return array        ucs4-encoded array
         * @throws InvalidArgumentException
         * @access private
         */
        private function _ucs4_string_to_ucs4($input)
        {
            $output = array();
            $inp_len = self::_byteLength($input);
            // Input length must be dividable by 4
            if ($inp_len % 4) {
                throw new InvalidArgumentException('Input UCS4 string is broken');
            }
            // Empty input - return empty output
            if (!$inp_len) {
                return $output;
            }
            for ($i = 0, $out_len = -1; $i < $inp_len; ++$i) {
                // Increment output position every 4 input bytes
                if (!$i % 4) {
                    $out_len++;
                    $output[$out_len] = 0;
                }
                $output[$out_len] += ord($input[$i]) << 8 * (3 - $i % 4);
            }
            return $output;
        }
        /**
         * Echo hex representation of UCS4 sequence.
         *
         * @param array   $input       UCS4 sequence
         * @param boolean $include_bit Include bitmask in output
         *
         * @return void
         * @static
         * @access private
         */
        private static function _showHex($input, $include_bit = false)
        {
            foreach ($input as $k => $v) {
                echo '[', $k, '] => ', sprintf('%X', $v);
                if ($include_bit) {
                    echo ' (', Net_IDNA2::_showBitmask($v), ')';
                }
                echo "\n";
            }
        }
        /**
         * Gives you a bit representation of given Byte (8 bits), Word (16 bits) or DWord (32 bits)
         * Output width is automagically determined
         *
         * @param int $octet ...
         *
         * @return string    Bitmask-representation
         * @static
         * @access private
         */
        private static function _showBitmask($octet)
        {
            if ($octet >= 1 << 16) {
                $w = 31;
            } else {
                if ($octet >= 1 << 8) {
                    $w = 15;
                } else {
                    $w = 7;
                }
            }
            $return = '';
            for ($i = $w; $i > -1; $i--) {
                $return .= $octet & 1 << $i ? '1' : '0';
            }
            return $return;
        }
        /**
         * Gets the length of a string in bytes even if mbstring function
         * overloading is turned on
         *
         * @param string $string the string for which to get the length.
         *
         * @return integer the length of the string in bytes.
         *
         * @see Net_IDNA2::$_mb_string_overload
         */
        private static function _byteLength($string)
        {
            if (self::$_mb_string_overload) {
                return mb_strlen($string, '8bit');
            }
            return strlen((string) $string);
        }
        // }}}}
        // {{{ factory
        /**
         * Attempts to return a concrete IDNA instance for either php4 or php5.
         *
         * @param array $params Set of paramaters
         *
         * @return Net_IDNA2
         * @access public
         */
        function getInstance($params = array())
        {
            return new Net_IDNA2($params);
        }
        // }}}
        // {{{ singleton
        /**
         * Attempts to return a concrete IDNA instance for either php4 or php5,
         * only creating a new instance if no IDNA instance with the same
         * parameters currently exists.
         *
         * @param array $params Set of paramaters
         *
         * @return object Net_IDNA2
         * @access public
         */
        function singleton($params = array())
        {
            static $instances;
            if (!isset($instances)) {
                $instances = array();
            }
            $signature = safe_serialize($params);
            if (!isset($instances[$signature])) {
                $instances[$signature] = Net_IDNA2::getInstance($params);
            }
            return $instances[$signature];
        }
    }
}

?>