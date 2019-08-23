<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">

    <div class="title-bar">
        <h1>{$LANG.cartproductaddons}</h1>
        {include file="templates/orderforms/{$carttpl}/category-chooser.tpl"}
    </div>

    <div class="row">

        {foreach from=$addons key=num item=addon}
            <div class="col-md-6">
                <div id="addon{$num}" class="product">

                    <div class="pricing">
                        {if $addon.free}
                            {$LANG.orderfree}
                        {else}
                            <span class="pricing">{$addon.recurringamount} {$addon.billingcycle}</span>
                            {if $addon.setupfee}<br />+ {$addon.setupfee} {$LANG.ordersetupfee}{/if}
                        {/if}
                    </div>

                    <div class="name">
                        {$addon.name}
                    </div>

                    <div class="description">{$addon.description}</div>

                    <form method="post" action="{$smarty.server.PHP_SELF}?a=add" class="form-inline">
                        <input type="hidden" name="aid" value="{$addon.id}" />
                        <div class="text-center">
                            <label for="inputProductId{$num}">{$LANG.cartproductaddonschoosepackage}</label><br />
                            <div class="form-group">
                                <select name="productid" id="inputProductId{$num}" class="form-control">
                                    {foreach from=$addon.productids item=product}
                                        <option value="{$product.id}">{$product.product}{if $product.domain} - {$product.domain}{/if}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-shopping-cart"></i>
                                {$LANG.ordernowbutton}
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            {if $num % 2}
                </div>
                <div class="row">
            {/if}

        {/foreach}

    </div>

    {if $noaddons}
        <div class="errorbox" style="display:block;">{$LANG.cartproductaddonsnone}</div>
    {/if}

</div>
