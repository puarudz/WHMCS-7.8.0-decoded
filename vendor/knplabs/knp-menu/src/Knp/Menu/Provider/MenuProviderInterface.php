<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Provider;

interface MenuProviderInterface
{
    /**
     * Retrieves a menu by its name
     *
     * @param string $name
     * @param array  $options
     *
     * @return \Knp\Menu\ItemInterface
     * @throws \InvalidArgumentException if the menu does not exists
     */
    public function get($name, array $options = array());
    /**
     * Checks whether a menu exists in this provider
     *
     * @param string $name
     * @param array  $options
     *
     * @return boolean
     */
    public function has($name, array $options = array());
}

?>