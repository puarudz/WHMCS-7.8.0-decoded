<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Update;

class UpdateNotification
{
    protected $style = "";
    protected $icon = "";
    protected $title = "";
    protected $body = "";
    protected $acceptanceMessage = "";
    protected $overrides = array();
    protected $id = "";
    const STYLE_SUCCESS = "success";
    const STYLE_WARNING = "warning";
    const STYLE_DANGER = "danger";
    const STYLE_INFO = "info";
    const STYLE_GREY = "grey";
    const STYLES = NULL;
    const TITLE_HIDDEN = "::hidden::";
    const MAX_TITLE_LENGTH = 90;
    public function __construct($title = "", $body = "", $style = self::STYLE_WARNING, $icon = "fa-asterisk", $requiresAcceptance = "", $overrides = array(), $id = "")
    {
        $this->setTitle($title);
        $this->setBody($body);
        if (!empty($id)) {
            $this->setUniqueId($id);
        } else {
            $this->updateUniqueId();
        }
        $this->setStyle($style);
        $this->setIcon($icon);
        $this->setAcceptanceMessage($requiresAcceptance);
        return $this;
    }
    protected function updateUniqueId()
    {
        $this->id = substr(sha1($this->title . "" . $this->body), 0, 8);
    }
    public static function loadFromJson($json)
    {
        $notification = new self();
        $fromJson = json_decode($json, true);
        $notification->setTitle($fromJson["title"]);
        $notification->setBody($fromJson["body"]);
        $notification->setUniqueId($fromJson["id"]);
        $notification->setStyle($fromJson["style"]);
        $notification->setIcon($fromJson["icon"]);
        $notification->setAcceptanceMessage($fromJson["requireAcceptance"]);
        $notification->setOverrides($fromJson["overrides"]);
        return $notification;
    }
    public function toArray()
    {
        $output = array();
        $output["id"] = $this->getUniqueId();
        $output["title"] = $this->getTitle();
        $output["body"] = $this->getBody();
        $output["style"] = $this->getStyle();
        $output["icon"] = $this->getIcon();
        $output["requireAcceptance"] = $this->getAcceptanceMessage();
        $output["overrides"] = $this->getOverrides();
        return $output;
    }
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
    public function setTitle($title)
    {
        if (!$this->validateTitle($title)) {
            throw new \WHMCS\Exception("Title must be less then " . self::MAX_TITLE_LENGTH . " characters.");
        }
        $this->title = $title;
        $this->updateUniqueId();
        return $this;
    }
    public function validateTitle($title)
    {
        if (is_string($title)) {
            return strlen($title) <= self::MAX_TITLE_LENGTH;
        }
        return false;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setBody($body)
    {
        if (!$this->validateBody($body)) {
            throw new \WHMCS\Exception("Body was not valid markdown.");
        }
        $this->body = $body;
        $this->updateUniqueId();
        return $this;
    }
    public function validateBody($body)
    {
        return true;
    }
    public function getBody()
    {
        return $this->body;
    }
    public function setStyle($style)
    {
        if (!$this->validateStyle($style)) {
            throw new \WHMCS\Exception("Style " . $style . " not a known style");
        }
        $this->style = $style;
        return $this;
    }
    public function validateStyle($style)
    {
        return in_array($style, self::STYLES);
    }
    public function getStyle()
    {
        return $this->style;
    }
    public function setIcon($icon)
    {
        if (!$this->validateIcon($icon)) {
            throw new \WHMCS\Exception("Icon " . $icon . " does not contain a fa- prefix.");
        }
        $this->icon = $icon;
        return $this;
    }
    public function validateIcon($icon)
    {
        return (bool) preg_match("/^fa\\-[a-z\\d\\- ]+\$/i", $icon);
    }
    public function getIcon()
    {
        return $this->icon;
    }
    public function setAcceptanceMessage($acceptance)
    {
        if (!$this->validateAcceptanceMessage($acceptance)) {
            throw new \WHMCS\Exception("Acceptance message does not meet requirements.");
        }
        $this->acceptanceMessage = $acceptance;
        return $this;
    }
    public function validateAcceptanceMessage($acceptance)
    {
        return true;
    }
    public function getAcceptanceMessage()
    {
        return $this->acceptanceMessage;
    }
    public function requiresAcceptance()
    {
        return !empty($this->acceptanceMessage);
    }
    public function setUniqueId($id)
    {
        if (!$this->validateUniqueId($id)) {
            throw new \WHMCS\Exception("Id must be a string");
        }
        $this->id = $id;
        return $this;
    }
    public function getUniqueId()
    {
        return $this->id;
    }
    public function validateUniqueId($id)
    {
        return is_string($id);
    }
    public function getSafeBody()
    {
        $markup = new \WHMCS\View\Markup\Markup();
        return $markup->transform($this->body, "markdown");
    }
    public function getSafeTitle()
    {
        $markup = new \WHMCS\View\Markup\Markup();
        return $markup->transform($this->title, "plain");
    }
    public function setOverrides($overrides)
    {
        if (!$this->validateOverrides($overrides)) {
            throw new \WHMCS\Exception("Overrides is not a single deep array of IDs");
        }
        $this->overrides = $overrides;
        return $this;
    }
    public function getOverrides()
    {
        return $this->overrides;
    }
    public function validateOverrides($overrides)
    {
        return is_array($overrides);
    }
}

?>