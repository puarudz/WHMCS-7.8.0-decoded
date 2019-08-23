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
namespace Symfony\Component\Config\Tests\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\NodeBuilder as BaseNodeBuilder;
class NodeBuilder extends BaseNodeBuilder
{
    public function barNode($name)
    {
        return $this->node($name, 'bar');
    }
    protected function getNodeClass($type)
    {
        switch ($type) {
            case 'variable':
                return __NAMESPACE__ . '\\' . ucfirst($type) . 'NodeDefinition';
            case 'bar':
                return __NAMESPACE__ . '\\' . ucfirst($type) . 'NodeDefinition';
            default:
                return parent::getNodeClass($type);
        }
    }
}

?>