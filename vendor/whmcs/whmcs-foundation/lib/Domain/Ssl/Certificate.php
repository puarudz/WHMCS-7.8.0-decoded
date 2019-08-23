<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain\Ssl;

class Certificate
{
    protected $certData = NULL;
    public function __construct($certData = NULL)
    {
        $this->certData = $this->normaliseCertificateData($certData);
    }
    protected function normaliseCertificateData($data)
    {
        $dataToReturn = array();
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            $dataToReturn[$key] = $value;
        }
        return $dataToReturn;
    }
    public function getSubjectCommonName()
    {
        $data = $this->parseIssuerString($this->certData["subject"]);
        return $data["CN"];
    }
    public function getSubjectOrg()
    {
        $data = $this->parseIssuerString($this->certData["subject"]);
        return $data["O"] ?: "";
    }
    public function getIssuerName()
    {
        $cPanelIssuerName = "cPanel, Inc. Certification Authority";
        $data = $this->parseIssuerString($this->certData["issuer"]);
        if (strstr($this->certData["issuer"], $cPanelIssuerName)) {
            $data["CN"] = $cPanelIssuerName;
        }
        return $data["CN"];
    }
    public function getIssuerOrg()
    {
        $cPanelOrgName = "cPanel, Inc.";
        $data = $this->parseIssuerString($this->certData["issuer"]);
        if (strstr($this->certData["issuer"], $cPanelOrgName)) {
            $data["O"] = $cPanelOrgName;
        }
        return $data["O"] ?: "";
    }
    public function getStartDate()
    {
        return $this->parseDate($this->certData["start date"]);
    }
    public function getExpiryDate()
    {
        return $this->parseDate($this->certData["expire date"]);
    }
    protected function parseIssuerString($string)
    {
        $data = array();
        $parts = explode(",", $string);
        foreach ($parts as $line) {
            $line = explode("=", $line, 2);
            $data[trim($line[0])] = trim($line[1]);
        }
        return $data;
    }
    protected function parseDate($date)
    {
        return \WHMCS\Carbon::parse($date);
    }
}

?>