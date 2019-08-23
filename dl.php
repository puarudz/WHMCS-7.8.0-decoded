<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$type = $whmcs->get_req_var("type");
$viewpdf = $whmcs->get_req_var("viewpdf");
$i = (int) $whmcs->get_req_var("i");
$id = (int) $whmcs->get_req_var("id");
$storage = NULL;
$allowedtodownload = "";
$file_name = $display_name = "";
$allowedtodownload = "";
if ($type == "i") {
    $result = select_query("tblinvoices", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $invoiceid = $data["id"];
    $invoicenum = $data["invoicenum"];
    $userid = $data["userid"];
    $status = $data["status"];
    if (!$invoiceid) {
        redir("", "clientarea.php");
    }
    require "includes/adminfunctions.php";
    if ($_SESSION["adminid"]) {
        if (!checkPermission("Manage Invoice", true)) {
            exit("You do not have the necessary permissions to download PDF invoices. If you feel this message to be an error, please contact the system administrator.");
        }
    } else {
        if ($_SESSION["uid"] == $userid) {
            if ($status == "Draft") {
                redir("", "clientarea.php");
            }
        } else {
            downloadLogin();
        }
    }
    if (!$invoicenum) {
        $invoicenum = $invoiceid;
    }
    require "includes/invoicefunctions.php";
    $pdfdata = pdfInvoice($id);
    $filenameSuffix = preg_replace("|[\\\\/]+|", "-", $invoicenum);
    header("Pragma: public");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
    header("Cache-Control: private", false);
    header("Content-Type: application/pdf");
    header("Content-Disposition: " . ($viewpdf ? "inline" : "attachment") . "; filename=\"" . $_LANG["invoicefilename"] . $filenameSuffix . ".pdf\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . strlen($pdfdata));
    echo $pdfdata;
    exit;
}
if ($type == "a" || $type == "ar" || $type == "an") {
    $useridOfMasterTicket = $useridOfReply = 0;
    $adminOnly = false;
    $ticketid = "";
    switch ($type) {
        case "an":
            $noteData = WHMCS\Database\Capsule::table("tblticketnotes")->find($id, array("ticketid", "attachments"));
            if ($noteData) {
                $attachments = $noteData->attachments;
                $ticketid = $noteData->ticketid;
                $adminOnly = true;
            }
            break;
        case "ar":
            $replyData = WHMCS\Database\Capsule::table("tblticketreplies")->find($id, array("tid", "userid", "attachment"));
            if ($replyData) {
                $attachments = $replyData->attachment;
                $ticketid = $replyData->tid;
                $useridOfReply = $replyData->userid;
                $useridOfMasterTicket = get_query_val("tbltickets", "userid", array("id" => $ticketid));
            }
            break;
        default:
            $ticketData = WHMCS\Database\Capsule::table("tbltickets")->find($id, array("id", "userid", "attachment"));
            if ($ticketData) {
                $attachments = $ticketData->attachment;
                $ticketid = $ticketData->id;
                $useridOfMasterTicket = $ticketData->userid;
            }
    }
    if (!$ticketid) {
        exit("Ticket ID Not Found");
    }
    if (WHMCS\Session::get("adminid")) {
        require_once ROOTDIR . "/includes/adminfunctions.php";
        if (!checkPermission("View Support Ticket", true)) {
            exit("You do not have the necessary permissions to View Support Tickets. " . "If you feel this message to be an error, please contact the system administrator.");
        }
        require_once ROOTDIR . "/includes/ticketfunctions.php";
        $access = validateAdminTicketAccess($ticketid);
        if ($access) {
            exit("Access Denied. You do not have the required permissions to view this ticket.");
        }
    } else {
        if (!$adminOnly) {
            if ($useridOfMasterTicket) {
                if ($useridOfMasterTicket != WHMCS\Session::get("uid")) {
                    downloadLogin();
                    exit;
                }
            } else {
                if ($useridOfReply) {
                    if ($useridOfReply != WHMCS\Session::get("uid")) {
                        downloadLogin();
                        exit;
                    }
                } else {
                    $AccessedTicketIDs = WHMCS\Session::get("AccessedTicketIDs");
                    $AccessedTicketIDsArray = explode(",", $AccessedTicketIDs);
                    if (!in_array($ticketid, $AccessedTicketIDsArray)) {
                        exit("Ticket Attachments cannot be accessed directly. " . "Please try again using the download link provided within the ticket. " . "If you are registered and have an account with us, you can access your tickets " . "from our client area. Otherwise, please use the link to view the ticket which you " . "should have received via email when the ticket was originally opened or last responded to.");
                    }
                }
            }
        }
    }
    $storage = Storage::ticketAttachments();
    $files = explode("|", $attachments);
    $file_name = $files[$i];
    $display_name = substr($file_name, 7);
} else {
    if ($type == "d") {
        $data = get_query_vals("tbldownloads", "id,location,clientsonly,productdownload", array("id" => $id));
        $downloadID = $data["id"];
        $filename = $data["location"];
        $clientsonly = $data["clientsonly"];
        $productdownload = $data["productdownload"];
        if (!$downloadID) {
            exit("Invalid Download Requested");
        }
        $userID = (int) WHMCS\Session::get("uid");
        if (!$userID && ($clientsonly || $productdownload)) {
            downloadLogin();
        }
        if ($productdownload) {
            $serviceID = (int) $whmcs->get_req_var("serviceid");
            if ($serviceID) {
                $servicesWhere = array("tblhosting.id" => $serviceID, "userid" => $userID, "tblhosting.domainstatus" => "Active");
                $addonsWhere = array("tblhostingaddons.hostingid" => $serviceID, "tblhosting.userid" => $userID, "tblhostingaddons.status" => "Active");
            } else {
                $servicesWhere = array("userid" => $userID, "tblhosting.domainstatus" => "Active");
                $addonsWhere = array("tblhosting.userid" => $userID, "tblhostingaddons.status" => "Active");
            }
            $allowAccess = false;
            $supportAndUpdatesAddons = array();
            $result = select_query("tblhosting", "tblhosting.id,tblproducts.id AS productid,tblproducts.servertype,tblproducts.configoption7", $servicesWhere, "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
            while ($data = mysql_fetch_array($result)) {
                $productServiceID = $data["id"];
                $productModule = $data["servertype"];
                $supportAndUpdatesAddon = $data["configoption7"];
                $productDownloadsArray = WHMCS\Product\Product::find($data["productid"])->getDownloadIds();
                if (is_array($productDownloadsArray) && in_array($downloadID, $productDownloadsArray)) {
                    if ($productModule == "licensing" && $supportAndUpdatesAddon && $supportAndUpdatesAddon != "0|None") {
                        $parts = explode("|", $supportAndUpdatesAddon);
                        $requiredAddonID = (int) $parts[0];
                        if ($requiredAddonID) {
                            $supportAndUpdatesAddons[$productServiceID] = $requiredAddonID;
                        }
                    } else {
                        $allowAccess = true;
                    }
                }
            }
            if (!$allowAccess) {
                $result = select_query("tblhostingaddons", "DISTINCT tbladdons.id,tbladdons.downloads", $addonsWhere, "", "", "", "tbladdons ON tbladdons.id=tblhostingaddons.addonid INNER JOIN tblhosting ON tblhosting.id=tblhostingaddons.hostingid");
                while ($data = mysql_fetch_array($result)) {
                    $addondownloads = $data["downloads"];
                    $addondownloads = explode(",", $addondownloads);
                    if (in_array($downloadID, $addondownloads)) {
                        $allowAccess = true;
                    }
                }
            }
            if (!$allowAccess && count($supportAndUpdatesAddons)) {
                foreach ($supportAndUpdatesAddons as $productServiceID => $requiredAddonID) {
                    $requiredAddonName = get_query_val("tbladdons", "name", array("id" => $requiredAddonID));
                    $where = "tblhosting.userid='" . $userID . "' AND tblhostingaddons.status='Active' AND (tblhostingaddons.name='" . db_escape_string($requiredAddonName) . "' OR tblhostingaddons.addonid='" . $requiredAddonID . "')";
                    if ($serviceID) {
                        $where .= " AND tblhosting.id='" . $serviceID . "'";
                    }
                    $addonCount = get_query_val("tblhostingaddons", "COUNT(tblhostingaddons.id)", $where, "", "", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid");
                    if ($addonCount) {
                        $allowAccess = true;
                    }
                }
                if (!$allowAccess) {
                    if ($serviceID) {
                        $productServiceID = $serviceID;
                        $requiredAddonID = $supportAndUpdatesAddons[$serviceID];
                    }
                    $pagetitle = $_LANG["downloadstitle"];
                    $breadcrumbnav = "<a href=\"" . $CONFIG["SystemURL"] . "/index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . routePath("download-index") . "\">" . $_LANG["downloadstitle"] . "</a>";
                    $pageicon = "";
                    $displayTitle = Lang::trans("supportAndUpdatesExpired");
                    $tagline = "";
                    initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
                    $smartyvalues["reason"] = "supportandupdates";
                    $smartyvalues["serviceid"] = $productServiceID;
                    $smartyvalues["licensekey"] = get_query_val("tblhosting", "domain", array("id" => $productServiceID));
                    $smartyvalues["addonid"] = $requiredAddonID;
                    Menu::addContext("topFiveDownloads", WHMCS\Download\Download::topDownloads()->get());
                    Menu::primarySidebar("downloadList");
                    Menu::secondarySidebar("downloadList");
                    outputClientArea("downloaddenied");
                    exit;
                }
            }
            if (!$allowAccess) {
                $pagetitle = $_LANG["downloadstitle"];
                $breadcrumbnav = "<a href=\"" . $CONFIG["SystemURL"] . "/index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . routePath("download-index") . "\">" . $_LANG["downloadstitle"] . "</a>";
                $pageicon = "";
                $displayTitle = Lang::trans("accessdenied");
                $tagline = "";
                initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
                if ($serviceID) {
                    $productsWithMatchingDownload = WHMCS\Product\Product::whereHas("productDownloads", function ($query) use($downloadID) {
                        $download = new WHMCS\Download\Download();
                        $query->where($download->getTable() . ".id", $downloadID);
                    })->whereHas("services", function ($query) use($serviceID) {
                        $service = new WHMCS\Service\Service();
                        $query->where($service->getTable() . ".id", $serviceID);
                    })->get();
                } else {
                    $productsWithMatchingDownload = WHMCS\Product\Product::whereHas("productDownloads", function ($query) use($downloadID) {
                        $download = new WHMCS\Download\Download();
                        $query->where($download->getTable() . ".id", $downloadID);
                    })->orderBy("hidden")->orderBy("order")->get();
                }
                $smartyvalues["pid"] = "";
                $smartyvalues["prodname"] = "";
                if (!$productsWithMatchingDownload->isEmpty()) {
                    $smartyvalues["pid"] = $productsWithMatchingDownload->first()->id;
                    $smartyvalues["prodname"] = $productsWithMatchingDownload->first()->name;
                }
                $smartyvalues["aid"] = "";
                $smartyvalues["addonname"] = "";
                $result = select_query("tbladdons", "id,name,downloads", array("downloads" => array("sqltype" => "NEQ", "value" => "")));
                while ($data = mysql_fetch_array($result)) {
                    $downloads = $data["downloads"];
                    $downloads = explode(",", $downloads);
                    if (in_array($downloadID, $downloads)) {
                        $smartyvalues["aid"] = $data["id"];
                        $smartyvalues["addonname"] = $data["name"];
                        break;
                    }
                }
                if (!$smartyvalues["prodname"] && !$smartyvalues["addonname"]) {
                    $smartyvalues["prodname"] = "Unable to Determine Required Product. Please contact support.";
                }
                $smartyvalues["reason"] = "accessdenied";
                Menu::addContext("topFiveDownloads", WHMCS\Download\Download::topDownloads()->get());
                Menu::primarySidebar("downloadList");
                Menu::secondarySidebar("downloadList");
                outputClientArea("downloaddenied");
                exit;
            }
        }
        update_query("tbldownloads", array("downloads" => "+1"), array("id" => $id));
        $storage = Storage::downloads();
        $file_name = $filename;
        $display_name = $filename;
    } else {
        if ($type == "f") {
            $result = select_query("tblclientsfiles", "userid,filename,adminonly", array("id" => $id));
            $data = mysql_fetch_array($result);
            $userid = $data["userid"];
            $file_name = $data["filename"];
            $adminonly = $data["adminonly"];
            $display_name = substr($file_name, 11);
            $storage = Storage::clientFiles();
            if ($userid != $_SESSION["uid"] && !$_SESSION["adminid"]) {
                downloadLogin();
            }
            if (!$_SESSION["adminid"] && $adminonly) {
                exit("Permission Denied");
            }
        } else {
            if ($type == "q") {
                if (!$_SESSION["uid"] && !$_SESSION["adminid"]) {
                    downloadLogin();
                }
                $result = select_query("tblquotes", "id,userid", array("id" => $id));
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                $userid = $data["userid"];
                if ($userid != $_SESSION["uid"] && !$_SESSION["adminid"]) {
                    exit("Permission Denied");
                }
                require ROOTDIR . "/includes/clientfunctions.php";
                require ROOTDIR . "/includes/invoicefunctions.php";
                require ROOTDIR . "/includes/quotefunctions.php";
                $pdfdata = genQuotePDF($id);
                header("Pragma: public");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
                header("Cache-Control: private", false);
                header("Content-Type: application/pdf");
                header("Content-Disposition: " . ($viewpdf ? "inline" : "attachment") . "; filename=\"" . $_LANG["quotefilename"] . $id . ".pdf\"");
                header("Content-Transfer-Encoding: binary");
                echo $pdfdata;
                exit;
            }
        }
    }
}
if (is_null($storage) || !trim($file_name)) {
    redir("", "index.php");
}
try {
    $fileSize = $storage->getSizeStrict($file_name);
} catch (Exception $e) {
    if (WHMCS\Admin::getID()) {
        $extraMessage = "This could indicate that the file is missing or that <a href=\"" . routePath("admin-setup-storage-index") . "\" target=\"_blank\">storage configuration settings" . "</a> are misconfigured. " . "<a href=\"https://docs.whmcs.com/Storage_Settings#Troubleshooting_a_File_Not_Found_Error\" target=\"_blank\">" . "Learn more</a>";
    } else {
        $extraMessage = "Please contact support.";
    }
    throw new WHMCS\Exception\Fatal("File not found. " . $extraMessage);
}
run_hook("FileDownload", array());
header("Pragma: public");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . $display_name . "\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . $fileSize);
$stream = $storage->readStream($file_name);
echo stream_get_contents($stream);
fclose($stream);
function downloadLogin()
{
    global $smartyvalues;
    $whmcs = App::self();
    $pageTitle = Lang::trans("downloadstitle");
    $tagline = Lang::trans("downloadLoginRequiredTagline");
    $breadCrumb = "<a href=\"" . $whmcs->getSystemURL() . "\">" . Lang::trans("globalsystemname") . "</a>" . " > " . "<a href=\"" . routePath("download-index") . "\">" . Lang::trans("downloadstitle") . "</a>";
    initialiseClientArea($pageTitle, $pageTitle, $tagline, "", $breadCrumb);
    require "login.php";
}

?>