<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../init.php";
require "../includes/clientfunctions.php";
require "../includes/orderfunctions.php";
require "../includes/invoicefunctions.php";
require "../includes/configoptionsfunctions.php";
require "../includes/cartfunctions.php";
require "../includes/domainfunctions.php";
/*
*** USAGE SAMPLES ***
<script language="javascript" src="feeds/carttotal.php"></script>
*/
$carttotals = calcCartTotals('', true, getCurrency());
$total = $carttotals['total'] ? $carttotals['total'] : formatCurrency(0);
widgetoutput($total);
function widgetoutput($value)
{
    echo "document.write('" . addslashes($value) . "');";
    exit;
}

?>