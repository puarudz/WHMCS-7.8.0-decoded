<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("List Support Tickets");
$aInt->title = $aInt->lang("support", "insertkblink");
ob_start();
echo "\n<script language=\"JavaScript\">\nfunction insertKBLink(id, title) {\n    window.opener.insertKBLink(\n        '";
echo $CONFIG["SystemURL"];
echo "/knowledgebase.php?action=displayarticle&catid=";
echo $cat;
echo "&id='+id,\n        title\n    );\n    window.close();\n}\n</script>\n\n<p><b>Categories</b></p>\n";
if ($cat == "") {
    $cat = 0;
}
$result = select_query("tblknowledgebasecats", "", array("parentid" => $cat, "language" => ""), "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $name = $data["name"];
    $description = $data["description"];
    echo "<a href=\"supportticketskbarticle.php?cat=" . $id . "\"><b>" . $name . "</b></a> - " . $description . "<br>";
    $catDone = true;
}
if (!$catDone) {
    echo $aInt->lang("support", "nocatsfound") . "<br>";
}
echo "<p><b>Articles</b></p>\n";
$result = select_query("tblknowledgebase", "tblknowledgebase.*", array("categoryid" => $cat), "title", "ASC", "", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $title = $data["title"];
    $article = $data["article"];
    $views = $data["views"];
    $article = strip_tags($article);
    $article = trim($article);
    $article = substr($article, 0, 100) . "...";
    echo "<a href=\"#\" onClick=\"insertKBLink('" . $id . "', '" . WHMCS\Input\Sanitize::encode(addslashes(WHMCS\Input\Sanitize::decode($title))) . "');\"><b>" . $title . "</b></a><br>" . $article . "<br>";
    $articleDone = true;
}
if (!$articleDone) {
    echo $aInt->lang("support", "noarticlesfound") . "<br>";
}
echo "\n<p><a href=\"javascript:history.go(-1)\"><< ";
echo $aInt->lang("global", "back");
echo "</a></p>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->displayPopUp();

?>