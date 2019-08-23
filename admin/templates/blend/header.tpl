<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="{$charset}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>WHMCS - {$pagetitle}</title>

    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600" rel="stylesheet">
    <link href="templates/{$template}/css/all.min.css?v={$versionHash}" rel="stylesheet" />
    <link href="{\WHMCS\Utility\Environment\WebHelper::getBaseUrl()}/assets/css/fontawesome-all.min.css" rel="stylesheet" />
    <script type="text/javascript" src="templates/{$template}/js/scripts.min.js?v={$versionHash}"></script>
    <script>
        var datepickerformat = "{$datepickerformat}",
            csrfToken="{$csrfToken}",
            adminBaseRoutePath = "{\WHMCS\Admin\AdminServiceProvider::getAdminRouteBase()}",
            whmcsBaseUrl = "{\WHMCS\Utility\Environment\WebHelper::getBaseUrl()}";

        {if $jquerycode}
            $(document).ready(function(){ldelim}
                {$jquerycode}
            {rdelim});
        {/if}
        {if $jscode}
            {$jscode}
        {/if}
    </script>

    {$headoutput}

</head>
<body data-phone-cc-input="{$phoneNumberInputStyle}">

    {$headeroutput}

    <div class="topbar">
        <div class="pull-left">
            <a href="index.php">{$_ADMINLANG.home.title}</a> |
            <a href="../">{$_ADMINLANG.global.clientarea}</a> |
            <a href="#" data-toggle="modal" data-target="#myNotes">{$_ADMINLANG.global.mynotes}</a> |
            <a href="myaccount.php">{$_ADMINLANG.global.myaccount}</a> |
            <a id="logout" href="logout.php">{$_ADMINLANG.global.logout}</a>
            {$topBarNotification}
        </div>
        <div class="pull-right date hidden-xs">
            {$carbon->translateTimestampToFormat($smarty.now, "l, j F Y, H:i")}
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="header">
        <div class="logo">
            <a href="index.php"><img src="templates/{$template}/images/logo.gif" border="0" /></a>
        </div>
        <div class="stats">
            <a href="orders.php?status=Pending">
                <span class="stat">{$sidebarstats.orders.pending}</span>
                {$_ADMINLANG.stats.pendingorders}
            </a> |
            <a href="invoices.php?status=Overdue">
                <span class="stat">{$sidebarstats.invoices.overdue}</span>
                {$_ADMINLANG.stats.overdueinvoices}
            </a> |
            <a href="supporttickets.php">
                <span class="stat">{$sidebarstats.tickets.awaitingreply}</span>
                {$_ADMINLANG.stats.ticketsawaitingreply}
            </a>
        </div>
    </div>

    {include file="$template/menu.tpl"}

    <div class="alert alert-warning global-admin-warning{if !$globalAdminWarningMsg} hidden{/if}">
        {$globalAdminWarningMsg}
    </div>

    <div id="sidebaropen"{if !$minsidebar} style="display:none;"{/if}>
        <a href="#" onclick="sidebarOpen();return false">
            <img src="templates/{$template}/images/opensidebar.png" border="0" />
        </a>
    </div>
    <div id="sidebar"{if $minsidebar} style="display:none;"{/if}>
        {include file="$template/sidebar.tpl"}
    </div>

    <div class="contentarea" id="contentarea"{if !$minsidebar} style="margin-left:209px;"{/if}>

        <div style="float:left;width:100%;">

            {if $helplink}
                <div class="contexthelp">
                    <a href="http://docs.whmcs.com/{$helplink}" target="_blank">
                        <img src="images/icons/help.png" border="0" align="absmiddle" />
                        {$_ADMINLANG.help.contextlink}
                    </a>
                </div>
            {/if}

            <h1{if $pagetitle == $_ADMINLANG.global.hometitle} class="pull-left"{/if}>{$pagetitle}</h1>
