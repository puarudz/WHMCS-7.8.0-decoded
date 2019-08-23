<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator\SystemRequirements;

class DiskQuotaExceeded extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;
    const PATTERN_DISK_QUOTA_EXCEEDED = "/Disk quota exceeded/";
    public function getTitle()
    {
        return "Insufficient Disk Space";
    }
    public function getHelpUrl()
    {
        return "https://docs.whmcs.com/Automatic_Updater#System_Requirements";
    }
    protected function isKnown($data)
    {
        return preg_match(static::PATTERN_DISK_QUOTA_EXCEEDED, $data);
    }
}

?>