<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron;

class Decorator
{
    protected $item = NULL;
    public function __construct(DecoratorItemInterface $item)
    {
        $this->item = $item;
    }
    public function render($data, $isDisabled)
    {
        if ($this->item->isBooleanStatusItem()) {
            return $this->renderBooleanItem($data, $isDisabled);
        }
        return $this->renderStatisticalItem($data, $isDisabled);
    }
    public function renderStatisticalItem($data, $isDisabled)
    {
        if (is_array($this->item->getSuccessCountIdentifier())) {
            $primarySuccessCount = 0;
            foreach ($this->item->getSuccessCountIdentifier() as $identifier) {
                $primarySuccessCount += (int) $data[$identifier];
            }
        } else {
            $primarySuccessCount = (int) $data[$this->item->getSuccessCountIdentifier()];
        }
        $successKeyword = $this->item->getSuccessKeyword();
        if ($this->item->getFailureCountIdentifier()) {
            $failedCountIdentifier = (int) $data[$this->item->getFailureCountIdentifier()];
            $failedUrl = $this->item->getFailureUrl();
            $failedLink = "<a href=\"" . $failedUrl . "\" class=\"failed\">" . $failedCountIdentifier . " " . $this->item->getFailureKeyword() . "</a>";
        } else {
            $failedLink = "";
        }
        $disabled = "";
        if ($isDisabled && $primarySuccessCount == 0) {
            $primarySuccessCount = "-";
            $successKeyword = "";
            $failedLink = "";
            $disabled = "<small>Disabled</small>";
        }
        return "<div class=\"widget\">\n            <div class=\"info-container\">\n                <div class=\"pull-right\"><i class=\"" . $this->item->getIcon() . " fa-2x\"></i></div>\n                <p class=\"intro\">" . $this->item->getName() . "</p>\n                <h3 class=\"title\"><span class=\"figure\">" . $primarySuccessCount . "</span><span class=\"note\">" . $successKeyword . "</span></h3>\n                " . $failedLink . $disabled . "\n            </div>\n        </div>";
    }
    public function renderBooleanItem($data, $isDisabled)
    {
        $primarySuccessCount = (bool) $data[$this->item->getSuccessCountIdentifier()];
        $name = $this->item->getName();
        if ($name == "WHMCS Updates" && array_key_exists("update.available", $data) && $data["update.available"] == 1) {
            $name = "<a href=\"update.php\">An Update is Available</a>";
            if (array_key_exists("update.version", $data) && $data["update.version"]) {
                $name = "<a href=\"update.php\">" . $data["update.version"] . " is Available</a>";
            }
        } else {
            if ($name == "WHMCS Updates") {
                $name = "No Update Available";
            }
        }
        $icon = $primarySuccessCount ? "fas fa-check" : "fas fa-times";
        $disabled = "";
        if ($isDisabled && !$primarySuccessCount) {
            $disabled = "<small>Disabled</small>";
        }
        return "<div class=\"widget\">\n            <div class=\"info-container info-container-boolean\">\n                <div class=\"pull-right\"><i class=\"" . $this->item->getIcon() . " fa-2x\"></i></div>\n                <p class=\"intro\">\n                    <span class=\"status\"><i class=\"" . $icon . "\"></i></span>&nbsp;\n                    " . $name . "\n                </p>\n                " . $disabled . "\n            </div>\n        </div>";
    }
}

?>