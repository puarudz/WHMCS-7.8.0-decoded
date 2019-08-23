<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Smarty;

class TemplateBody extends BodyContentWrapper
{
    public function __construct($bodyTemplateName)
    {
        parent::__construct();
        $this->setTemplateName($bodyTemplateName);
    }
    public function getBodyContent()
    {
        if (!$this->bodyContent) {
            $this->bodyContent = "";
            $smarty = $this->getTemplateEngine();
            if ($this->getTemplateName()) {
                $this->bodyContent = $smarty->fetch($this->getTemplateDirectory() . "/" . $this->getTemplateName() . ".tpl");
            }
        }
        return $this->bodyContent;
    }
}

?>