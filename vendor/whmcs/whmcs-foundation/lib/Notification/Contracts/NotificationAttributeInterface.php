<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Contracts;

interface NotificationAttributeInterface
{
    public function getLabel();
    public function setLabel($label);
    public function getValue();
    public function setValue($value);
    public function getUrl();
    public function setUrl($url);
    public function getStyle();
    public function setStyle($style);
    public function getIcon();
    public function setIcon($icon);
}

?>