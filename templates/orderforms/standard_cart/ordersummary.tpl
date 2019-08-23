{if $producttotals}
    <span class="product-name">{if $producttotals.allowqty && $producttotals.qty > 1}{$producttotals.qty} x {/if}{$producttotals.productinfo.name}</span>
    <span class="product-group">{$producttotals.productinfo.groupname}</span>

    <div class="clearfix">
        <span class="pull-left">{$producttotals.productinfo.name}</span>
        <span class="pull-right">{$producttotals.pricing.baseprice}</span>
    </div>

    {foreach $producttotals.configoptions as $configoption}
        {if $configoption}
            <div class="clearfix">
                <span class="pull-left">&nbsp;&raquo; {$configoption.name}: {$configoption.optionname}</span>
                <span class="pull-right">{$configoption.recurring}{if $configoption.setup} + {$configoption.setup} {$LANG.ordersetupfee}{/if}</span>
            </div>
        {/if}
    {/foreach}

    {foreach $producttotals.addons as $addon}
        <div class="clearfix">
            <span class="pull-left">+ {$addon.name}</span>
            <span class="pull-right">{$addon.recurring}</span>
        </div>
    {/foreach}

    {if $producttotals.pricing.setup || $producttotals.pricing.recurring || $producttotals.pricing.addons}
        <div class="summary-totals">
            {if $producttotals.pricing.setup}
                <div class="clearfix">
                    <span class="pull-left">{$LANG.cartsetupfees}:</span>
                    <span class="pull-right">{$producttotals.pricing.setup}</span>
                </div>
            {/if}
            {foreach from=$producttotals.pricing.recurringexcltax key=cycle item=recurring}
                <div class="clearfix">
                    <span class="pull-left">{$cycle}:</span>
                    <span class="pull-right">{$recurring}</span>
                </div>
            {/foreach}
            {if $producttotals.pricing.tax1}
                <div class="clearfix">
                    <span class="pull-left">{$carttotals.taxname} @ {$carttotals.taxrate}%:</span>
                    <span class="pull-right">{$producttotals.pricing.tax1}</span>
                </div>
            {/if}
            {if $producttotals.pricing.tax2}
                <div class="clearfix">
                    <span class="pull-left">{$carttotals.taxname2} @ {$carttotals.taxrate2}%:</span>
                    <span class="pull-right">{$producttotals.pricing.tax2}</span>
                </div>
            {/if}
        </div>
    {/if}

    <div class="total-due-today">
        <span class="amt">{$producttotals.pricing.totaltoday}</span>
        <span>{$LANG.ordertotalduetoday}</span>
    </div>
{elseif $renewals}
    {if $carttotals.renewals}
        <span class="product-name">{lang key='domainrenewals'}</span>
        {foreach $carttotals.renewals as $domainId => $renewal}
            <div class="clearfix" id="cartDomainRenewal{$domainId}">
                <span class="pull-left">
                    {$renewal.domain} - {$renewal.regperiod} {if $renewal.regperiod == 1}{lang key='orderForm.year'}{else}{lang key='orderForm.years'}{/if}
                </span>
                <span class="pull-right">
                    {$renewal.priceBeforeTax}
                    <a onclick="removeItem('r','{$domainId}'); return false;" href="#" id="linkCartRemoveDomainRenewal{$domainId}">
                        <i class="fas fa-fw fa-trash-alt"></i>
                    </a>
                </span>
            </div>
            {if $renewal.dnsmanagement}
                <div class="clearfix">
                    <span class="pull-left">+ {lang key='domaindnsmanagement'}</span>
                </div>
            {/if}
            {if $renewal.emailforwarding}
                <div class="clearfix">
                    <span class="pull-left">+ {lang key='domainemailforwarding'}</span>
                </div>
            {/if}
            {if $renewal.idprotection}
                <div class="clearfix">
                    <span class="pull-left">+ {lang key='domainidprotection'}</span>
                </div>
            {/if}
            {if $renewal.hasGracePeriodFee}
                <div class="clearfix">
                    <span class="pull-left">+ {lang key='domainRenewal.graceFee'}</span>
                </div>
            {/if}
            {if $renewal.hasRedemptionGracePeriodFee}
                <div class="clearfix">
                    <span class="pull-left">+ {lang key='domainRenewal.redemptionFee'}</span>
                </div>
            {/if}

        {/foreach}
    {/if}
    <div class="summary-totals">
        <div class="clearfix">
            <span class="pull-left">{lang key='ordersubtotal'}:</span>
            <span class="pull-right">{$carttotals.subtotal}</span>
        </div>
        {if ($carttotals.taxrate && $carttotals.taxtotal) || ($carttotals.taxrate2 && $carttotals.taxtotal2)}
            {if $carttotals.taxrate}
                <div class="clearfix">
                    <span class="pull-left">{$carttotals.taxname} @ {$carttotals.taxrate}%:</span>
                    <span class="pull-right">{$carttotals.taxtotal}</span>
                </div>
            {/if}
            {if $carttotals.taxrate2}
                <div class="clearfix">
                    <span class="pull-left">{$carttotals.taxname2} @ {$carttotals.taxrate2}%:</span>
                    <span class="pull-right">{$carttotals.taxtotal2}</span>
                </div>
            {/if}
        {/if}
    </div>
    <div class="total-due-today">
        <span class="amt">{$carttotals.total}</span>
        <span>{lang key='ordertotalduetoday'}</span>
    </div>
{/if}
