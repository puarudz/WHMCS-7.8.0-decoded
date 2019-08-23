
        </div>
        <div class="clear"></div>

    </div>

    <div class="clear"></div>

    <div class="footerbar clearfix">
        <div class="pull-left">
            <a href="#">
                <i class="fas fa-angle-double-up"></i>
                Top
            </a>
        </div>
        <div class="text-right whmcs-link">
            Copyright &copy; <a href="http://www.whmcs.com/" target="_blank">WHMCompleteSolution</a>.  All Rights Reserved.
        </div>
    </div>

    <div class="intelligent-search-container">
        <form method="post" action="{routePath('admin-search-intellisearch')}" id="frmIntelligentSearch">
            <input type="hidden" name="token" value="{$csrfToken}">
            <input id="intelliSearchMoreOf" type="hidden" name="more" value="">
            <input id="intelliSearchHideInactive" type="hidden" name="hide_inactive" value="1">
            <input type="text" name="searchterm" id="inputIntelliSearchValue" data-toggle="tooltip" data-placement="bottom" data-trigger="manual" data-title="You must enter at least 3 characters">
            <input type="submit" style="display:none;">
        </form>
    </div>

    <div id="intelligentSearchResults">
        <div class="search-header">
            {lang key="global.numSearchResultsFound" number='<span class="search-result-count">0</span>'}
            <div class="pull-right">
                <button type="button" class="close" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <div class="search-results">
            <h5>{lang key="clients.title"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="clients"></ul>
            <h5>{lang key="clientsummary.contacts"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="contacts"></ul>
            <h5>{lang key="services.title"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="services"></ul>
            <h5>{lang key="domains.title"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="domains"></ul>
            <h5>{lang key="invoices.title"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="invoices"></ul>
            <h5>{lang key="support.supporttickets"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="tickets"></ul>
            <h5>{lang key="search.otherResults"} (<span class="count"></span>)
                <i class="far fa-chevron-down"></i></h5>
            <ul class="others"></ul>
        </div>
        <div class="search-in-progress" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
            {lang key="search.performingSearch"}
        </div>
        <div class="search-no-results" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            {lang key="search.noResultsFound"}.<br>
            {lang key="search.tryAlternativeSearchTerm"}.
        </div>
        <div class="session-expired" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            {lang key="search.sessionExpired"}.<br>
            {lang key="search.refreshAndRetry"}.
        </div>
        <div class="search-warning" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="warning-msg"></span>
        </div>
        <div class="error" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            {lang key="global.erroroccurred"}.<br>
            {lang key="global.seeConsoleLog"}.
        </div>
        <div class="search-footer">
            <a href="#" class="collapse-all">{lang key="global.collapseAll"}</a>
            <a href="#" class="expand-all hidden">{lang key="global.expandAll"}</a>
            <span class="realtime"><input type="checkbox" id="intelliSearchRealtime" data-size="mini" data-label-text="{lang key="search.autoSearchOnType"}" data-on-color="info" data-url="{routePath('admin-search-intellisearch-settings-autosearch')}"{if $intelligentSearch.autoSearchEnabled} checked{/if}></span>
            <span class="hide-inactive"><input type="checkbox" id="intelliSearchHideInactiveSwitch" data-size="mini" data-label-text="{lang key="global.hideInactive"}" checked="checked"></span>
        </div>
        <div class="hidden">
            <a class="search-more-results" data-type="placeholder">
                <i class="fas fa-info-circle"></i>
                {lang key="search.showMoreResults"}.
            </a>
        </div>
    </div>

    <div id="greyout"></div>

<form id="frmmynotes">
    <input type="hidden" name="action" value="savenotes" />
    <input type="hidden" name="token" value="{$csrfToken}" />
    <div class="modal fade modal-my-notes" id="myNotes">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-warning">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{$_ADMINLANG.global.mynotes}</h4>
                </div>
                <div class="modal-body">
                    <textarea id="mynotesbox" name="notes" rows="12" class="form-control">{$admin_notes}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{$_ADMINLANG.global.cancel}</button>
                    <button type="button" class="btn btn-primary" onclick="notesclose('1')">{$_ADMINLANG.global.savechanges}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{if $clientLimitNotification}
    <div class="client-limit-notification client-limit-notification-form panel panel-{$clientLimitNotification.class}" id="clientLimitNotification">
        <div class="panel-heading">
            <button type="button" class="close" id="btnClientLimitNotificationDismiss"><span aria-hidden="true">&times;</span></button>
            <h3 class="panel-title">
                <i class="fas {$clientLimitNotification.icon}"></i>
                <span>{$clientLimitNotification.title}</span>
                <small>({$clientLimitNotification.numberOfActiveClients} / {$clientLimitNotification.clientLimit})</small>
            </h3>
        </div>
        <div class="panel-body">
            <p>{$clientLimitNotification.body}</p>
            <form method="post" action="{$clientLimitNotification.upgradeUrl}" target="_blank" data-fetch-url="{routePath('admin-help-license-upgrade-data')}">
                <input type="hidden" name="token" value="{$csrfToken}">
                <input type="hidden" name="getupgradedata" value="1">
                <input type="hidden" name="license_key" value="" class="input-license-key">
                <input type="hidden" name="member_data" value="" class="input-member-data">
                <div class="links">
                    <a href="#" id="btnClientLimitNotificationDontShowAgain" class="btn btn-xs btn-link pull-right">Don't show this again</a>
                    <button type="submit" class="btn btn-xs btn-{$clientLimitNotification.class}{if $clientLimitNotification.autoUpgradeEnabled} hidden{/if}" id="btnClientLimitNotificationUpgrade">Upgrade Now</button>
                    {if $clientLimitNotification.learnMoreUrl}
                        <a href="{$clientLimitNotification.learnMoreUrl}" class="btn btn-xs {if $clientLimitNotification.autoUpgradeEnabled}btn-{$clientLimitNotification.class}{else}btn-link{/if}" target="_blank">Learn more &raquo;</a>
                    {/if}
                </div>
            </form>
        </div>
    </div>
{/if}

<div class="modal whmcs-modal fade" id="modalAjax" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary">
            <div class="modal-header panel-heading" id="modalAjaxHeader">
                <button id="modalAjaxCloseSmall" type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title" id="modalAjaxTitle">Title</h4>
            </div>
            <div class="modal-body panel-body" id="modalAjaxBody">
                Loading...
            </div>
            <div class="modal-footer panel-footer" id="modalAjaxFooter">
                <div id="modalFooterLeft"></div>
                <div class="pull-left loader" id="modalAjaxLoader">
                    <i class="fas fa-circle-notch fa-spin"></i> Loading...
                </div>
                <button id="modalAjaxClose" type="button" class="btn btn-default" data-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary modal-submit" id="modalAjaxSubmit">
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>

{$footeroutput}

</body>
</html>
