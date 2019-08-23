<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Spam Control");
$aInt->title = $aInt->lang("stspamcontrol", "stspamcontroltitle");
$aInt->sidebar = "config";
$aInt->icon = "spamcontrol";
$aInt->helplink = "Email Piping Spam Control";
$action = $whmcs->get_req_var("action");
if ($action == "add") {
    check_token("WHMCS.admin.default");
    $type = $whmcs->get_req_var("type");
    $spamvalue = $whmcs->get_req_var("spamvalue");
    logAdminActivity("Spam Control Record Created: Type: '" . ucfirst($type) . "' - Content: '" . $spamvalue . "'");
    insert_query("tblticketspamfilters", array("type" => $type, "content" => $spamvalue));
    redir("added=1");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $id = (int) $whmcs->get_req_var("id");
    $spamFilter = Illuminate\Database\Capsule\Manager::table("tblticketspamfilters")->find($id);
    logAdminActivity("Spam Control Record Deleted: Type: '" . ucfirst($spamFilter->type) . "' - Content: '" . $spamFilter->content . "'");
    delete_query("tblticketspamfilters", array("id" => $id));
    redir("deleted=1");
}
ob_start();
$jscode = "function doDelete(id,num) {\nif (confirm(\"" . $aInt->lang("stspamcontrol", "delsurespamcontrol", 1) . "\")) {\nwindow.location='" . $_SERVER["PHP_SELF"] . "?action=delete&id='+id+'&tabnum='+num+'" . generate_token("link") . "';\n}}";
if ($added) {
    infoBox($aInt->lang("stspamcontrol", "spamcontrolupdatedtitle"), $aInt->lang("stspamcontrol", "spamcontrolupdatedadded"));
}
if ($deleted) {
    infoBox($aInt->lang("stspamcontrol", "spamcontrolupdatedtitle"), $aInt->lang("stspamcontrol", "spamcontrolupdateddel"));
}
echo $infobox;
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=add\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\"><b>";
echo $aInt->lang("global", "add");
echo ":</b> ";
echo $aInt->lang("stspamcontrol", "typeval");
echo "</td><td class=\"fieldarea\"><div class=\"col-sm-3\"><select name=\"type\" class=\"form-control\"><option value=\"sender\">";
echo $aInt->lang("stspamcontrol", "sender");
echo "</option><option value=\"subject\">";
echo $aInt->lang("stspamcontrol", "subject");
echo "</option><option value=\"phrase\">";
echo $aInt->lang("stspamcontrol", "phrase");
echo "</option></select></div><div class=\"col-sm-5\"><input type=\"text\" name=\"spamvalue\" size=\"50\" class=\"form-control\" /></div><div class=\"col-sm-2\"><input type=\"submit\" value=\"";
echo $aInt->lang("stspamcontrol", "addnewsc");
echo "\" class=\"btn btn-primary\" /></div></td></tr>\n</table>\n</form>\n\n<br>\n\n";
echo $aInt->beginAdminTabs(array($aInt->lang("stspamcontrol", "tab1"), $aInt->lang("stspamcontrol", "tab2"), $aInt->lang("stspamcontrol", "tab3")), true);
$nums = array("0", "1", "2");
foreach ($nums as $num) {
    if ($num == 0) {
        $filtertype = "sender";
    } else {
        if ($num == 1) {
            $filtertype = "subject";
        } else {
            if ($num == 2) {
                $filtertype = "phrase";
            }
        }
    }
    $result = select_query("tblticketspamfilters", "COUNT(*)", array("type" => $filtertype));
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
    $aInt->sortableTableInit("id", "ASC");
    $tabledata = array();
    $result = select_query("tblticketspamfilters", "", array("type" => $filtertype), "content", "ASC", $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $content = $data["content"];
        $tabledata[] = array($content, "<a href=\"#\" onClick=\"doDelete('" . $id . "','" . $num . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "content"), ""), $tabledata);
    echo $aInt->nextAdminTab();
}
echo $aInt->endAdminTabs();
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>