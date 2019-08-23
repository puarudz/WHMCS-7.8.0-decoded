<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../init.php";
/*
*** USAGE SAMPLES ***
<script language="javascript" src="feeds/cartnumitems.php"></script>
*/
$products = isset($_SESSION["cart"]["products"]) && is_array($_SESSION["cart"]["products"]) ? $_SESSION["cart"]["products"] : array();
$addons = isset($_SESSION["cart"]["addons"]) && is_array($_SESSION["cart"]["addons"]) ? $_SESSION["cart"]["addons"] : array();
$domains = isset($_SESSION["cart"]["domains"]) && is_array($_SESSION["cart"]["domains"]) ? $_SESSION["cart"]["domains"] : array();
$renewals = isset($_SESSION["cart"]["renewals"]) && is_array($_SESSION["cart"]["renewals"]) ? $_SESSION["cart"]["renewals"] : array();
$cartitems = count($products) + count($addons) + count($domains) + count($renewals);
$items = $cartitems == 1 ? 'item' : 'items';
widgetoutput('' . \Lang::trans('feeds.itemsInBasket', [':count' => $cartitems]) . '');
function widgetoutput($value)
{
    echo "document.write('" . addslashes($value) . "');";
    exit;
}

?>