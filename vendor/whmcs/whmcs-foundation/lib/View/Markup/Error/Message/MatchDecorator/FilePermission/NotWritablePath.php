<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error\Message\MatchDecorator\FilePermission;

class NotWritablePath extends \WHMCS\View\Markup\Error\Message\MatchDecorator\AbstractMatchDecorator
{
    use \WHMCS\View\Markup\Error\Message\MatchDecorator\GenericMatchDecorationTrait;
    const PATTERN_PATH_NOT_WRITABLE = "/Permission Error. Failed to create or modify path: (.*)/";
    const PATTERN_VENDOR_NOT_WRITABLE = "/(vendor(?:\\/whmcs(?:\\/whmcs)?)?) does not exist and could not be created/";
    const PATTERN_VENDOR_NO_DELETE = "/Could not delete (\\/(?:.*)vendor\\/(?:.*))/";
    const PATTERN_WHMCS_WHMCS_MISSING = "/file could not be written to ((?:.*)\\/vendor\\/whmcs\\/whmcs).*\\.zip: failed to open stream: No such file or directory/";
    const PATTERN_WHMCS_WHMCS_NOT_MUTABLE = "/file could not be written to ((?:.*)\\/vendor\\/whmcs\\/whmcs).*\\.zip: failed to open stream: Permission denied/";
    public function getTitle()
    {
        return "Insufficient File Permissions";
    }
    public function getHelpUrl()
    {
        return "https://docs.whmcs.com/Automatic_Updater#Permission_Errors";
    }
    protected function isKnown($data)
    {
        return preg_match(static::PATTERN_PATH_NOT_WRITABLE, $data) || preg_match(static::PATTERN_VENDOR_NOT_WRITABLE, $data) || preg_match(static::PATTERN_VENDOR_NO_DELETE, $data) || preg_match(static::PATTERN_WHMCS_WHMCS_MISSING, $data) || preg_match(static::PATTERN_WHMCS_WHMCS_NOT_MUTABLE, $data);
    }
}

?>