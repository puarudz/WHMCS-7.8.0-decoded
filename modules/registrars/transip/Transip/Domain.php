<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_Domain
{
    public $name = "";
    public $nameservers = array();
    public $contacts = array();
    public $dnsEntries = array();
    public $branding = NULL;
    public $authCode = "";
    public $isLocked = false;
    public $registrationDate = "";
    public $renewalDate = "";
    public function __construct($name, $nameservers = array(), $contacts = array(), $dnsEntries = array(), $branding = NULL)
    {
        $this->name = $name;
        $this->nameservers = $nameservers;
        $this->contacts = $contacts;
        $this->dnsEntries = $dnsEntries;
        $this->branding = $branding;
    }
}

?>