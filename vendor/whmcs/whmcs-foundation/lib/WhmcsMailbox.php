<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class WhmcsMailbox extends \PhpImap\Mailbox
{
    protected function convertStringEncoding($string, $fromEncoding, $toEncoding)
    {
        if (strcasecmp($fromEncoding, "iso-8859-8-i") == 0) {
            $fromEncoding = "iso-8859-8";
        }
        return parent::convertStringEncoding($string, $fromEncoding, $toEncoding);
    }
}

?>