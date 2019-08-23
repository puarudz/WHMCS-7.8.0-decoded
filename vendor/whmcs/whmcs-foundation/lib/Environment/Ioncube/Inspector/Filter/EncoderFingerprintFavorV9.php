<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Inspector\Filter;

class EncoderFingerprintFavorV9 extends AbstractAbsolutelyNonDecodableIterator
{
    public function getAssessment(\WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface $file)
    {
        return $file->getAnalyzer()->versionCompatibilityAssessment($this->getPhpVersion());
    }
}

?>