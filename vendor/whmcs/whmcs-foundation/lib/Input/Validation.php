<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Input;

class Validation
{
    public function escapeshellcmd($string)
    {
        if (function_exists("escapeshellcmd") && \WHMCS\Environment\Php::functionEnabled("escapeshellcmd")) {
            return escapeshellcmd($string);
        }
        $shellCharacters = array("#", "&", ";", "`", "|", "*", "?", "~", "<", ">", "^", "(", ")", "[", "]", "{", "}", "\$", chr(10), chr(255));
        if (\WHMCS\Environment\OperatingSystem::isWindows()) {
            $shellCharacters[] = "%";
            $shellCharacters[] = "\\";
            $string = str_replace($shellCharacters, " ", $string);
            $quotePosition = $this->mismatchedQuotePosition($string);
            if ($quotePosition !== false) {
                $string = substr_replace($string, " ", $quotePosition, 1);
            }
            $quotePosition = $this->mismatchedQuotePosition($string, "'");
            if ($quotePosition !== false) {
                $string = substr_replace($string, " ", $quotePosition, 1);
            }
        } else {
            $string = str_replace("\\", "\\\\", $string);
            foreach ($shellCharacters as $shellCharacter) {
                $string = str_replace($shellCharacter, "\\" . $shellCharacter, $string);
            }
            $quotePosition = $this->mismatchedQuotePosition($string);
            if ($quotePosition !== false) {
                $string = substr_replace($string, "\\\"", $quotePosition, 1);
            }
            $quotePosition = $this->mismatchedQuotePosition($string, "'");
            if ($quotePosition !== false) {
                $string = substr_replace($string, "\\'", $quotePosition, 1);
            }
        }
        return $string;
    }
    public function mismatchedQuotePosition($string, $quoteCharacter = "\"")
    {
        return substr_count($string, $quoteCharacter) % 2 == 0 ? false : strrpos($string, $quoteCharacter);
    }
}

?>