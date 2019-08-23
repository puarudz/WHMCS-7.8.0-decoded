<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * My Licenses Menu Item
 *
 * Adds a 'My Licenses' link to the Services dropdown menu within the
 * client area linking to a product/service listing filtered for
 * licensing addon related products.
 *
 * @param WHMCS\Menu\Item $menu
 */
add_hook('ClientAreaPrimaryNavbar', -1, function ($menu) {
    // check services menu exists
    if (!is_null($menu->getChild('Services'))) {
        // add my licenses link
        $menu->getChild('Services')->addChild(Lang::trans('licensingaddon.mylicenses'), array('uri' => 'clientarea.php?action=products&module=licensing', 'order' => 11));
    }
});

?>