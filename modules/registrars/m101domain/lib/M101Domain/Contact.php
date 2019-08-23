<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace M101Domain;

class Contact
{
    public $handle = NULL;
    public $first_name = NULL;
    public $last_name = NULL;
    public $company = NULL;
    public $address1 = NULL;
    public $address2 = NULL;
    public $address3 = NULL;
    public $city = NULL;
    public $state = NULL;
    public $postal = NULL;
    public $country = NULL;
    public $email = NULL;
    public $phone = NULL;
    public $phone_ext = NULL;
    public $fax = NULL;
    public $fax_ext = NULL;
    public static $labels = array("first_name" => "First Name", "last_name" => "Last Name", "company" => "Company", "address1" => "Address 1", "address2" => "Address 2", "address3" => "Address 3", "city" => "City", "state" => "State or Province", "postal" => "Postal Code", "country" => "Country Code", "email" => "Email Address", "phone" => "Phone", "phone_ext" => "Phone Ext", "fax" => "Fax", "fax_ext" => "Fax Extension");
    private static $phone_codes = array("AF" => "93", "AL" => "355", "DZ" => "213", "AS" => "1", "AD" => "376", "AO" => "244", "AI" => "1", "AG" => "1", "AR" => "54", "AM" => "374", "AW" => "297", "AU" => "61", "AT" => "43", "AZ" => "994", "BS" => "1", "BH" => "973", "BD" => "880", "BB" => "1", "BY" => "375", "BE" => "32", "BZ" => "501", "BJ" => "229", "BM" => "1", "BT" => "975", "BO" => "591", "CW" => "599", "BA" => "387", "BW" => "267", "BR" => "55", "VG" => "1", "DB" => "673", "BG" => "359", "BF" => "226", "BI" => "257", "KH" => "855", "CM" => "237", "CA" => "1", "CV" => "238", "KY" => "1", "CF" => "236", "TD" => "235", "CL" => "56", "CN" => "86", "CO" => "57", "KM" => "269", "CG" => "242", "CK" => "682", "CR" => "506", "CI" => "225", "HR" => "385", "CU" => "53", "CY" => "357", "CZ" => "420", "KP" => "850", "CD" => "243", "DK" => "45", "IO" => "246", "DJ" => "253", "DM" => "1", "DO" => "1", "EC" => "593", "EG" => "20", "SV" => "503", "GQ" => "240", "ER" => "291", "EE" => "372", "ET" => "251", "FK" => "500", "FO" => "298", "FJ" => "679", "FI" => "358", "FR" => "33", "RE" => "262", "GF" => "594", "PF" => "689", "GA" => "241", "GM" => "220", "GE" => "995", "DE" => "49", "GH" => "233", "GI" => "350", "GR" => "30", "GL" => "299", "GD" => "1", "GP" => "590", "GU" => "1", "GT" => "502", "GN" => "224", "GW" => "245", "GY" => "592", "HT" => "509", "HN" => "504", "HK" => "852", "HU" => "36", "IS" => "354", "IN" => "91", "ID" => "62", "IR" => "98", "IQ" => "964", "IE" => "353", "IL" => "972", "IT" => "39", "JM" => "1", "JP" => "81", "JO" => "962", "KZ" => "7", "KE" => "254", "KI" => "686", "KR" => "82", "KW" => "965", "KG" => "996", "LA" => "856", "LV" => "371", "LB" => "961", "LS" => "266", "LR" => "231", "LY" => "218", "LI" => "423", "LT" => "370", "LU" => "352", "MO" => "853", "MG" => "261", "MW" => "265", "MY" => "60", "MV" => "960", "ML" => "223", "MT" => "356", "MH" => "692", "MQ" => "596", "MR" => "222", "MU" => "230", "MX" => "52", "FM" => "691", "MD" => "373", "MC" => "377", "MN" => "976", "ME" => "382", "MS" => "1", "MA" => "212", "MZ" => "258", "MM" => "95", "NA" => "264", "NR" => "674", "NP" => "977", "NL" => "31", "NC" => "687", "NZ" => "64", "NI" => "505", "NE" => "227", "NG" => "234", "NU" => "683", "NF" => "672", "MP" => "1", "NO" => "47", "OM" => "968", "PK" => "92", "PW" => "680", "PA" => "507", "PG" => "675", "PY" => "595", "PE" => "51", "PH" => "63", "PL" => "48", "PT" => "351", "PR" => "1", "QA" => "974", "RO" => "40", "RU" => "7", "RW" => "250", "SH" => "290", "KN" => "1", "LC" => "1", "PM" => "508", "VC" => "1", "WS" => "685", "SM" => "378", "ST" => "239", "SA" => "966", "SN" => "221", "RS" => "381", "SC" => "248", "SL" => "232", "SG" => "65", "SX" => "1", "SK" => "421", "SI" => "386", "SB" => "677", "SO" => "252", "ZA" => "27", "SS" => "211", "ES" => "34", "LK" => "94", "SD" => "249", "SR" => "597", "SZ" => "268", "SE" => "46", "CH" => "41", "SY" => "963", "TW" => "886", "TJ" => "992", "TZ" => "255", "TH" => "66", "MK" => "389", "TP" => "670", "TG" => "228", "TK" => "690", "TO" => "676", "TT" => "1", "TN" => "216", "TR" => "90", "TM" => "993", "TC" => "1", "TV" => "688", "UG" => "256", "UA" => "380", "AE" => "971", "UK" => "44", "US" => "1", "VI" => "1", "UY" => "598", "UZ" => "998", "VU" => "678", "VE" => "58", "VN" => "84", "WF" => "681", "YE" => "967", "ZM" => "260", "ZW" => "263");
    public function normalize()
    {
        $this->country = strtoupper($this->country);
        $this->phone = $this->fixPhone($this->phone);
        $this->fax = $this->fixPhone($this->fax);
        $required = array("email" => "Email Address", "first_name" => "First Name", "last_name" => "Last Name", "address1" => "Address", "city" => "City", "country" => "Country", "phone" => "Phone");
        $errors = array();
        foreach ($required as $vn => $title) {
            if (empty($this->{$vn})) {
                $errors[] = (string) $title . " can not be empty";
            }
        }
        if (0 < count($errors)) {
            throw new Exception\Error("Failed to Create Contact: " . join($errors, ", "));
        }
    }
    private function fixPhone($phone)
    {
        if (empty($phone)) {
            return $phone;
        }
        $phone = preg_replace("/[^0-9\\.+]/", "", $phone);
        if (!preg_match("/^\\+\\d{1,3}\\.\\d+\$/", $phone)) {
            $phone = preg_replace("/[^0-9]/", "", $phone);
            $pfx = self::$phone_codes[$this->country];
            if ($pfx && strpos($phone, $pfx) === 0) {
                $phone = preg_replace("/^" . preg_quote($pfx) . "/", "", $phone);
                $phone = "+" . $pfx . "." . $phone;
            } else {
                if ($pfx) {
                    $phone = "+" . $pfx . "." . $phone;
                } else {
                    $phone = "+1." . $phone;
                }
            }
        }
        return $phone;
    }
}

?>