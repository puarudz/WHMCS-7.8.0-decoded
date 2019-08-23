<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

trait BodyContentTrait
{
    protected $bodyContent = "";
    public function getBodyContent()
    {
        return $this->bodyContent;
    }
    public function setBodyContent($content)
    {
        $this->bodyContent = (string) $content;
        return $this;
    }
}

?>