<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator;

interface MatchDecoratorInterface extends \WHMCS\View\Markup\Error\Message\DecoratorInterface, \WHMCS\View\Markup\Error\ErrorLevelInterface
{
    public function wrap(\Iterator $data);
    public function getData();
    public function hasMatch();
}

?>