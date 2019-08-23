<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
$container = new ContainerBuilder();
$container->register('foo', 'FooClass\\Foo')->setDecoratedService('bar');
return $container;

?>