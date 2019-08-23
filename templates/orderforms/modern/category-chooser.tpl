<div class="choosecat btn-group">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        {$LANG.cartchooseanothercategory} <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu">
        {foreach key=num item=productgroup from=$productgroups}
            <li><a href="cart.php?gid={$productgroup.gid}">{$productgroup.name}</a></li>
        {/foreach}
        {if $loggedin}
            <li><a href="cart.php?gid=addons">{$LANG.cartproductaddons}</a></li>
            {if $renewalsenabled}
                <li><a href="cart.php?gid=renewals">{$LANG.domainrenewals}</a></li>
            {/if}
        {/if}
        {if $registerdomainenabled}
            <li><a href="cart.php?a=add&domain=register">{$LANG.registerdomain}</a></li>
        {/if}
        {if $transferdomainenabled}
            <li><a href="cart.php?a=add&domain=transfer">{$LANG.transferdomain}</a></li>
        {/if}
        <li><a href="cart.php?a=view">{$LANG.viewcart}</a></li>
    </ul>
</div>
