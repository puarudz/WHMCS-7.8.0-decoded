<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Helper;

class ClientSelectedDropDown extends ClientSearchDropdown
{
    protected $selectedClientId = 0;
    public function __construct($nameAttribute = "userid", $selectedClientId = 0)
    {
        parent::__construct($nameAttribute, $selectedClientId, array(), \AdminLang::trans("global.typeToSearchClients"), "id", 0);
        $this->setSelectedClientId($selectedClientId);
    }
    public function getSelectedClientId()
    {
        return $this->selectedClientId;
    }
    public function setSelectedClientId($selectedClientId)
    {
        $this->selectedClientId = (int) $selectedClientId;
        return $this;
    }
    protected function getSelectOptionsForClientId($clientId = 0)
    {
        $selectOptions = array();
        if ($clientId) {
            $client = \WHMCS\Database\Capsule::table("tblclients")->find($clientId, array("firstname", "lastname", "companyname", "email"));
            if ($client) {
                $selectOptions[$clientId] = sprintf("%s %s%s", $client->firstname, $client->lastname, $client->companyname ? " (" . $client->companyname . ")" : "");
            }
        }
        return $selectOptions;
    }
    protected function getHtmlSelectOptions()
    {
        $this->setSelectOptions($this->getSelectOptionsForClientId($this->getSelectedClientId()));
        return parent::getHtmlSelectOptions();
    }
}

?>