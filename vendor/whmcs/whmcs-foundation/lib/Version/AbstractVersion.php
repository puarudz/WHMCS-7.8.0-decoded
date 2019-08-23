<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Version;

abstract class AbstractVersion
{
    protected $major = "";
    protected $minor = "";
    protected $patch = "";
    protected $preReleaseIdentifier = "";
    protected $preReleaseRevision = "";
    protected $buildTag = "";
    protected $casualNames = array("release" => "", "rc" => "RC", "beta" => "Beta", "alpha" => "Alpha");
    protected $version = "0.0.0";
    protected $data = array();
    const DEFAULT_PRERELEASE_IDENTIFIER = "release";
    const DEFAULT_PRERELEASE_REVISION = "1";
    public function __construct($version)
    {
        $this->setVersion($version);
    }
    public abstract function isValid($version);
    public abstract function parse($version);
    public function getVersion()
    {
        return $this->version;
    }
    public function setVersion($version)
    {
        if (!$this->isValid($version)) {
            throw new \WHMCS\Exception\Version\BadVersionNumber(sprintf("'%s' is not a valid version number.", $version));
        }
        $this->version = $version;
        $this->parse($version);
        return $this;
    }
    public function getCanonical()
    {
        $version = sprintf("%d.%d.%d", $this->getMajor(), $this->getMinor(), $this->getPatch());
        return $version;
    }
    public function getCasual()
    {
        $version = sprintf("%d.%d.%d", $this->getMajor(), $this->getMinor(), $this->getPatch());
        $label = $this->getPreReleaseIdentifier();
        if (!empty($this->casualNames[$label])) {
            $version .= " " . $this->casualNames[$label];
            if (0 < $this->getPreReleaseRevision()) {
                $version .= $this->getPreReleaseRevision();
            }
        }
        return $version;
    }
    public function getMajor()
    {
        return $this->major;
    }
    public function getMinor()
    {
        return $this->minor;
    }
    public function getPatch()
    {
        return $this->patch;
    }
    public function getPreReleaseIdentifier()
    {
        return $this->preReleaseIdentifier;
    }
    public function getPreReleaseRevision()
    {
        return $this->preReleaseRevision;
    }
    public function getBuildTag()
    {
        return $this->buildTag;
    }
    public function setMajor($data)
    {
        $this->major = $data;
        return $this;
    }
    public function setMinor($data)
    {
        $this->minor = $data;
        return $this;
    }
    public function setPatch($data)
    {
        $this->patch = $data;
        return $this;
    }
    public function setPreReleaseIdentifier($data)
    {
        $this->preReleaseIdentifier = $data;
        return $this;
    }
    public function setPreReleaseRevision($data)
    {
        $this->preReleaseRevision = $data;
        return $this;
    }
    public function setBuildTag($data)
    {
        $this->buildTag = $data;
        return $this;
    }
}

?>