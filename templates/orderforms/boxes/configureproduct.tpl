<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="header-lined">
        <h1>{$LANG.orderconfigure}</h1>
    </div>

    <p>{$LANG.cartproductdesc}</p>

    <form method="post" action="{$smarty.server.PHP_SELF}?a=confproduct&i={$i}">
        <input type="hidden" name="configure" value="true">

        {if $errormessage}
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <strong>{$LANG.clientareaerrors}</strong>
                <ul>
                    {$errormessage}
                </ul>
            </div>
        {/if}

        <h3>{$LANG.orderproduct}</h3>
        <div class="fields-container">
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.orderproduct}</div>
                <div class="col-sm-8"><strong>{$productinfo.groupname} - {$productinfo.name}</strong></div>
            </div>
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.orderdesc}</div>
                <div class="col-sm-8">{$productinfo.description}</div>
            </div>
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.orderbillingcycle}</div>
                <div class="col-sm-8">
                    <input type="hidden" name="previousbillingcycle" value="{$billingcycle}" />
                    {if $pricing.type eq "free"}
                        <input type="hidden" name="billingcycle" value="free" />
                        {$LANG.orderfree}
                    {elseif $pricing.type eq "onetime"}
                        <input type="hidden" name="billingcycle" value="onetime" />
                        {$pricing.onetime} {$LANG.orderpaymenttermonetime}
                    {else}
                        <select name="billingcycle" onchange="submit()" class="form-control select-inline">
                            {if $pricing.monthly}<option value="monthly"{if $billingcycle eq "monthly"} selected="selected"{/if}>{$pricing.monthly}</option>{/if}
                            {if $pricing.quarterly}<option value="quarterly"{if $billingcycle eq "quarterly"} selected="selected"{/if}>{$pricing.quarterly}</option>{/if}
                            {if $pricing.semiannually}<option value="semiannually"{if $billingcycle eq "semiannually"} selected="selected"{/if}>{$pricing.semiannually}</option>{/if}
                            {if $pricing.annually}<option value="annually"{if $billingcycle eq "annually"} selected="selected"{/if}>{$pricing.annually}</option>{/if}
                            {if $pricing.biennially}<option value="biennially"{if $billingcycle eq "biennially"} selected="selected"{/if}>{$pricing.biennially}</option>{/if}
                            {if $pricing.triennially}<option value="triennially"{if $billingcycle eq "triennially"} selected="selected"{/if}>{$pricing.triennially}</option>{/if}
                        </select>
                    {/if}
                </div>
            </div>
        </div>

        {if $productinfo.type eq "server"}
        <h3>{$LANG.cartconfigserver}</h3>
        <div class="fields-container">
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.serverhostname}</div>
                <div class="col-sm-8 row">
                    <div class="col-xs-4">
                        <input type="text" name="hostname" value="{$server.hostname}" class="form-control" />
                    </div>
                    <div class="col-xs-8">
                        <p class="form-control-static">{$LANG.serverhostnameexample}</p>
                    </div>
                </div>
            </div>
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.serverns1prefix}</div>
                <div class="col-sm-8 row">
                    <div class="col-xs-3">
                        <input type="text" name="ns1prefix" value="{$server.ns1prefix}" class="form-control" />
                    </div>
                    <div class="col-xs-9">
                        <p class="form-control-static">{$LANG.serverns1prefixexample}</p>
                    </div>
                </div>
            </div>
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.serverns2prefix}</div>
                <div class="col-sm-8 row">
                    <div class="col-xs-3">
                        <input type="text" name="ns2prefix" value="{$server.ns2prefix}" class="form-control" />
                    </div>
                    <div class="col-xs-9">
                        <p class="form-control-static">{$LANG.serverns2prefixexample}</p>
                    </div>
                </div>
            </div>
            <div class="field-row clearfix">
                <div class="col-sm-4">{$LANG.serverrootpw}</div>
                <div class="col-sm-8 row">
                    <div class="col-xs-3">
                        <input type="password" name="rootpw" value="{$server.rootpw}" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
        {/if}

        {if $configurableoptions}
        <h3>{$LANG.orderconfigpackage}</h3>
        <div class="fields-container">
            {foreach from=$configurableoptions item=configoption}
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$configoption.optionname}</div>
                    <div class="col-sm-8">
                        {if $configoption.optiontype eq 1}
                        <select name="configoption[{$configoption.id}]" class="form-control select-inline">
                            {foreach key=num2 item=options from=$configoption.options}
                                <option value="{$options.id}"{if $configoption.selectedvalue eq $options.id} selected="selected"{/if}>{$options.name}</option>
                            {/foreach}
                        </select>
                        {elseif $configoption.optiontype eq 2}
                            {foreach key=num2 item=options from=$configoption.options}
                                <label class="radio-inline"><input type="radio" name="configoption[{$configoption.id}]" value="{$options.id}"{if $configoption.selectedvalue eq $options.id} checked="checked"{/if}> {$options.name}</label><br />
                            {/foreach}
                        {elseif $configoption.optiontype eq 3}
                            <label class="checkbox-inline"><input type="checkbox" name="configoption[{$configoption.id}]" value="1"{if $configoption.selectedqty} checked{/if}> {$configoption.options.0.name}</label>
                        {elseif $configoption.optiontype eq 4}
                            <input type="text" name="configoption[{$configoption.id}]" value="{$configoption.selectedqty}" size="5"> x {$configoption.options.0.name}
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>
        {/if}

        {if $addons}
        <h3>{$LANG.cartaddons}</h3>
        <div class="fields-container">
            {foreach from=$addons item=addon}
                <div class="field-row clearfix">
                    <div class="col-sm-4"><label class="checkbox-inline">{$addon.checkbox} {$addon.name}</label></div>
                    <div class="col-sm-8">
                        {$addon.description}<br />
                        <strong>{$addon.pricing}</strong>
                    </div>
                </div>
            {/foreach}
        </div>
        {/if}

        {if $customfields}
        <h3>{$LANG.orderadditionalrequiredinfo}</h3>
        <div class="fields-container">
            {foreach from=$customfields item=customfield}
                <div class="field-row clearfix">
                    <div class="col-sm-4">{$customfield.name}</div>
                    <div class="col-sm-8">
                        {$customfield.input}{if $customfield.description}<br />{$customfield.description}{/if}
                    </div>
                </div>
            {/foreach}
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
