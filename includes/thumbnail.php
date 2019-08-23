<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../init.php";
$tid = (int) App::get_req_var("tid");
$rid = (int) App::get_req_var("rid");
$nid = (int) App::get_req_var("nid");
$attachments = "";
if ($tid) {
    $data = get_query_vals("tbltickets", "userid,attachment", array("id" => $tid));
    list($userid, $attachments) = $data;
}
if ($rid) {
    $data = get_query_vals("tblticketreplies", "tid,attachment", array("id" => $rid));
    list($ticketid, $attachments) = $data;
    $userid = get_query_val("tbltickets", "userid", array("id" => $ticketid));
}
if ($nid) {
    $data = get_query_vals("tblticketnotes", "ticketid,attachments", array("id" => $nid));
    $ticketid = $data["ticketid"];
    $attachments = $data["attachments"];
    $userid = get_query_val("tbltickets", "userid", array("id" => $ticketid));
}
$attachments = explode("|", $attachments);
$filename = isset($attachments[$i]) ? $attachments[$i] : NULL;
try {
    if (!function_exists("imagecreatefromstring")) {
        logActivity("Unable to generate image thumbnail: GD library is required but appears to be missing from PHP build");
        throw new WHMCS\Exception();
    }
    if (!empty($_SESSION["adminid"]) || !empty($_SESSION["uid"]) && $_SESSION["uid"] == $userid) {
        if (!trim($filename)) {
            throw new WHMCS\Exception();
        }
        $storage = Storage::ticketAttachments();
        if (!$storage->has($filename)) {
            throw new WHMCS\Exception();
        }
        $img = imagecreatefromstring($storage->read($filename));
        if (!$img) {
            throw new WHMCS\Exception();
        }
        $thumbWidth = 200;
        $thumbHeight = 125;
        $width = imagesx($img);
        $height = imagesy($img);
        $new_height = $thumbHeight;
        $new_width = floor($width * $thumbHeight / $height);
        if ($new_width < 200) {
            $new_width = 200;
            $new_height = floor($height * $thumbWidth / $width);
        } else {
            if (500 < $new_width) {
                $new_width = 500;
                $new_height = floor($height * $thumbWidth / $width);
            }
        }
        $tmp_img = imagecreatetruecolor($new_width, $new_height);
        imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Content-Type: image/png");
        imagepng($tmp_img);
        imagedestroy($tmp_img);
    } else {
        throw new WHMCS\Exception("Access denied");
    }
} catch (Exception $e) {
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Content-Type: image/gif");
    $filename = DI::make("asset")->getFilesystemImgPath() . "/nothumbnail.gif";
    echo file_get_contents($filename);
}

?>