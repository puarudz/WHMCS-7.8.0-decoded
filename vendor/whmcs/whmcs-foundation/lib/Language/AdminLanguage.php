<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Language;

class AdminLanguage extends AbstractLanguage
{
    protected $globalVariable = "_ADMINLANG";
    public static function getDirectory()
    {
        $adminDirectory = \App::get_admin_folder_name();
        return ROOTDIR . DIRECTORY_SEPARATOR . $adminDirectory . DIRECTORY_SEPARATOR . "lang";
    }
    public static function factory($languageName = self::FALLBACK_LANGUAGE)
    {
        $validLanguageName = self::getValidLanguageName($languageName);
        return static::findOrCreate($validLanguageName);
    }
}

?>