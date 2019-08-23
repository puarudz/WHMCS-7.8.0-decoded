<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\System\PhpCompat\View\AccordionByCompat;

class AccordionGroup
{
    protected $id = "";
    protected $subId = "";
    protected $description = "";
    protected $title = "";
    protected $data = array();
    protected $titleCssClass = "";
    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function getSubId()
    {
        return $this->subId;
    }
    public function setSubId($subId)
    {
        $this->subId = $subId;
        return $this;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function getData()
    {
        return $this->data;
    }
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    public function getCollapseId()
    {
        return "collapse" . $this->id . $this->subId;
    }
    public function getHeadingId()
    {
        return "heading" . $this->id . $this->subId;
    }
    public function getTableId()
    {
        return "tblcompat" . $this->id . $this->subId;
    }
    public function getTitleIconClass()
    {
        $count = count($this->getData());
        if ($this->getId() === "Compat" || $count < 1) {
            $iconClass = "fa-check";
        } else {
            $iconClass = "fa-exclamation-triangle";
        }
        return $iconClass;
    }
    public function getTitleBadgeCount()
    {
        return count($this->getData());
    }
    public function getTitleCssClass()
    {
        if (!$this->getData()) {
            return "success";
        }
        return $this->titleCssClass;
    }
    public function setTitleCssClass($titleCssClass)
    {
        $this->titleCssClass = $titleCssClass;
        return $this;
    }
}

?>