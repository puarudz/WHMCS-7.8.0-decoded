<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Renderer;

@trigger_error('The ' . __NAMESPACE__ . '\\PimpleProvider class is deprecated since version 2.1 and will be removed in 3.0. Use the ' . __NAMESPACE__ . '\\ArrayAccessProvider class instead.', E_USER_DEPRECATED);
/**
 * Renderer provider getting renderers from a Pimple 1 container
 *
 * @deprecated use the ArrayAccessProvider instead.
 */
class PimpleProvider extends ArrayAccessProvider
{
    public function __construct(\Pimple $pimple, $defaultRenderer, array $rendererIds)
    {
        parent::__construct($pimple, $defaultRenderer, $rendererIds);
    }
}

?>