<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="pull-md-right col-md-9">

        <div class="header-lined">
            <h1>{$groupname}</h1>
        </div>

    </div>

    <div class="col-md-3 pull-md-left sidebar hidden-xs hidden-sm">

        {include file="orderforms/$carttpl/sidebar-categories.tpl"}

    </div>

    <div class="col-md-9 pull-md-right">

        <div class="line-padded visible-xs visible-sm clearfix">

            {include file="orderforms/$carttpl/sidebar-categories-collapsed.tpl"}

        </div>

        <form method="post" action="{$smarty.server.PHP_SELF}?a=add">

            <div class="fields-container">
                {foreach from=$products item=product}
                    <div class="field-row clearfix">
                        <div class="col-xs-12">
                            <label class="radio-inline product-radio"><input type="radio" name="pid" id="pid{$product.pid}" value="{if $product.bid}b{$product.bid}{else}{$product.pid}{/if}"{if $product.qty eq "0"} disabled{/if} /> <strong>{$product.name}</strong> {if $product.qty!=""}<em>({$product.qty} {$LANG.orderavailable})</em>{/if}{if $product.description} - {$product.description}{/if}</label>
                        </div>
                    </div>
            {/foreach}
            </div>

            <div class="line-padded text-center">
                <button type="submit" class="btn btn-primary btn-lg">{$LANG.continue} &nbsp;<i class="fas fa-arrow-circle-right"></i></button>
            </div>

        </form>

    </div>

    <div class="clearfix"></div>

    <div class="secure-warning">
        <img src="assets/img/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp;{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>

</div>
