<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator\Validation;

class InvalidCertificate extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;
    const PATTERN_FAILED_CERT_LOAD = "/Invalid certificate content/";
    public function getTitle()
    {
        return "Certification Error - Invalid or Corrupt Certificate";
    }
    public function getHelpUrl()
    {
        return "https://docs.whmcs.com/Automatic_Updater#Certification_Error";
    }
    protected function isKnown($data)
    {
        return preg_match(self::PATTERN_FAILED_CERT_LOAD, $data);
    }
}

?>