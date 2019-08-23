<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Affiliate Cookie Tracking + Redirection Handler
 *
 * @package    WHMCS
 * @author     WHMCS Limited <development@whmcs.com>
 * @copyright  Copyright (c) WHMCS Limited 2005-2018
 * @license    https://www.whmcs.com/license/ WHMCS Eula
 * @version    $Id$
 * @link       https://www.whmcs.com/
 */
use WHMCS\Affiliate\Referrer;
use WHMCS\Carbon;
use WHMCS\Cookie;
define("CLIENTAREA", true);
require "init.php";
// if affiliate id is present, update visitor count & set cookie
if ($aff = $whmcs->get_req_var('aff')) {
    update_query("tblaffiliates", array("visitors" => "+1"), array("id" => $aff));
    Cookie::set('AffiliateID', $aff, '3m');
    $referrer = trim($_SERVER['HTTP_REFERER']);
    Referrer::firstOrCreate(['affiliate_id' => $aff, 'referrer' => $referrer])->hits()->create(['affiliate_id' => $aff, 'created_at' => Carbon::now()->toDateTimeString()]);
}
/**
 * Executes when a user has clicked an affiliate referral link.
 *
 * @param int $affiliateId The unique id of the affiliate that the link belongs to
 */
run_hook("AffiliateClickthru", array('affiliateId' => $aff));
// if product id passed in, redirect to order form
if ($pid = $whmcs->get_req_var('pid')) {
    redir("a=add&pid=" . (int) $pid, "cart.php");
}
// if product group id passed in, redirect to product group
if ($gid = $whmcs->get_req_var('gid')) {
    redir("gid=" . (int) $gid, "cart.php");
}
// if register = true, redirect to registration form
if ($whmcs->get_req_var('register')) {
    redir("", "register.php");
}
// if gocart = true, redirect to cart with request params
if ($whmcs->get_req_var('gocart')) {
    $reqvars = '';
    foreach ($_GET as $k => $v) {
        $reqvars .= $k . '=' . urlencode($v) . '&';
    }
    redir($reqvars, "cart.php");
}
// perform redirect
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $whmcs->get_config('Domain'), true, 301);

?>