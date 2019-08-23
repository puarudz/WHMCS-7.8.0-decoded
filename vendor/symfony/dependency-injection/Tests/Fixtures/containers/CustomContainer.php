<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Symfony\Component\DependencyInjection\Tests\Fixtures\containers;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
class CustomContainer extends Container
{
    public function getBarService()
    {
    }
    public function getFoobarService()
    {
    }
}

?>