<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View;

class Asset
{
    protected $webRoot = "";
    public function __construct($webRoot)
    {
        $this->webRoot = $webRoot;
    }
    public function getWebRoot()
    {
        return $this->webRoot;
    }
    public function getJsPath()
    {
        return $this->getWebRoot() . "/assets/js";
    }
    public function getCssPath()
    {
        return $this->getWebRoot() . "/assets/css";
    }
    public function getImgPath()
    {
        return $this->getWebRoot() . "/assets/img";
    }
    public function getFontsPath()
    {
        return $this->getWebRoot() . "/assets/fonts";
    }
    public function getFontAwesomePath()
    {
        return $this->getWebRoot() . "/assets";
    }
    public function getFilesystemImgPath()
    {
        return ROOTDIR . "/assets/img";
    }
    public static function cssInclude($filename)
    {
        return sprintf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />", \DI::make("asset")->getCssPath() . "/" . $filename);
    }
    public static function conditionalFontawesomeCssInclude($html)
    {
        $conditionalRegex = "/\\/css\\/fontawesome[\\.a-zA-Z0-9\\-_\\/]*\\.css/";
        $filename = \DI::make("asset")->getFontAwesomePath() . "/css/fontawesome-all.min.css";
        $html = static::conditionalCssInclude($filename, $html, $conditionalRegex);
        return $html;
    }
    public static function conditionalCssInclude($filename, $html, $conditionalRegex = NULL)
    {
        if (is_null($conditionalRegex)) {
            $conditionalRegex = "/" . $filename . "/";
        }
        if (stripos($filename, "http") === 0 || strpos($filename, "/") === 0) {
            $fullPath = $filename;
        } else {
            $fullPath = \DI::make("asset")->getCssPath() . "/" . $filename;
        }
        $cssLink = sprintf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />", $fullPath);
        return static::conditionalInclude($html, $conditionalRegex, $cssLink);
    }
    public static function conditionalInclude($html, $conditionalRegex, $injectable)
    {
        $start = stripos($html, "<head>");
        $end = stripos($html, "</head>");
        if ($start !== false && $end !== false && $start < $end) {
            $headLength = $end - $start;
            $head = substr($html, $start, $headLength);
            if (!preg_match($conditionalRegex, $head)) {
                $comment = PHP_EOL . "<!-- Dynamic Template Compatibility -->" . PHP_EOL . "<!-- Please update your theme to include or have a " . "comment on the following to negate dynamic inclusion -->" . PHP_EOL;
                $head .= $comment . $injectable . PHP_EOL . PHP_EOL;
                $html = substr_replace($html, $head, $start, $headLength);
            }
        }
        return $html;
    }
    public static function jsInclude($filename)
    {
        return sprintf("<script type=\"text/javascript\" src=\"%s\"></script>", \DI::make("asset")->getJsPath() . "/" . $filename);
    }
    public static function imgTag($filename, $alt = "", $options = array())
    {
        $attributes = "";
        foreach ($options as $key => $value) {
            $attributes .= " " . $key . "=\"" . $value . "\"";
        }
        return sprintf("<img src=\"%s\" border=\"0\" alt=\"%s\"%s>", \DI::make("asset")->getImgPath() . "/" . $filename, $alt, $attributes);
    }
    public static function icon($rootClassName)
    {
        $iconClassParts = explode("-", $rootClassName, 2);
        return "<i class=\"" . $iconClassParts[0] . " " . $rootClassName . "\"></i>";
    }
}

?>