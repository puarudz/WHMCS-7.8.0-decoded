<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Inspector\Filter;

abstract class AbstractAbsolutelyNonDecodableIterator extends AbstractCacheIterator
{
    public function accept(\WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface $current)
    {
        if ($this->getAssessment($current) === \WHMCS\Environment\Ioncube\Contracts\EncodedFileInterface::ASSESSMENT_COMPAT_NO) {
            return true;
        }
        return false;
    }
    public abstract function getAssessment(\WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface $file);
}

?>