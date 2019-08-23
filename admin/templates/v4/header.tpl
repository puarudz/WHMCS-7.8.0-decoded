<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<title>WHMCS - {$pagetitle}</title>
<link href="templates/{$template}/css/all.min.css?v={$versionHash}" rel="stylesheet" type="text/css" />
<link href="{\WHMCS\Utility\Environment\WebHelper::getBaseUrl()}/assets/css/fontawesome-all.min.css" rel="stylesheet" />
<script type="text/javascript" src="templates/{$template}/js/scripts.min.js?v={$versionHash}"></script>
<script type="text/javascript">
var datepickerformat = "{$datepickerformat}",
    csrfToken = "{$csrfToken}",
    adminBaseRoutePath = "{\WHMCS\Admin\AdminServiceProvider::getAdminRouteBase()}",
    whmcsBaseUrl = "{\WHMCS\Utility\Environment\WebHelper::getBaseUrl()}";
{if $jquerycode}$(document).ready(function(){ldelim}
    {$jquerycode}
{rdelim});
{/if}
{if $jscode}{$jscode}
{/if}

</script>
{$headoutput}
</head>
<body data-phone-cc-input="{$phoneNumberInputStyle}">

{$headeroutput}

  <div id="headerWrapper" align="center">
    <div id="bodyContentWrapper" align="left">
      <div id="mynotes"><form id="frmmynotes"><input type="hidden" name="action" value="savenotes" /><input type="hidden" name="token" value="{$csrfToken}" /><textarea id="mynotesbox" name="notes" rows="15" cols="80">{$admin_notes}</textarea><br /><input type="button" value="Save" id="savenotes" /></form></div>
      <div id="topnav">
        <div id="welcome">{$_ADMINLANG.global.welcomeback} <strong>{$admin_username}</strong>&nbsp;&nbsp;- <a href="../" title="Client Area">{$_ADMINLANG.global.clientarea}</a> | <a href="#" id="shownotes" title="My Notes">{$_ADMINLANG.global.mynotes}</a> | <a href="myaccount.php" title="My Account">{$_ADMINLANG.global.myaccount}</a> | <a href="logout.php" title="Logout">{$_ADMINLANG.global.logout}</a>{$topBarNotification}</div>
        <div id="date">{$carbon->translateTimestampToFormat($smarty.now, "l | j F Y | h:i A")}</div>
        <div class="clear"></div>
      </div>
      <div id="intellisearch">
        <form id="frmintellisearch">
          <input type="hidden" name="intellisearch" value="1" />
          <input type="hidden" name="token" value="{$csrfToken}" />
          <div class="input-group input-group-sm">
            <input type="text" name="value" id="intellisearchval" class="form-control"  placeholder="{$_ADMINLANG.global.intellisearch}..." >
            <span class="input-group-addon">
              <button type="submit" id="btnIntelliSearch">
                <span class="fas fa-search"></span>
              </button>
              <button class="hidden" id="btnIntelliSearchCancel" onclick="intellisearchcancel()">
                <span class="fas fa-times"></span>
              </button>
            </span>
          </div>
          <div align="left" id="searchresults" class="hidden"></div>
        </form>
      </div>
      <a title="WHMCS Home" href="./" id="logo"></a>
      <div class="navigation">
        <ul id="menu">
          {include file="v4/menu.tpl"}
        </ul>
      </div>
    </div>
  </div>
  <div class="alert alert-warning global-admin-warning{if !$globalAdminWarningMsg} hidden{/if}">
    {$globalAdminWarningMsg}
  </div>
  <div id="content_container">

    <div class="col-md-10 col-md-push-2 col-sm-9 col-sm-push-3">

      <div id="content">
        {if $helplink}<div class="contexthelp"><a href="http://docs.whmcs.com/{$helplink}" target="_blank"><img src="images/icons/help.png" border="0" align="absmiddle" /> {$_ADMINLANG.help.contextlink}</a></div>{/if}
        <h1>{$pagetitle}</h1>
        <div id="content_padded">
