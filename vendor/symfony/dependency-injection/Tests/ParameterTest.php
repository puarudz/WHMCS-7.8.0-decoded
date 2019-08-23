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
namespace Symfony\Component\DependencyInjection\Tests;

use Symfony\Component\DependencyInjection\Parameter;
class ParameterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $ref = new Parameter('foo');
        $this->assertEquals('foo', (string) $ref, '__construct() sets the id of the parameter, which is used for the __toString() method');
    }
}

?>