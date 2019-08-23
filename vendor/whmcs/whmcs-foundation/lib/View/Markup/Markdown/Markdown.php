<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Markdown;

class Markdown extends \Michelf\MarkdownExtra
{
    public $no_markup = true;
    public $email_friendly = false;
    public function __construct()
    {
        $this->span_gamut += array("doTextWithBreaks" => 100);
        $this->document_gamut += array("doHtmlPurifier" => 100);
        parent::__construct();
        $this->predef_urls = array();
        $this->predef_titles = array();
        $this->predef_abbr = array();
    }
    public static function defaultTransform($text)
    {
        $text = \WHMCS\Input\Sanitize::decode($text);
        return parent::defaultTransform($text);
    }
    protected function doHtmlPurifier($text)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set("HTML.TargetBlank", true);
        $config->set("HTML.ForbiddenElements", array("img"));
        $config->set("CSS.ForbiddenProperties", array("background-image"));
        $config->set("Cache.SerializerPath", \App::getTemplatesCacheDir());
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($text);
    }
    protected function doTextWithBreaks($text)
    {
        $lines = explode("\n", trim($text));
        $textWithBreaks = "";
        $numberOfLines = count($lines) - 1;
        foreach ($lines as $i => $line) {
            $textWithBreaks .= $line;
            if ($i < $numberOfLines) {
                $textWithBreaks .= "<br />";
            }
            $textWithBreaks .= "\n";
        }
        return $textWithBreaks;
    }
    protected function doAutoLinks($text)
    {
        $text = parent::doAutoLinks($text);
        $text = $this->legacyAutoHyperLink($text);
        return $text;
    }
    protected function _doAutoLinks_url_callback($matches)
    {
        $url = $this->encodeURLAttribute($matches[1], $text);
        $targetClass = "";
        if (substr($url, 0, 4) == "http") {
            $targetClass = " target=\"_blank\" class=\"autoLinked\"";
        }
        $link = "<a href=\"" . $url . "\"" . $targetClass . ">" . $text . "</a>";
        return $this->hashPart($link);
    }
    protected function _doAnchors_inline_callback($matches)
    {
        $whole_match = $matches[1];
        $link_text = $this->runSpanGamut($matches[2]);
        $url = $matches[3] == "" ? $matches[4] : $matches[3];
        $title =& $matches[7];
        $attr = $this->doExtraAttributes("a", $dummy =& $matches[8]);
        $unhashed = $this->unhash($url);
        if ($unhashed != $url) {
            $url = preg_replace("/^<(.*)>\$/", "\\1", $unhashed);
        }
        $url = $this->encodeURLAttribute($url);
        $result = "<a href=\"" . $url . "\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"" . $title . "\"";
        }
        $result .= $attr;
        $targetClass = "";
        if (substr($url, 0, 4) == "http") {
            $targetClass = " target=\"_blank\" class=\"autoLinked\"";
        }
        $link_text = $this->runSpanGamut($link_text);
        $result .= (string) $targetClass . ">" . $link_text . "</a>";
        return $this->hashPart($result);
    }
    protected function _doImages_reference_callback($matches)
    {
        list(, $whole_match, $alt_text) = $matches;
        $link_id = strtolower($matches[3]);
        if ($link_id == "") {
            $link_id = strtolower($alt_text);
        }
        if (isset($this->urls[$link_id])) {
            $result = "";
        } else {
            $result = $whole_match;
        }
        return $result;
    }
    protected function _doImages_inline_callback($matches)
    {
        $result = "";
        return $this->hashPart($result);
    }
    protected function _doLinkReplace_callback(array $matches)
    {
        list($url, , , $optionalS, $subDomain, $domain, $pathAndQuery) = $matches;
        $displayUrl = $url;
        $pathAndQuery = trim($pathAndQuery);
        $characterMatches = array();
        if (preg_match("%(&quot;)|(&#039;)\$%", trim($pathAndQuery), $characterMatches)) {
            $pathAndQuery = preg_replace("/" . preg_quote($characterMatches[0]) . "\$/", "", $pathAndQuery);
            $displayUrl = preg_replace("/" . preg_quote($characterMatches[0]) . "\$/", "", $displayUrl);
        } else {
            $characterMatches[0] = "";
        }
        $fullUrl = "http" . $optionalS . "://" . $subDomain . $domain . $pathAndQuery;
        return $this->hashPart("<a href=\"" . $fullUrl . "\" target=\"_blank\" class=\"autoLinked\">" . $displayUrl . "</a>" . $characterMatches[0]);
    }
    protected function legacyAutoHyperLink($message)
    {
        $regex = "/((http(s?):\\/\\/)|(www\\.))([\\w\\.]+)([a-zA-Z0-9?&%#~.;:\\/=+_-]+)/i";
        return preg_replace_callback($regex, array($this, "_doLinkReplace_callback"), $message);
    }
    protected function formParagraphs($text)
    {
        $text = preg_replace("/\\A\\n+|\\n+\\z/", "", $text);
        $grafs = preg_split("/\\n{2,}/", $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($grafs as $key => $value) {
            $value = trim($this->runSpanGamut($value));
            $is_p = !preg_match("/^B\\x1A[0-9]+B|^C\\x1A[0-9]+C\$/", $value);
            if ($is_p) {
                $value = "<p>" . $value . "</p>";
            }
            $grafs[$key] = $value;
        }
        $text = implode("", $grafs);
        $text = $this->unhash($text);
        return $text;
    }
    protected function _doFencedCodeBlocks_callback($matches)
    {
        $classname =& $matches[2];
        $attrs =& $matches[3];
        $codeblock = $matches[4];
        if ($this->code_block_content_func) {
            $codeblock = call_user_func($this->code_block_content_func, $codeblock, $classname);
        } else {
            $codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);
        }
        $codeblock = preg_replace_callback("/^\\n+/", array($this, "_doFencedCodeBlocks_newlines"), $codeblock);
        $classes = array();
        if ($classname != "") {
            if ($classname[0] == ".") {
                $classname = substr($classname, 1);
            }
            $classes[] = $this->code_class_prefix . $classname;
        }
        $attr_str = $this->doExtraAttributes($this->code_attr_on_pre ? "pre" : "code", $attrs, null, $classes);
        $pre_attr_str = $this->code_attr_on_pre ? $attr_str : "";
        $code_attr_str = $this->code_attr_on_pre ? "" : $attr_str;
        $inlineCss = "";
        if ($this->email_friendly) {
            $inlineCss = " style=\"padding:12px; background-color: #444; color: #f8f8f8; border-radius: 4px;\"";
        }
        $codeblock = "<pre" . $pre_attr_str . $inlineCss . "><code" . $code_attr_str . ">" . $codeblock . "</code></pre>";
        return "\n\n" . $this->hashBlock($codeblock) . "\n\n";
    }
    protected function _doTable_callback($matches)
    {
        list(, $head, $underline, $content) = $matches;
        $head = preg_replace("/[|] *\$/m", "", $head);
        $underline = preg_replace("/[|] *\$/m", "", $underline);
        $content = preg_replace("/[|] *\$/m", "", $content);
        $this->table_align_class_tmpl = "text-%%";
        $separators = preg_split("/ *[|] */", $underline);
        foreach ($separators as $n => $s) {
            if (preg_match("/^ *-+: *\$/", $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr("right");
            } else {
                if (preg_match("/^ *:-+: *\$/", $s)) {
                    $attr[$n] = $this->_doTable_makeAlignAttr("center");
                } else {
                    if (preg_match("/^ *:-+ *\$/", $s)) {
                        $attr[$n] = $this->_doTable_makeAlignAttr("left");
                    } else {
                        $attr[$n] = "";
                    }
                }
            }
        }
        $head = $this->parseSpan($head);
        $headers = preg_split("/ *[|] */", $head);
        $col_count = count($headers);
        $attr = array_pad($attr, $col_count, "");
        $text = "<div class=\"table-responsive\">\n";
        $text .= "<table>\n";
        $text .= "<thead>\n";
        $text .= "<tr>\n";
        foreach ($headers as $n => $header) {
            $text .= "  <th" . $attr[$n] . ">" . $this->runSpanGamut(trim($header)) . "</th>\n";
        }
        $text .= "</tr>\n";
        $text .= "</thead>\n";
        $rows = explode("\n", trim($content, "\n"));
        $text .= "<tbody>\n";
        foreach ($rows as $row) {
            $row = $this->parseSpan($row);
            $row_cells = preg_split("/ *[|] */", $row, $col_count);
            $row_cells = array_pad($row_cells, $col_count, "");
            $text .= "<tr>\n";
            foreach ($row_cells as $n => $cell) {
                $text .= "  <td" . $attr[$n] . ">" . $this->runSpanGamut(trim($cell)) . "</td>\n";
            }
            $text .= "</tr>\n";
        }
        $text .= "</tbody>\n";
        $text .= "</table>\n";
        $text .= "</div>";
        return $this->hashBlock($text) . "\n";
    }
    protected function _doLists_callback($matches)
    {
        $result = parent::_doLists_callback($matches);
        return preg_replace("|^\nB|", "B", $result);
    }
}

?>