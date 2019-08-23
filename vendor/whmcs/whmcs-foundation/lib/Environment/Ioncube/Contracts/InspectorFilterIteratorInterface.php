<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Contracts;

interface InspectorFilterIteratorInterface
{
    public function getPhpVersion();
    public function setPhpVersion($phpVersion);
    public function getFilterIterator(\Iterator $iterator);
    public function accept(InspectedFileInterface $current);
}

?>