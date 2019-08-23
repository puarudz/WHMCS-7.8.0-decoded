<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Loader;

use Knp\Menu\ItemInterface;
interface LoaderInterface
{
    /**
     * Loads the data into a menu item
     *
     * @param mixed $data
     *
     * @return ItemInterface
     */
    public function load($data);
    /**
     * Checks whether the loader can load these data
     *
     * @param mixed $data
     *
     * @return boolean
     */
    public function supports($data);
}

?>