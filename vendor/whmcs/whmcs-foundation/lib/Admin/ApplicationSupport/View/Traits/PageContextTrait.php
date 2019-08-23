<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

trait PageContextTrait
{
    protected $charset = "";
    protected $favicon = "";
    protected $title = "";
    protected $helpLink = "";
    protected $csrfToken = "";
    protected $versionHash = "";
    protected $dateFormat = "";
    protected $htmlHeadElements = array();
    public function getFavicon()
    {
        return $this->favicon;
    }
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;
        return $this;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function getHelpLink()
    {
        return $this->helpLink;
    }
    public function setHelpLink($helpLink)
    {
        $this->helpLink = $helpLink;
        return $this;
    }
    public function getCsrfToken()
    {
        if (!$this->csrfToken) {
            $this->setCsrfToken(generate_token("plain"));
        }
        return $this->csrfToken;
    }
    public function setCsrfToken($csrfToken)
    {
        $this->csrfToken = $csrfToken;
        return $this;
    }
    public function getVersionHash()
    {
        if (!$this->versionHash) {
            $this->setVersionHash(\WHMCS\View\Helper::getAssetVersionHash());
        }
        return $this->versionHash;
    }
    public function setVersionHash($versionHash)
    {
        $this->versionHash = $versionHash;
        return $this;
    }
    public function getCharset()
    {
        if (!$this->charset) {
            $this->setCharset((string) \WHMCS\Config\Setting::getValue("Charset"));
        }
        return $this->charset;
    }
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }
    public function getDateFormat()
    {
        if (!$this->dateFormat) {
            $this->setDateFormat(str_replace(array("DD", "MM", "YYYY"), array("dd", "mm", "yy"), \WHMCS\Config\Setting::getValue("DateFormat")));
        }
        return $this->dateFormat;
    }
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }
    public function getLicenseBannerHtml()
    {
        $html = "";
        $licenseBannerMsg = \DI::make("license")->getBanner();
        if ($licenseBannerMsg) {
            $html = "<script type=\"text/javascript\">\n\$(function(){\n    \$(window).resize(function(e){\n        placeDevBanner();\n    });\n    \$.event.add(window, \"scroll\", function() {\n        placeDevBanner();\n    });\n    placeDevBanner();\n    \$(\"#whmcsdevbanner\").css(\"position\",\"absolute\");\n    \$(\"#whmcsdevbanner\").css(\"display\",\"inline\");\n    \$(\"body\").css(\"margin\",\"0 0 \"+\$(\"#whmcsdevbanner\").height()+\"px 0\");\n});\nfunction placeDevBanner() {\n    var docheight = \$(\"body\").height();\n    var newheight = \$(document).scrollTop() + parseInt(\$(window).height()) - parseInt(\$(\"#whmcsdevbanner\").height());\n    if (newheight>docheight) newheight = docheight;\n    \$(\"#whmcsdevbanner\").css(\"top\",newheight);\n    \$(\"body\").css(\"margin\",\"0 0 \"+\$(\"#whmcsdevbanner\").height()+\"px 0\");\n}\n</script>\n<div id=\"whmcsdevbanner\" style=\"display:block;margin:0;padding:0;width:100%;background-color:#ffffd2;\">\n    <div style=\"padding:10px 35px;font-size:16px;text-align:center;color:#555;\">" . $licenseBannerMsg . "</div>\n</div>";
        }
        return $html;
    }
    public function getHtmlHeadElements()
    {
        return $this->htmlHeadElements;
    }
    public function setHtmlHeadElements($htmlHeadElements)
    {
        $this->htmlHeadElements = $htmlHeadElements;
        return $this;
    }
    public function getFormattedHtmlHeadContent()
    {
        return implode("\n", $this->getHtmlHeadElements());
    }
    public function addHtmlHeadElement($htmlHeadElement)
    {
        $this->htmlHeadElements[] = $htmlHeadElement;
    }
}

?>