<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator;

class NoMatchDecorator extends AbstractMatchDecorator
{
    use GenericMatchDecorationTrait;
    public function getTitle()
    {
        return "Error";
    }
    public function getHelpUrl()
    {
        return "https://docs.whmcs.com/Automatic_Updater#Troubleshooting";
    }
    protected function isKnown($data)
    {
        return true;
    }
}

?>