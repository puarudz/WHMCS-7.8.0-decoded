<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">

    <div class="text-center">
        <h1>{$LANG.cartdomainsconfig}</h1>
        <p>{$LANG.cartdomainsconfiginfo}</p>
    </div>

    {if $errormessage}
        <div class="errorbox" style="display:block;">
            {$errormessage|replace:'<li>':' &nbsp;#&nbsp; '} &nbsp;#&nbsp;
        </div>
        <br />
    {/if}

    <form method="post" action="{$smarty.server.PHP_SELF}?a=confdomains">
        <input type="hidden" name="update" value="true" />

        {foreach key=num item=domain from=$domains}

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        {$domain.domain} - {$domain.regperiod} {$LANG.orderyears}
                        {if $domain.hosting}
                            <span style="color:#009900;">[{$LANG.cartdomainshashosting}]</span>
                        {else}
                            <a href="cart.php" style="color:#cc0000;">[{$LANG.cartdomainsnohosting}]</a>
                        {/if}
                    </h3>
                </div>
                <div class="panel-body">

                    <div class="row">
                        <div class="col-sm-4">{$LANG.hosting}:</div>
                        <div class="col-sm-8">{if $domain.hosting}<span style="color:#009900;">[{$LANG.cartdomainshashosting}]</span>{else}<a href="cart.php" style="color:#cc0000;">[{$LANG.cartdomainsnohosting}]</a>{/if}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">{$LANG.orderregperiod}:</div>
                        <div class="col-sm-8">{$domain.regperiod} {$LANG.orderyears}</div>
                    </div>
                    {if $domain.eppenabled}
                        <div class="row">
                            <div class="col-sm-4">{$LANG.domaineppcode}:</div>
                            <div class="col-sm-8"><input type="text" name="epp[{$num}]" size="20" value="{$domain.eppvalue}" class="form-control" /> {$LANG.domaineppcodedesc}</div>
                        </div>
                    {/if}
                    {if $domain.dnsmanagement || $domain.emailforwarding || $domain.idprotection}
                        <div class="row">
                            <div class="col-sm-4">{$LANG.cartaddons}:</div>
                            <div class="col-sm-8">

                                {if $domain.dnsmanagement}
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="dnsmanagement[{$num}]"{if $domain.dnsmanagementselected} checked{/if} />
                                        {$LANG.domaindnsmanagement} ({$domain.dnsmanagementprice})
                                    </label>
                                    <br />
                                {/if}
                                {if $domain.emailforwarding}
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="emailforwarding[{$num}]"{if $domain.emailforwardingselected} checked{/if} />
                                        {$LANG.domainemailforwarding} ({$domain.emailforwardingprice})
                                    </label>
                                    <br />
                                {/if}
                                {if $domain.idprotection}
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="idprotection[{$num}]"{if $domain.idprotectionselected} checked{/if} />
                                        {$LANG.domainidprotection} ({$domain.idprotectionprice})
                                    </label>
                                    <br />
                                {/if}

                            </div>
                        </div>
                    {/if}
                    {foreach from=$domain.fields key=domainfieldname item=domainfield}
                        <div class="row">
                            <div class="col-sm-4">{$domainfieldname}:</div>
                            <div class="col-sm-8">{$domainfield}</div>
                        </div>
                    {/foreach}
                </div>
            </div>

        {/foreach}

        {if $atleastonenohosting}

            <h2>{$LANG.domainnameservers}</h2>
            <p>{$LANG.cartnameserversdesc}</p>

            <div class="form-horizontal">
                <div class="form-group">
                    <label for="inputNs1" class="col-sm-3 col-sm-offset-1 control-label">{$LANG.domainnameserver1}</label>
                    <div class="col-sm-7 col-md-5">
                        <input type="text" class="form-control" id="inputNs1" name="domainns1" value="{$domainns1}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputNs2" class="col-sm-3 col-sm-offset-1 control-label">{$LANG.domainnameserver2}</label>
                    <div class="col-sm-7 col-md-5">
                        <input type="text" class="form-control" id="inputNs2" name="domainns2" value="{$domainns2}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputNs3" class="col-sm-3 col-sm-offset-1 control-label">{$LANG.domainnameserver3}</label>
                    <div class="col-sm-7 col-md-5">
                        <input type="text" class="form-control" id="inputNs3" name="domainns3" value="{$domainns3}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputNs1" class="col-sm-3 col-sm-offset-1 control-label">{$LANG.domainnameserver4}</label>
                    <div class="col-sm-7 col-md-5">
                        <input type="text" class="form-control" id="inputNs4" name="domainns4" value="{$domainns4}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputNs5" class="col-sm-3 col-sm-offset-1 control-label">{$LANG.domainnameserver5}</label>
                    <div class="col-sm-7 col-md-5">
                        <input type="text" class="form-control" id="inputNs5" name="domainns5" value="{$domainns5}" />
                    </div>
                </div>
            </div>

        {/if}

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">{$LANG.continue} &nbsp;<i class="fas fa-arrow-circle-right"></i></button>
        </div>

    </form>

</div>
