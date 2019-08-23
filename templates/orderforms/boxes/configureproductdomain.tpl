<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<script type="text/javascript" src="templates/orderforms/{$carttpl}/checkout.js"></script>

<div id="order-boxes">

    <div class="header-lined">
        <h1>{$productinfo.name}</h1>
    </div>

    <p>{$LANG.cartproductdomaindesc}</p>

    {if $errormessage}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <strong>{$LANG.clientareaerrors}</strong>
            <ul>
                {$errormessage}
            </ul>
        </div>
    {/if}

    <form method="post" action="{$smarty.server.PHP_SELF}?a=add&pid={$pid}">

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="fields-container">

                    {if $incartdomains}
                        <div class="field-row clearfix">
                            <label class="radio-inline product-radio"><input type="radio" name="domainoption" value="incart" onclick="chooseDomainReg('incart')"{if $domainoption eq "incart"} checked{/if} /> {$LANG.cartproductdomainuseincart}</label>
                            <div class="row domain-option line-padded{if $domainoption neq "incart"} hidden{/if}" id="domopt-incart">
                                <div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
                                    <select name="incartdomain" class="form-control">
                                        {foreach key=num item=incartdomain from=$incartdomains}
                                            <option value="{$incartdomain}">{$incartdomain}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        </div>
                    {/if}

                    {if $registerdomainenabled}
                        <div class="field-row clearfix">
                            <label class="radio-inline product-radio"><input type="radio" name="domainoption" value="register"{if $domainoption eq "register"} checked{/if} onclick="chooseDomainReg('register')" /> {$LANG.orderdomainoption1part1} {$companyname} {$LANG.orderdomainoption1part2}</label>
                            <div class="row domain-option line-padded{if $domainoption neq "register"} hidden{/if}" id="domopt-register">
                                <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">www.</p></div>
                                <div class="col-sm-5 col-xs-6"><input type="text" name="sld[0]" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                <div class="col-sm-2 col-xs-3"><select name="tld[0]" class="form-control">
                                    {foreach from=$registertlds item=listtld}
                                        <option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
                                    {/foreach}
                                </select></div>
                            </div>
                        </div>
                    {/if}

                    {if $transferdomainenabled}
                        <div class="field-row clearfix">
                            <label class="radio-inline product-radio"><input type="radio" name="domainoption" value="transfer"{if $domainoption eq "transfer"} checked{/if} onclick="chooseDomainReg('transfer')" /> {$LANG.orderdomainoption3} {$companyname}</label>
                            <div class="row domain-option line-padded{if $domainoption neq "transfer"} hidden{/if}" id="domopt-transfer">
                                <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">www.</p></div>
                                <div class="col-sm-5 col-xs-6"><input type="text" name="sld[1]" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                <div class="col-sm-2 col-xs-3"><select name="tld[1]" class="form-control">
                                    {foreach from=$transfertlds item=listtld}
                                        <option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
                                    {/foreach}
                                </select></div>
                            </div>
                        </div>
                    {/if}

                    {if $owndomainenabled}
                        <div class="field-row clearfix">
                            <label class="radio-inline product-radio"><input type="radio" name="domainoption" value="owndomain"{if $domainoption eq "owndomain"} checked{/if} onclick="chooseDomainReg('owndomain')" /> {$LANG.orderdomainoption2}</label>
                            <div class="row domain-option line-padded{if $domainoption neq "owndomain"} hidden{/if}" id="domopt-owndomain">
                                <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">www.</p></div>
                                <div class="col-sm-5 col-xs-6"><input type="text" name="sld[2]" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                <div class="col-sm-2 col-xs-3"><input type="text" name="tld[2]" value="{$tld|substr:1}" class="form-control" autocapitalize="none" /></div>
                            </div>
                        </div>
                    {/if}

                    {if $subdomains}
                        <div class="field-row clearfix">
                            <div class="col-sm-12">
                                <label class="radio-inline product-radio"><input type="radio" name="domainoption" value="subdomain"{if $domainoption eq "subdomain"} checked{/if} onclick="chooseDomainReg('subdomain')" /> {$LANG.orderdomainoption4}</label>
                                <div class="domain-option line-padded{if $domainoption neq "subdomain"} hidden{/if}" id="domopt-subdomain">
                                    <div class="col-sm-1 col-sm-offset-2 col-xs-3"><p class="form-control-static text-right">http://</p></div>
                                    <div class="col-sm-3 col-xs-3"><input type="text" name="sld[3]" value="{$sld}" class="form-control" autocapitalize="none" /></div>
                                    <div class="col-sm-4 col-xs-6">
                                        <select name="tld[3]" class="form-control">
                                            {foreach from=$subdomains key=subid item=subdomain}
                                                <option value="{$subid}">{$subdomain}</option>
                                            {/foreach}
                                        </select>
                                    </div>
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
                            <tr class="text-center"><td>{$result.domain}</td><td class="{if $result.status eq $searchvar}textgreen{else}textred{/if}">{if $result.status eq $searchvar}<input type="checkbox" name="domains[]" value="{$result.domain}"{if $num eq 0} checked{/if} /> {$LANG.domainavailable}{else}{$LANG.domainunavailable}{/if}</td><td>{if $result.regoptions}<select name="domainsregperiod[{$result.domain}]">{foreach key=period item=regoption from=$result.regoptions}{if $regoption.$domainoption}<option value="{$period}">{$period} {$LANG.orderyears} @ {$regoption.$domainoption}</option>{/if}{/foreach}</select>{/if}</td></tr>
                        {/foreach}
                    </table>

                {/if}

                {if $freedomaintlds}
                    <p>* <em>{$LANG.orderfreedomainregistration} {$LANG.orderfreedomainappliesto}: {$freedomaintlds}</em></p>
                {/if}

            </div>

            <div class="clearfix"></div>

        </div>

        <div class="line-padded text-center">
            <button type="submit" class="btn btn-primary btn-lg">{$LANG.continue} &nbsp;<i class="fas fa-arrow-circle-right"></i></button>
        </div>

    </form>

    <div class="secure-warning">
        <img src="assets/img/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp;{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>

</div>
