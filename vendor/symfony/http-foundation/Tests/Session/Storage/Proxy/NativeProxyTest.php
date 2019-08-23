<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation\Tests\Session\Storage\Proxy;

use Symfony\Component\HttpFoundation\Session\Storage\Proxy\NativeProxy;
/**
 * Test class for NativeProxy.
 *
 * @author Drak <drak@zikula.org>
 */
class NativeProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testIsWrapper()
    {
        $proxy = new NativeProxy();
        $this->assertFalse($proxy->isWrapper());
    }
    public function testGetSaveHandlerName()
    {
        $name = ini_get('session.save_handler');
        $proxy = new NativeProxy();
        $this->assertEquals($name, $proxy->getSaveHandlerName());
    }
}

?>