<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification;

class FeatureHighlight
{
    protected $title = NULL;
    protected $subtitle = NULL;
    protected $headlineImage = NULL;
    protected $iconImage = NULL;
    protected $description = NULL;
    protected $btn1Link = NULL;
    protected $btn1Label = NULL;
    protected $btn2Link = NULL;
    protected $btn2Label = NULL;
    protected $assetHelper = NULL;
    public function __construct($title = NULL, $subtitle = NULL, $headlineImage = NULL, $iconImage = NULL, $description = NULL, $btn1Link = NULL, $btn1Label = NULL, $btn2Link = NULL, $btn2Label = NULL)
    {
        if (empty($title)) {
            throw new \WHMCS\Exception("FeatureHighlight Entities are required to have a title.");
        }
        if (empty($subtitle)) {
            throw new \WHMCS\Exception("FeatureHighlight Entities are required to have a subtitle.");
        }
        if (empty($iconImage)) {
            throw new \WHMCS\Exception("FeatureHighlight Entities are required to have an icon image.");
        }
        if (empty($description)) {
            throw new \WHMCS\Exception("FeatureHighlight Entities are required to have a description.");
        }
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->headlineImage = $headlineImage;
        $this->iconImage = $iconImage;
        $this->description = $description;
        if (!is_null($btn1Link)) {
            $this->btn1Link = $btn1Link;
            $this->btn1Label = $btn1Label;
        }
        if (!is_null($btn2Link)) {
            $this->btn2Link = $btn2Link;
            $this->btn2Label = $btn2Label;
        }
        $this->assetHelper = \DI::make("asset");
        return $this;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getSubtitle()
    {
        return $this->subtitle;
    }
    public function getImage($imageName = NULL)
    {
        if (substr($imageName, 0, 4) == "http") {
            return $imageName;
        }
        return "images/whatsnew/" . $imageName;
    }
    public function getIcon()
    {
        return $this->getImage($this->iconImage);
    }
    public function getHeadlineImage()
    {
        return $this->getImage($this->headlineImage);
    }
    public function hasHeadlineImage()
    {
        return !is_null($this->headlineImage);
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function hasBtn1Link()
    {
        return !is_null($this->btn1Link);
    }
    public function getBtn1Link()
    {
        if (!$this->btn1Link) {
            return null;
        }
        return $this->btn1Link;
    }
    public function getBtn1Label()
    {
        return $this->btn1Label;
    }
    public function hasBtn2Link()
    {
        return !is_null($this->btn2Link);
    }
    public function getBtn2Link()
    {
        if (!$this->btn2Link) {
            return null;
        }
        return $this->btn2Link;
    }
    public function getBtn2Label()
    {
        return $this->btn2Label;
    }
}

?>