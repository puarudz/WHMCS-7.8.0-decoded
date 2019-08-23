<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification;

trait DescriptionTrait
{
    protected $displayName = "";
    protected $logoFileName = "";
    public function isActive()
    {
        $provider = \WHMCS\Notification\Provider::where("name", "=", $this->getName())->first();
        if (!$provider) {
            return false;
        }
        return (bool) $provider->active;
    }
    public function getName()
    {
        return basename(str_replace("\\", "/", get_class($this)));
    }
    public function getDisplayName()
    {
        return $this->displayName;
    }
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }
    public function getLogoFileName()
    {
        return $this->logoFileName;
    }
    public function setLogoFileName($logoFileName)
    {
        $this->logoFileName = $logoFileName;
        return $this;
    }
    public function getLogoPath()
    {
        return "/modules/notifications/" . $this->getName() . "/" . $this->logoFileName;
    }
}

?>