<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Assent\View;

class AssentPage extends \WHMCS\Admin\ApplicationSupport\View\Html\Php\TemplatePage
{
    public function getTemplateDirectory()
    {
        return parent::getTemplateDirectory() . DIRECTORY_SEPARATOR . "assent";
    }
}

?>