<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator;

trait GenericMatchDecorationTrait
{
    public function toHtml()
    {
        return $this->toGenericHtml(implode("\n", $this->getParsedMessageList()));
    }
    public function toPlain()
    {
        return $this->toGenericPlain(implode("\n", $this->getParsedMessageList()));
    }
}

?>