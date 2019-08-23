<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html;

abstract class AbstractTemplateEngine extends \WHMCS\Http\Message\AbstractViewableResponse implements \WHMCS\View\HtmlPageInterface
{
    use \WHMCS\Admin\ApplicationSupport\View\Traits\AdminHtmlViewTrait;
    public abstract function getFormattedHeaderContent();
    public abstract function getFormattedBodyContent();
    public abstract function getFormattedFooterContent();
    public function prepareVariableContent()
    {
        $this->standardPrepareVariableContent();
        $smarty = $this->getTemplateEngine();
        $smarty->assign($this->getTemplateVariables()->all());
        $smarty->assign($this->getNonHookTemplateVariables());
        return $this;
    }
    public function getOutputContent()
    {
        $this->prepareVariableContent();
        $hookVariables = $this->getTemplateVariables()->all();
        ob_start();
        $smarty = $this->getTemplateEngine();
        $hookVariables = $this->runHookAdminAreaPage($hookVariables);
        $smarty->assign($hookVariables);
        $htmlHeadElement = $this->getFormattedHtmlHeadContent();
        $smarty->assign("headoutput", $htmlHeadElement . "\n" . $this->runHookAdminHeadOutput($hookVariables));
        $smarty->assign("headeroutput", $this->runHookAdminHeaderOutput($hookVariables));
        $smarty->assign("footeroutput", $this->runHookAdminFooterOutput($hookVariables));
        $content = $this->getFormattedHeaderContent() . $this->getFormattedBodyContent();
        echo $content;
        echo $this->getFormattedFooterContent();
        $html = ob_get_clean();
        return (new \WHMCS\Admin\ApplicationSupport\View\PreRenderProcessor())->process($html);
    }
}

?>