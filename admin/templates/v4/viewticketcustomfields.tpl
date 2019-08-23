{if !$numcustomfields}
    <div align="center">{$_ADMINLANG.support.nocustomfields}</div>
{else}
    <form method="post" action="{$smarty.server.PHP_SELF}?action=viewticket&id={$ticketid}&sub=savecustomfields">
        {$csrfTokenHiddenInput}
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        {foreach from=$customfields item=customfield}
            <tr>
                <td width="25%" class="fieldlabel">{$customfield.name}</td>
                <td class="fieldarea">{$customfield.input}</td>
            </tr>
        {/foreach}
        </table>
        <div class="btn-container">
            <input type="submit" value="{$_ADMINLANG.global.savechanges}" class="btn btn-primary" />
            <input type="reset" value="{$_ADMINLANG.global.cancelchanges}" class="btn btn-default" />
        </div>
    </form>
{/if}
