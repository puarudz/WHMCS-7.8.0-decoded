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
namespace Symfony\Component\Config\Definition\Builder;

/**
 * This class builds validation conditions.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ValidationBuilder
{
    protected $node;
    public $rules = array();
    /**
     * Constructor.
     *
     * @param NodeDefinition $node The related node
     */
    public function __construct(NodeDefinition $node)
    {
        $this->node = $node;
    }
    /**
     * Registers a closure to run as normalization or an expression builder to build it if null is provided.
     *
     * @param \Closure $closure
     *
     * @return ExprBuilder|$this
     */
    public function rule(\Closure $closure = null)
    {
        if (null !== $closure) {
            $this->rules[] = $closure;
            return $this;
        }
        return $this->rules[] = new ExprBuilder($this->node);
    }
}

?>