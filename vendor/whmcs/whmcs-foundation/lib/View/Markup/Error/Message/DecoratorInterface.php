<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message;

interface DecoratorInterface
{
    public function toHtml();
    public function toPlain();
    public function getTitle();
    public function getHelpUrl();
}

?>