<div class="pull-left form-inline">
    <form method="get" action="{$smarty.server.PHP_SELF}">
        {$LANG.ordercategories}:
        <select name="gid" onchange="submit()" class="form-control">
        {foreach key=num item=productgroup from=$productgroups}
            <option value="{$productgroup.gid}"{if $gid eq $productgroup.gid} selected="selected"{/if}>{$productgroup.name}</option>
        {/foreach}
        {if $loggedin}
            <option value="addons"{if $gid eq "addons"} selected{/if}>{$LANG.cartproductaddons}</option>
            {if $renewalsenabled}
                <option value="renewals"{if $gid eq "renewals"} selected{/if}>{$LANG.domainrenewals}</option>
            {/if}
        {/if}
        {if $registerdomainenabled}
            <option value="domains"{if $domain eq "register"} selected{/if}>{$LANG.navregisterdomain}</option>
        {/if}
        {if $transferdomainenabled}
            <option value="domains"{if $domain eq "transfer"} selected{/if}>{$LANG.transferinadomain}</option>
        {/if}
        </select>
    </form>
</div>

{if !$loggedin && $currencies}
    <div class="pull-right form-inline">
        <form method="post" action="cart.php?gid={$smarty.get.gid}">
            {$LANG.choosecurrency}:
            <select name="currency" onchange="submit()" class="form-control">
                {foreach from=$currencies item=curr}
                    <option value="{$curr.id}"{if $curr.id eq $currency.id} selected{/if}>{$curr.code}</option>
                {/foreach}
            </select>
        </form>
    </div>
{/if}
