<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
$container = new ContainerBuilder(new ParameterBag(array('FOO' => '%baz%', 'baz' => 'bar', 'bar' => 'foo is %%foo bar', 'escape' => '@escapeme', 'values' => array(true, false, null, 0, 1000.3, 'true', 'false', 'null'))));
return $container;

?>