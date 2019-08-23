<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Helper;

class ClientSearchDropdown implements \WHMCS\View\HtmlPageInterface
{
    protected $nameAttribute = "";
    protected $selected = "";
    protected $selectOptions = array();
    protected $placeholderText = "";
    protected $dataValueFieldAttribute = "id";
    protected $fieldTabIndex = 0;
    public function __construct($nameAttribute, $selectedOption = "", array $selectOptions = array(), $placeholderText = "", $dataValueFieldAttribute = "id", $fieldTabIndex = 0)
    {
        $this->setNameAttribute($nameAttribute)->setSelected($selectedOption)->setSelectOptions($selectOptions)->setPlaceholderText($placeholderText)->setDataValueFieldAttribute($dataValueFieldAttribute)->setFieldTabIndex($fieldTabIndex);
    }
    public function getFormattedHtmlHeadContent()
    {
        return "<script>function getClientSearchPostUrl() { return '" . routePath("admin-search-client") . "'; }</script>" . PHP_EOL . "<script type=\"text/javascript\" " . "src=\"../assets/js/AdminClientDropdown.js\"></script>";
    }
    public function getFormattedHeaderContent()
    {
        return "";
    }
    protected function getHtmlSelectOptions()
    {
        $html = array();
        $options = $this->getSelectOptions();
        if (!empty($options)) {
            $selectedOption = $this->getSelected();
            foreach ($options as $optionValue => $optionText) {
                if (!$optionValue && !$optionText) {
                    continue;
                }
                $selected = "";
                if ((string) $optionValue === $selectedOption) {
                    $selected = "selected=\"selected\"";
                }
                $html[] = sprintf("<option value=\"%s\" %s>%s</option>", $optionValue, $selected, $optionText);
            }
        }
        return implode(PHP_EOL, $html);
    }
    public function getFormattedBodyContent()
    {
        $placeHolderAttribute = "";
        if ($this->getPlaceholderText()) {
            $placeHolderAttribute = "placeholder=\"" . $this->getPlaceholderText() . "\"";
        }
        $tabIndexAttribute = "";
        if ($this->getFieldTabIndex() != 0) {
            $tabIndexAttribute .= "tabindex=\"" . $this->getFieldTabIndex() . "\"";
        }
        return sprintf("<select id=\"select%s\" name=\"%s\"" . " class=\"form-control selectize selectize-client-search\"" . " data-value-field=\"%s\"" . " data-active-label=\"%s\"" . " data-inactive-label=\"%s\"" . " %s %s>%s</select>", ucfirst($this->getNameAttribute()), $this->getNameAttribute(), $this->getDataValueFieldAttribute(), \AdminLang::trans("status.active"), \AdminLang::trans("status.inactive"), $placeHolderAttribute, $tabIndexAttribute, $this->getHtmlSelectOptions());
    }
    public function getFormattedFooterContent()
    {
        return "";
    }
    public function getNameAttribute()
    {
        return $this->nameAttribute;
    }
    public function setNameAttribute($nameAttribute = "")
    {
        $this->nameAttribute = (string) $nameAttribute;
        return $this;
    }
    public function getSelected()
    {
        return $this->selected;
    }
    public function setSelected($selectedOption = "")
    {
        $this->selected = (string) $selectedOption;
        return $this;
    }
    public function getSelectOptions()
    {
        return $this->selectOptions;
    }
    public function setSelectOptions(array $selectOptions = array())
    {
        $this->selectOptions = $selectOptions;
        return $this;
    }
    public function getPlaceholderText()
    {
        return $this->placeholderText;
    }
    public function setPlaceholderText($placeholderText = "")
    {
        $this->placeholderText = (string) $placeholderText;
        return $this;
    }
    public function getDataValueFieldAttribute()
    {
        return $this->dataValueFieldAttribute;
    }
    public function setDataValueFieldAttribute($dataValueFieldAttribute = "id")
    {
        $this->dataValueFieldAttribute = (string) $dataValueFieldAttribute;
        return $this;
    }
    public function getFieldTabIndex()
    {
        return $this->fieldTabIndex;
    }
    public function setFieldTabIndex($fieldTabIndex = 0)
    {
        $this->fieldTabIndex = (int) $fieldTabIndex;
        return $this;
    }
}

?>