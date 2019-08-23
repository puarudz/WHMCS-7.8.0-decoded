<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class twitterIntents
{
    private $url = NULL;
    private $username = NULL;
    private $version = NULL;
    private $dbPull = false;
    private $db = NULL;
    public function __construct($username = "", WHMCS\Version\SemanticVersion $version)
    {
        $this->username = trim($username);
        $this->url = "https://twitter.com/intent/user?screen_name=" . $this->username;
        $this->version = $version;
        $this->db = new WHMCS\TransientData();
    }
    protected function doDependencyCheck()
    {
        if (!class_exists("DOMDocument")) {
            logModuleCall("Twitter", "Accessing DOMDocument", "DOMDocument", "DOMDocument class does not exist.");
            return false;
        }
        if (!class_exists("DOMXPath")) {
            logModuleCall("Twitter", "Accessing DOMXPath", "DOMXPath", "DOMXPath class does not exist.");
            return false;
        }
        return true;
    }
    public function getTweets()
    {
        if (!$this->doDependencyCheck()) {
            return false;
        }
        if ($tweets = $this->getCachedTweets()) {
            return $tweets;
        }
        $tweets = $this->scrapePage();
        return $tweets;
    }
    protected function tidyHTML($html)
    {
        $tidy = new tidy();
        $tidy->ParseString($html);
        $tidy->cleanRepair();
        return (string) $tidy;
    }
    protected function scrapePage()
    {
        $twitterLink = curlCall($this->url, "");
        if (function_exists("mb_convert_encoding")) {
            $twitterLink = mb_convert_encoding($twitterLink, "HTML-ENTITIES", "UTF-8");
        } else {
            $twitterLink = "<?xml encoding=\"UTF-8\">" . $twitterLink;
        }
        if (class_exists("tidy")) {
            $twitterLink = $this->tidyHTML($twitterLink);
        }
        $doc = new DOMDocument();
        $doc->loadHTML($twitterLink);
        $xpath = new DOMXpath($doc);
        $tweetComments = $this->findClassNodes($xpath, "div", "tweet-text");
        $tweetDates = $this->findClassNodes($xpath, "span", "_timestamp");
        $tweetLinks = $this->findClassNodes($xpath, "div", "tweet-text", "/a");
        $tweetAbsLinks = $this->findClassNodes($xpath, "div", "tweet-text", "/a/@href");
        $tweets = array();
        foreach ($tweetComments as $key => $value) {
            $value = WHMCS\Input\Sanitize::encode($value);
            foreach ($tweetLinks as $k => $link) {
                if (strpos($value, $link) !== false) {
                    if (preg_match("%^/%", $tweetAbsLinks[$k])) {
                        continue;
                    }
                    $replace = "<a href = \"" . WHMCS\Input\Sanitize::encode($tweetAbsLinks[$k]) . "\" target = \"_blank\">" . WHMCS\Input\Sanitize::encode($link) . "</a>";
                    $value = str_replace($link, $replace, $value);
                }
            }
            $twitterdate = strtotime($tweetDates[$key]);
            if ($twitterdate === false) {
                $twitterdate = $tweetDates[$key];
            } else {
                $twitterdate = fromMySQLDate(date("Y-m-d H:i", $twitterdate), true);
            }
            $tweets[] = array("date" => $twitterdate, "tweet" => $value);
        }
        $this->cacheTweets($tweets);
        return $tweets;
    }
    protected function findClassNodes($xpath, $tag, $classname = "", $attributes = "")
    {
        $classname = trim($classname);
        if (strlen($classname) < 1) {
            return false;
        }
        $arr = array();
        $result = $xpath->query("//" . $tag . "[@class='" . $classname . "']" . $attributes);
        foreach ($result as $value) {
            $arr[] = trim($value->textContent);
        }
        return $arr;
    }
    protected function getCachedTweets()
    {
        $minVersion = "5.3.0";
        if (isset($this->version)) {
            if (WHMCS\Version\SemanticVersion::compare($this->version, new WHMCS\Version\SemanticVersion($minVersion), "<")) {
                return false;
            }
            $tweets = $this->db->retrieve("twitter");
            if (strlen(trim($tweets)) < 1) {
                return false;
            }
            $tweets = json_decode($tweets, true);
            $this->dbPull = true;
            return $tweets;
        }
        return false;
    }
    protected function cacheTweets($tweets = array())
    {
        if (count($tweets) < 1) {
            return false;
        }
        if ($this->dbPull) {
            return false;
        }
        $name = "twitter";
        $data = json_encode($tweets);
        $this->db->store($name, $data, 300);
    }
}

?>