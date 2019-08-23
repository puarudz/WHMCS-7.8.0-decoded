<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class Registrars
{
    public function getTemplateVariables()
    {
        $vars = array();
        $assetHelper = \DI::make("asset");
        $vars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
        return $vars;
    }
    public function getStepContent()
    {
        return "\n<div class=\"alert alert-info info-alert\">{lang key=\"wizard.sellingDomains\"}</div>\n\n<div class=\"form-horizontal\">\n    <div class=\"form-group\">\n        <label for=\"inputDomainsEnable\" class=\"col-sm-3 control-label\">{lang key=\"wizard.enableDomains\"}</label>\n        <div class=\"col-sm-9\">\n            <input id=\"inputDomainsEnable\" type=\"checkbox\" name=\"EnableDomains\" value=\"1\" data-size=\"mini\" checked />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputExtensionCom\" class=\"col-sm-3 control-label\">{lang key=\"wizard.autoSetupTlds\"}</label>\n        <div class=\"col-sm-9\">\n            <div style=\"help-block\">{lang key=\"wizard.setupExtensions\"}<br /><small>{lang key=\"wizard.extensionsAddMoreLater\"}</small></div>\n            <div class=\"bottom-margin-5\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"Extensions[]\" value=\".com\" id=\"inputExtensionCom\" checked> .com\n                </label>\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"Extensions[]\" value=\".net\" checked> .net\n                </label>\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"Extensions[]\" value=\".org\" checked> .org\n                </label>\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"Extensions[]\" value=\".biz\" checked> .biz\n                </label>\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"Extensions[]\" value=\".info\" checked> .info\n                </label>\n            </div>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDomainPrice\" class=\"col-sm-3 control-label\">{lang key=\"fields.price\"}</label>\n        <div class=\"col-sm-9\">\n            <input type=\"text\" name=\"DomainPrice\" id=\"inputDomainPrice\" placeholder=\"14.95\" value=\"14.95\" class=\"form-control input-100 input-inline\" /> {lang key=\"wizard.extensionsChangeLater\"}\n        </div>\n    </div>\n</div>\n\n<div class=\"alert alert-warning info-alert\" style=\"margin:20px 0 5px;\">{lang key=\"wizard.domainRegistrarPromo\"}</div>\n\n<div class=\"clearfix\">\n    <div style=\"float:left;\"><img src=\"{\$BASE_PATH_IMG}/wizard/enom.png\" alt=\"{lang key=\"wizard.registrarEnom\"}\"></div>\n    <div style=\"float:left;padding:20px;width:390px;\">{lang key=\"wizard.registrarEnomDescription\"}</div>\n</div>\n\n<div class=\"row bottom-margin-5\">\n    <div class=\"col-sm-3 text-right\">\n        <label>\n            <input id=\"checkboxEnomEnable\" type=\"checkbox\" name=\"EnomEnable\" checked> {lang key=\"wizard.enable\"}\n        </label>\n    </div>\n    <div class=\"col-sm-9\">\n        {lang key=\"wizard.createFreeEnomAccount\"}\n    </div>\n</div>\n";
    }
    public function save($data)
    {
        $enableDomains = isset($data["EnableDomains"]) ? trim($data["EnableDomains"]) : "";
        $Extensions = isset($data["Extensions"]) ? $data["Extensions"] : "";
        $DomainPrice = isset($data["DomainPrice"]) ? trim($data["DomainPrice"]) : "";
        $EnomEnable = isset($data["EnomEnable"]) ? trim($data["EnomEnable"]) : "";
        $domains = new \WHMCS\Admin\Setup\Domains();
        if ($enableDomains) {
            $domains->enable();
            $domains->setupTldsWithDefaultOptions($Extensions, $EnomEnable ? "enom" : "", $DomainPrice);
        } else {
            $domains->disable();
        }
        if (!$EnomEnable) {
            return array("skipNextStep" => true);
        }
    }
}

?>