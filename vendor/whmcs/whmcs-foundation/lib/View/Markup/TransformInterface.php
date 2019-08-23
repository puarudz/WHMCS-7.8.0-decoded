<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup;

interface TransformInterface
{
    const FORMAT_PLAIN = "plain";
    const FORMAT_BBCODE = "bbcode";
    const FORMAT_MARKDOWN = "markdown";
    const FORMAT_HTML = "html";
    public function transform($text, $markupFormat, $emailFriendly);
}

?>