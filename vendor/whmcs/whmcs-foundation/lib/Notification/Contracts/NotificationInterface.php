<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Contracts;

interface NotificationInterface
{
    public function getTitle();
    public function setTitle($title);
    public function getMessage();
    public function setMessage($message);
    public function getUrl();
    public function setUrl($url);
    public function getAttributes();
    public function setAttributes($attributes);
    public function addAttribute(NotificationAttributeInterface $attribute);
}

?>