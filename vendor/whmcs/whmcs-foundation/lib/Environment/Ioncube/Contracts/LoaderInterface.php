<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Contracts;

interface LoaderInterface
{
    public static function getVersion();
    public function compatAssessment($phpVersion, InspectedFileInterface $file);
    public function supportsBundledEncoding();
}

?>