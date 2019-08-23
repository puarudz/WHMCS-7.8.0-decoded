<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Symfony\Component\DependencyInjection\Tests\Fixtures\includes;

use Symfony\Component\DependencyInjection\Tests\Compiler\Foo;
class FooVariadic
{
    public function __construct(Foo $foo)
    {
    }
    public function bar(...$arguments)
    {
    }
}

?>