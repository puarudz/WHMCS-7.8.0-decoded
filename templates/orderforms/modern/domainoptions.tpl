{if $invalid}
    <div class="domaininvalid">
        {if $reason}
            {$reason}
        {else}
            {$LANG.cartdomaininvalid}
        {/if}
    </div>
    <p align="center">
        <button type="button" onclick="cancelcheck()" class="btn btn-default btn-lg">
            <i class="fas fa-arrow-circle-left"></i>
            {$LANG.carttryanotherdomain}
        </button>
    </p>
{elseif $alreadyindb}
    <div class="domaininvalid">
        {$LANG.cartdomainexists}
    </div>
    <p align="center">
        <button type="button" onclick="cancelcheck()" class="btn btn-default btn-lg">
            <i class="fas fa-arrow-circle-left"></i>
            {$LANG.carttryanotherdomain}
        </button>
    </p>
{else}

{if $checktype=="register" && $regenabled}

<input type="hidden" name="domainoption" value="register" />

{if $status eq "available" || $status eq "error"}

<div class="domainavailable">{$LANG.cartcongratsdomainavailable|sprintf2:$domain}</div>
<input type="hidden" name="domains[]" value="{$domain}" />
<div class="domainregperiod">{$LANG.cartregisterhowlong} <select name="domainsregperiod[{$domain}]" id="regperiod" class="form-control select-inline">{foreach key=period item=regoption from=$regoptions}{if $regoption.register}<option value="{$period}">{$period} {$LANG.orderyears} @ {$regoption.register}</option>{/if}{/foreach}</select></div>

{assign var='continueok' value=true}

{elseif $status eq "unavailable"}

<div class="domainunavailable">{$LANG.cartdomaintaken|sprintf2:$domain}</div>
<p align="center">
    <button type="button" onclick="cancelcheck()" class="btn btn-default btn-lg">
        <i class="fas fa-arrow-circle-left"></i>
        {$LANG.carttryanotherdomain}
    </button>
</p>


{/if}

{elseif $checktype=="transfer" && $transferenabled}

<input type="hidden" name="domainoption" value="transfer" />

{if $status eq "available"}

<div class="domainunavailable">{$LANG.carttransfernotregistered|sprintf2:$domain}</div>
<p align="center">
    <button type="button" onclick="cancelcheck()" class="btn btn-default btn-lg">
        <i class="fas fa-arrow-circle-left"></i>
        {$LANG.carttryanotherdomain}
    </button>
</p>

{elseif $status eq "unavailable" || $status eq "error"}

<div class="domainavailable">{$LANG.carttransferpossible|sprintf2:$domain:$transferprice}</div>
<input type="hidden" name="domains[]" value="{$domain}" />
<input type="hidden" name="domainsregperiod[{$domain}]" value="{$transferterm}" />

{assign var='continueok' value=true}

{/if}

{elseif $checktype=="owndomain" || $checktype=="subdomain"}

<input type="hidden" name="domainoption" value="{$checktype}" />
<input type="hidden" name="sld" value="{$sld}" />
<input type="hidden" name="tld" value="{$tld}" />
<script language="javascript">
completedomain();
</script>

{/if}

{if $othersuggestions}

<div class="domainsuggestions">{$LANG.cartotherdomainsuggestions}</div>

<table align="center" cellspacing="1" class="domainsuggestions">
<tr><th width="50"></th><th>{$LANG.domainname}</th><th>{$LANG.clientarearegistrationperiod}</th></tr>
{foreach from=$othersuggestions item=other}
<tr><td><input type="checkbox" name="domains[]" value="{$other.domain}" /></td><td>{$other.domain}</td><td><select name="domainsregperiod[{$other.domain}]">{foreach from=$other.regoptions key=period item=regoption}{if $regoption.register}<option value="{$period}">{$period} {$LANG.orderyears} @ {$regoption.register}</option>{/if}{/foreach}</select></td></tr>
{/foreach}
</table>

{assign var='continueok' value=true}

{/if}

{if $continueok}
<div class="text-center">
    <button type="submit" class="btn btn-primary btn-lg">{$LANG.continue} &nbsp;<i class="fas fa-arrow-circle-right"></i></button>
</div>
{/if}

{/if}
