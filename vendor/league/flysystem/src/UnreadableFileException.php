<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem;

use SplFileInfo;
class UnreadableFileException extends Exception
{
    public static function forFileInfo(SplFileInfo $fileInfo)
    {
        return new static(sprintf('Unreadable file encountered: %s', $fileInfo->getRealPath()));
    }
}

?>