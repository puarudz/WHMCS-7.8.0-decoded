<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<script type="text/javascript" src="templates/orderforms/{$carttpl}/checkout.js"></script>

<div id="order-boxes">

    <div class="pull-md-right col-md-9">

        {if $domain eq "register"}
            <div class="header-lined">
                <h1>{$LANG.navregisterdomain}</h1>
            </div>
        {else}
            <div class="header-lined">
                <h1>{$LANG.transferinadomain}</h1>
            </div>
        {/if}

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
                {if $registerdomainenabled}
                    <div class="field-row clearfix">
                        <div class="col-sm-12">
                            <label class="radio-inline product-radio"><input type="radio" name="domain" value="register"{if $domain eq "register"} checked{/if} onclick="chooseDomainReg('register')" /> {$LANG.orderdomainoption1part1} {$companyname} {$LANG.orderdomainoption1part2}</label>
                            <div class="domain-option line-padded{if $domain neq "register"} hidden{/if}" id="domopt-register">
                                <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">www.</p></div>
                                <div class="col-sm-5 col-xs-6"><input type="text" name="sld" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                <div class="col-sm-2 col-xs-3"><select name="tld" class="form-control">
                                    {foreach from=$registertlds item=listtld}
                                        <option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
                                    {/foreach}
                                </select></div>
                            </div>
                        </div>
                    </div>
                {/if}

                {if $transferdomainenabled}
                    <div class="field-row clearfix">
                        <div class="col-sm-12">
                            <label class="radio-inline product-radio"><input type="radio" name="domain" value="transfer"{if $domain eq "transfer"} checked{/if} onclick="chooseDomainReg('transfer')" /> {$LANG.orderdomainoption3} {$companyname}</label>
                            <div class="domain-option line-padded{if $domain neq "transfer"} hidden{/if}" id="domopt-transfer">
                                <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">www.</p></div>
                                <div class="col-sm-5 col-xs-6"><input type="text" name="sld_transfer" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                <div class="col-sm-2 col-xs-3"><select name="tld_transfer" class="form-control">
                                    {foreach from=$transfertlds item=listtld}
                                        <option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
                                    {/foreach}
                                </select></div>
                            </div>
                        </div>
                    </div>
                {/if}

            </div>

            {if $availabilityresults}

                <h2>{$LANG.choosedomains}</h2>

                <table class="styled">
                    <tr><th>{$LANG.domainname}</th><th>{$LANG.domainstatus}</th><th>{$LANG.domainmoreinfo}</th></tr>
                    {foreach key=num item=result from=$availabilityresults}
                        <tr class="text-center">
                            <td>{$result.domain}</td>
                            <td class="{if $result.status eq $searchvar}textgreen{else}textred{/if}"><label class="checkbox-inline">{if $result.status eq $searchvar}<input type="checkbox" name="domains[]" value="{$result.domain}"{if $result.domain|in_array:$domains} checked{/if} /> {$LANG.domainavailable}{else}{$LANG.domainunavailable}{/if}</label></td>
                            <td>{if $result.regoptions}<select name="domainsregperiod[{$result.domain}]">{foreach key=period item=regoption from=$result.regoptions}{if $regoption.$domain}<option value="{$period}">{$period} {$LANG.orderyears} @ {$regoption.$domain}</option>{/if}{/foreach}</select>{/if}</td>
                        </tr>
                    {/foreach}
                </table>

            {/if}

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
