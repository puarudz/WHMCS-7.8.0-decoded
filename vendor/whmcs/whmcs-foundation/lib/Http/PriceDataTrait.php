<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http;

trait PriceDataTrait
{
    public function mutatePriceToFull($data = array())
    {
        array_walk_recursive($data, function (&$item) {
            if ($item instanceof \WHMCS\View\Formatter\Price) {
                $item = $item->toFull();
            }
        });
        return $data;
    }
}

?>