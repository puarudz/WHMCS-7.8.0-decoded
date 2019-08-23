<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// Tags releases
if (php_sapi_name() != 'cli') {
    echo 'Release script cannot be called from web-browser.';
    exit;
}
require 'svn.php';
$svn_info = my_svn_info('.');
$version = trim(file_get_contents('VERSION'));
$trunk_url = $svn_info['Repository Root'] . '/htmlpurifier/trunk';
$trunk_tag_url = $svn_info['Repository Root'] . '/htmlpurifier/tags/' . $version;
echo "Tagging trunk to tags/{$version}...";
passthru("svn copy --message \"Tag {$version} release.\" {$trunk_url} {$trunk_tag_url}");
// vim: et sw=4 sts=4

?>