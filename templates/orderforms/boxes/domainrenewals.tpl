<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="pull-md-right col-md-9">

        <div class="header-lined">
            <h1>{$LANG.domainrenewals}</h1>
        </div>

    </div>

    <div class="col-md-3 pull-md-left sidebar hidden-xs hidden-sm">

        {include file="orderforms/$carttpl/sidebar-categories.tpl"}

    </div>

    <div class="col-md-9 pull-md-right">

        <div class="line-padded visible-xs visible-sm clearfix">

            {include file="orderforms/$carttpl/sidebar-categories-collapsed.tpl"}

        </div>

        <form method="post" action="cart.php?a=add&renewals=true">

            <p>{$LANG.domainrenewdesc}</p>

            <table class="styled">
                <tr>
                    <th width="20"></th>
                    <th>{$LANG.orderdomain}</th>
                    <th>{$LANG.domainstatus}</th>
                    <th>{$LANG.domaindaysuntilexpiry}</th>
                    <th></th>
                </tr>
                {foreach from=$renewals item=renewal}
                    <tr>
                        <td>{if !$renewal.pastgraceperiod && !$renewal.beforerenewlimit}<input type="checkbox" name="renewalids[]" value="{$renewal.id}" />{/if}</td><td>{$renewal.domain}</td>
                        <td>{$renewal.status}</td>
                        <td>
                            {if $renewal.daysuntilexpiry > 30}
                                <span class="textgreen">{$renewal.daysuntilexpiry} {$LANG.domainrenewalsdays}</span>
                            {elseif $renewal.daysuntilexpiry > 0}
                                <span class="textred">{$renewal.daysuntilexpiry} {$LANG.domainrenewalsdays}</span>
                            {else}
                                <span class="textblack">{$renewal.daysuntilexpiry*-1} {$LANG.domainrenewalsdaysago}</span>
                            {/if}
                            {if $renewal.ingraceperiod}
                                <br />
                                <span class="textred">{$LANG.domainrenewalsingraceperiod}<span>
                            {/if}
                        </td>
                        <td>
                            {if $renewal.beforerenewlimit}
                                <span class="textred">{$LANG.domainrenewalsbeforerenewlimit|sprintf2:$renewal.beforerenewlimitdays}<span>
                            {elseif $renewal.pastgraceperiod}
                                <span class="textred">{$LANG.domainrenewalspastgraceperiod}<span>
                            {else}
                                <select name="renewalperiod[{$renewal.id}]">
                                    {foreach from=$renewal.renewaloptions item=renewaloption}
                                        <option value="{$renewaloption.period}">{$renewaloption.period} {$LANG.orderyears} @ {$renewaloption.price}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        </td>
                    </tr>
                {foreachelse}
                    <tr>
                        <td colspan="5" class="text-center">{$LANG.domainrenewalsnoneavailable}</td>
                    </tr>
                {/foreach}
            </table>

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
