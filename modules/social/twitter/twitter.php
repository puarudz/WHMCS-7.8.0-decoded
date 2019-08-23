<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function twitter_getTwitterIntents($username, $version)
{
    require_once ROOTDIR . "/modules/social/twitter/twitterIntents.php";
    $twitter = new twitterIntents($username, $version);
    $tweets = $twitter->getTweets();
    return $tweets;
}

?>