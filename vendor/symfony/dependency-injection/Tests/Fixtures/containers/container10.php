<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . '/../includes/classes.php';
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
$container = new ContainerBuilder();
$container->register('foo', 'FooClass')->addArgument(new Reference('bar'));
return $container;

?>