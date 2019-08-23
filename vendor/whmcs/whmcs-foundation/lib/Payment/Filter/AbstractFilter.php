<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Filter;

abstract class AbstractFilter implements FilterInterface
{
    public function getFilteredIterator(\Iterator $iterator)
    {
        return new Iterator\CallbackIterator($iterator, array($this, "filter"));
    }
    public abstract function filter(\WHMCS\Payment\Adapter\AdapterInterface $adapter);
}

?>