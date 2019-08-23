<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="pull-md-right col-md-9">

        <div class="header-lined">
            <h1>{$LANG.cartproductaddons}</h1>
        </div>

    </div>

    <div class="col-md-3 pull-md-left sidebar hidden-xs hidden-sm">

        {include file="orderforms/$carttpl/sidebar-categories.tpl"}

    </div>

    <div class="col-md-9 pull-md-right">

        <div class="line-padded visible-xs visible-sm clearfix">

            {include file="orderforms/$carttpl/sidebar-categories-collapsed.tpl"}

        </div>

        <div class="fields-container">
            {foreach from=$addons item=addon}
                <div class="field-row clearfix">
                    <div class="col-xs-12">
                        <form method="post" action="{$smarty.server.PHP_SELF}?a=add">
                            <input type="hidden" name="aid" value="{$addon.id}" />
                            <div class="pull-right">
                                {if $addon.free}
                                    {$LANG.orderfree}
                                {else}
                                    {$addon.recurringamount} {$addon.billingcycle}
                                    {if $addon.setupfee}+ {$addon.setupfee} {$LANG.ordersetupfee}<br />{/if}
                                {/if}
                            </div>
                            <strong>{$addon.name}</strong>
                            <div class="line-padded">
                                {$addon.description}
                            </div>
                            <div class="col-sm-3">
                                {$LANG.cartproductaddonschoosepackage}:
                            </div>
                            <div class="col-sm-6">
                                <select name="productid" class="form-control">
                                    {foreach from=$addon.productids item=product}
                                        <option value="{$product.id}">{$product.product}{if $product.domain} - {$product.domain}{/if}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <input type="submit" value="{$LANG.ordernowbutton} &raquo;" class="btn btn-primary btn-block" />
                            </div>
                        </form>
                    </div>
                </div>
            {foreachelse}
                <div class="field-row clearfix">
                    <div class="col-xs-12 text-center">
                        <br />
                        {$LANG.cartproductaddonsnone}
                        <br /><br />
                    </div>
                </div>
            {/foreach}
        </div>

    </div>

    <div class="clearfix"></div>

    <div class="secure-warning">
        <img src="assets/img/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp;{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>

</div>
