{if file_exists("$template/includes/pageheader.tpl")}
    {include file="$template/includes/pageheader.tpl" title=$LANG.xxxmembershipidupdate}
{elseif file_exists("$template/pageheader.tpl")}
    {include file="$template/pageheader.tpl" title=$LANG.xxxmembershipidupdate}
{/if}
{if $success}<div class="alert-message success">
    <p>{$LANG.changessavedsuccessfully}</p>
</div>{/if}
{if $error}<div class="alert-message error">
    <p>{$error}</p>
</div>{/if}

<form method="post" action="{$smarty.server.PHP_SELF}?action=domaindetails">
    <fieldset class="onecol">

        <div class="clearfix">
            <label for="membershipid">{$LANG.xxxmemberid}</label>
            <div class="input">
                <input type="text" name="membershipid" id="membershipid" value="{$membershipid}" />
            </div>
        </div>
        <input type="hidden" name="id" value="{$domainid}" />
        <input type="hidden" name="modop" value="custom" />
        <input type="hidden" name="a" value="UpdateXXX" />
        </fieldset>

        <div class="actions">
            <input class="btn primary" type="submit" name="submit" value="{$LANG.clientareasavechanges}" />
            <input class="btn" type="button" value="{$LANG.cancel}" onclick="window.location='clientarea.php?action=domaindetails&id={$domainid}'" />
        </div>
</form>
