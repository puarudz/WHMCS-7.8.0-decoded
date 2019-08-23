<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator\NetworkIssue;

class FailedKeyserverFetch extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;
    const PATTERN_GENERIC_GET_URL = "/Failed to get certificate metadata from keyserver. Error:/";
    const PATTERN_CRL_GET_URL = "/Failed to get CRL package from keyserver. Error:/";
    public function getTitle()
    {
        return "Network Issue - Failed Communication with Update Server";
    }
    public function getHelpUrl()
    {
        return "https://docs.whmcs.com/Automatic_Updater#Unable_to_connect_to_the_WHMCS_Update_Server";
    }
    protected function isKnown($data)
    {
        return preg_match(self::PATTERN_CRL_GET_URL, $data) || preg_match(self::PATTERN_GENERIC_GET_URL, $data);
    }
}

?>