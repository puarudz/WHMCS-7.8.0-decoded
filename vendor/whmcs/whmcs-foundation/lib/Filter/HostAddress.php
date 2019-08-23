<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Filter;

class HostAddress
{
    private $ipAddress = "";
    private $hostname = "";
    private $isIpv6 = false;
    private $port = "";
    public function __construct($hostname, $ipAddress = "", $port = "")
    {
        if (!$hostname && !$ipAddress) {
            throw new \WHMCS\Exception\Validation\InvalidHostAddress();
        }
        if ($hostname) {
            $this->setHostname($hostname);
        }
        if ($ipAddress) {
            $this->setIpAddress($ipAddress);
        }
        if ($port && !$this->port) {
            $this->setPort($port);
        }
    }
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
    public function getIpAddressForUrl()
    {
        $ipAddress = $this->getIpAddress();
        if ($ipAddress && $this->isIpv6) {
            $ipAddress = "[" . $ipAddress . "]";
        }
        return $ipAddress;
    }
    public function getHostname()
    {
        return $this->hostname ?: $this->getIpAddress();
    }
    public function getHostnameForUrl()
    {
        return $this->hostname ?: $this->getIpAddressForUrl();
    }
    public function getPort()
    {
        return $this->port;
    }
    private function sanitizePort($port)
    {
        if (is_string($port)) {
            $port = trim($port);
        }
        if (!ctype_digit($port)) {
            return false;
        }
        $port = (int) $port;
        if ($port < 0 || 65535 < $port) {
            return false;
        }
        return $port;
    }
    private function setPort($port)
    {
        $port = $this->sanitizePort($port);
        if ($port === false) {
            throw new \WHMCS\Exception\Validation\InvalidPort();
        }
        $this->port = $port;
    }
    private function setIpAddress($ipAddress)
    {
        $addressPort = null;
        $ipAddress = trim($ipAddress);
        if ($ipAddress === "") {
            return null;
        }
        $isIpv6 = false;
        if (strpos($ipAddress, "[") !== false) {
            if (preg_match("/^\\[([a-f\\d:]+\\])([:\\d]+)?\$/i", $ipAddress, $matches)) {
                $isIpv6 = true;
                $ipAddress = $matches[1];
                $addressPort = isset($matches[2]) ? $matches[2] : "";
                $addressPort = trim($addressPort, ":");
                if ($addressPort !== "") {
                    $addressPort = $this->sanitizePort($addressPort);
                    if ($addressPort === false) {
                        throw new \WHMCS\Exception\Validation\InvalidIpAddress();
                    }
                } else {
                    $addressPort = null;
                }
            }
        } else {
            if (preg_match("/^[a-f\\d:]+\$/i", $ipAddress)) {
                $isIpv6 = true;
            }
        }
        if ($isIpv6) {
            $ipAddress = trim($ipAddress, "[]");
        } else {
            $ipAddressParts = explode(":", $ipAddress, 2);
            if (count($ipAddressParts) === 2) {
                list($ipAddress, $addressPort) = $ipAddressParts;
                if ($addressPort === "") {
                    $addressPort = null;
                } else {
                    if (!ctype_digit($addressPort)) {
                        throw new \WHMCS\Exception\Validation\InvalidIpAddress();
                    }
                }
            }
        }
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new \WHMCS\Exception\Validation\InvalidIpAddress();
        }
        $this->ipAddress = $ipAddress;
        $this->isIpv6 = $isIpv6;
        if (!is_null($addressPort)) {
            $this->port = $addressPort;
        }
        return $this;
    }
    private function setHostname($hostname)
    {
        $hostnamePort = null;
        $hostname = trim($hostname);
        if ($hostname === "") {
            return null;
        }
        $hostParts = explode(":", $hostname, 2);
        if (count($hostParts) === 2) {
            list($hostname, $hostnamePort) = $hostParts;
            if ($hostnamePort !== "") {
                $hostnamePort = $this->sanitizePort($hostnamePort);
                if ($hostnamePort === false) {
                    throw new \WHMCS\Exception\Validation\InvalidHostname();
                }
            } else {
                $hostnamePort = null;
            }
        }
        if (defined("FILTER_VALIDATE_DOMAIN") && defined("FILTER_FLAG_HOSTNAME")) {
            if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                throw new \WHMCS\Exception\Validation\InvalidHostname();
            }
        } else {
            if (!preg_match("/^[a-z0-9][a-z0-9\\-\\.]*\\.[a-z0-9]+\$/i", $hostname)) {
                logActivity("Invalid hostname detected on PHP " . PHP_VERSION . ": " . $hostname);
                throw new \WHMCS\Exception\Validation\InvalidHostname();
            }
        }
        $this->hostname = $hostname;
        if (!is_null($hostnamePort)) {
            $this->port = $hostnamePort;
        }
        return $this;
    }
}

?>