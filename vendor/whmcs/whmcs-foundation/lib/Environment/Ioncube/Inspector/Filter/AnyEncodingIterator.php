<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Inspector\Filter;

class AnyEncodingIterator extends AbstractCacheIterator
{
    public function accept(\WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface $current)
    {
        if (in_array($current->getEncoderVersion(), array(\WHMCS\Environment\Ioncube\Contracts\EncodedFileInterface::ENCODER_VERSION_NONE, \WHMCS\Environment\Ioncube\Contracts\EncodedFileInterface::ENCODER_VERSION_UNKNOWN))) {
            return false;
        }
        return true;
    }
}

?>