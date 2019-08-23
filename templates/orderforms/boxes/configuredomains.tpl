<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="header-lined">
        <h1>{$LANG.cartconfigdomainextras}</h1>
    </div>

    <p>{$LANG.cartdomainsconfigdesc}</p>

    {if $errormessage}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <strong>{$LANG.clientareaerrors}</strong>
            <ul>
                {$errormessage}
            </ul>
        </div>
    {/if}

    <form method="post" action="{$smarty.server.PHP_SELF}?a=confdomains">
        <input type="hidden" name="update" value="true" />

        {foreach from=$domains key=num item=domain}

            <h2>{$domain.domain} - {$domain.regperiod} {$LANG.orderyears}</h2>
            <div class="fields-container">
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.hosting}</div>
                    <div class="col-sm-8">{if $domain.hosting}{$LANG.cartdomainshashosting}{else}<a href="cart.php" style="color:#cc0000;">{$LANG.cartdomainsnohosting}</a>{/if}</div>
                </div>
                {if $domain.configtoshow}
                    {if $domain.eppenabled}
                        <div class="field-row clearfix">
                            <div class="col-sm-4">{$LANG.domaineppcode}</div>
                            <div class="col-sm-8 row">
                                <div class="col-sm-5">
                                    <input type="text" name="epp[{$num}]" value="{$domain.eppvalue}" class="form-control" />
                                </div>
                                <div class="col-sm-7">
                                    {$LANG.domaineppcodedesc}
                                </div>
                            </div>
                        </div>
                    {/if}
                    {if $domain.dnsmanagement}
                        <div class="field-row clearfix">
                            <div class="col-sm-4">{$LANG.domaindnsmanagement}</div>
                            <div class="col-sm-8">
                                <label class="checkbox-inline"><input type="checkbox" name="dnsmanagement[{$num}]"{if $domain.dnsmanagementselected} checked{/if} /> {$domain.dnsmanagementprice}</label>
                            </div>
                        </div>
                    {/if}
                    {if $domain.emailforwarding}
                        <div class="field-row clearfix">
                            <div class="col-sm-4">{$LANG.domainemailforwarding}</div>
                            <div class="col-sm-8">
                                <label class="checkbox-inline"><input type="checkbox" name="emailforwarding[{$num}]"{if $domain.emailforwardingselected} checked{/if} /> {$domain.emailforwardingprice}</label>
                            </div>
                        </div>
                    {/if}
                    {if $domain.idprotection}
                        <div class="field-row clearfix">
                            <div class="col-sm-4">{$LANG.domainidprotection}</div>
                            <div class="col-sm-8">
                                <label class="checkbox-inline"><input type="checkbox" name="idprotection[{$num}]"{if $domain.idprotectionselected} checked{/if} /> {$domain.idprotectionprice}</label>
                            </div>
                        </div>
                    {/if}
                    {foreach from=$domain.fields key=domainfieldname item=domainfieldinput}
                        <div class="field-row clearfix">
                            <div class="col-sm-4">{$domainfieldname}</div>
                            <div class="col-sm-8">
                                {$domainfieldinput}
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </div>

        {/foreach}

        {if $atleastonenohosting}

            <h2>{$LANG.domainnameservers}</h2>

            <p>{$LANG.cartnameserversdesc}</p>

            <div class="fields-container">
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.domainnameserver1}</div>
                    <div class="col-sm-5"><input type="text" name="domainns1" value="{$domainns1}" class="form-control" /></div>
                </div>
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.domainnameserver2}</div>
                    <div class="col-sm-5"><input type="text" name="domainns2" value="{$domainns2}" class="form-control" /></div>
                </div>
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.domainnameserver3}</div>
                    <div class="col-sm-5"><input type="text" name="domainns3" value="{$domainns3}" class="form-control" /></div>
                </div>
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.domainnameserver4}</div>
                    <div class="col-sm-5"><input type="text" name="domainns4" value="{$domainns4}" class="form-control" /></div>
                </div>
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$LANG.domainnameserver5}</div>
                    <div class="col-sm-5"><input type="text" name="domainns5" value="{$domainns5}" class="form-control" /></div>
                </div>
            </div>
        {/if}

        <div class="line-padded text-center">
            <button type="submit" class="btn btn-primary btn-lg">{$LANG.continue} &nbsp;<i class="fas fa-arrow-circle-right"></i></button>
        </div>

    </form>

    <div class="secure-warning">
        <img src="assets/img/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp;{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>

</div>
