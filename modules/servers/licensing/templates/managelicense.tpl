{if $status == "Reissued"}
    <div class="alert alert-success text-center">
        {$LANG.licensingaddon.reissuestatusmsg}
    </div>
{/if}

{if $downloads}
    <div class="alert alert-warning text-center licensing-addon-latest-download">
        <h3>{$LANG.licensingaddon.latestdownload}</h3>
        <p>{$downloads.0.description|nl2br}</p>
        <p><a href="{$downloads.0.link}" class="btn btn-default">
            <i class="fas fa-fw fa-download"></i>
            {$LANG.licensingaddon.downloadnow}
        </a></p>
    </div>
{/if}

{foreach $hookOutput as $output}
    <div>
        {$output}
    </div>
{/foreach}

<div class="row">

    <div class="col-md-4 pull-md-right">

        <div class="row">

            {if $allowreissues}
                <div class="col-xs-4 col-md-12 margin-bottom-5">
                    <form method="post" action="clientarea.php?action=productdetails">
                        <input type="hidden" name="id" value="{$id}" />
                        <input type="hidden" name="serveraction" value="custom" />
                        <input type="hidden" name="a" value="reissue" />
                        <button type="submit" class="btn btn-success btn-lg btn-block"{if $status != "Active"} disabled{/if}>
                            <i class="fas fa-sync fa-2x"></i><br />
                            {$LANG.licensingaddon.reissue}
                        </button>
                    </form>
                </div>
            {/if}

            {if $packagesupgrade}
                <div class="col-xs-4 col-md-12 margin-bottom-5">
                    <a href="upgrade.php?type=package&id={$id}" role="button" class="btn btn-info btn-lg btn-block">
                        <i class="fas fa-arrow-up fa-2x"></i><br />
                        {$LANG.upgrade}
                    </a>
                </div>
            {/if}

            <div class="col-xs-4 col-md-12 margin-bottom-5">
                <form method="post" action="clientarea.php?action=cancel">
                    <input type="hidden" name="id" value="{$id}" />
                    <button type="submit" class="btn btn-danger btn-lg btn-block{if $pendingcancellation} disabled{/if}">
                        <i class="fas fa-times fa-2x"></i><br />
                        {if $pendingcancellation}
                            {$LANG.cancellationrequested}
                        {else}
                            {$LANG.cancel}
                        {/if}
                    </button>
                </form>
            </div>

        </div>

    </div>
    <div class="col-md-8">

        <h4>{$LANG.licensingaddon.licensekey}</h4>
        <input type="text" class="form-control" readonly="true" value="{$licensekey}" />

        {if $configurableoptions}
            <div class="alert alert-info margin-top-5">
                {foreach from=$configurableoptions item=configoption}
                    <div class="row">
                        <div class="col-xs-5 text-right">
                            <strong>{$configoption.optionname}</strong>
                        </div>
                        <div class="col-xs-7">
                            {if $configoption.optiontype eq 3}
                                {if $configoption.selectedqty}
                                    {$LANG.yes}
                                {else}
                                    {$LANG.no}
                                {/if}
                            {elseif $configoption.optiontype eq 4}
                                {$configoption.selectedqty} x {$configoption.selectedoption}
                            {else}
                                {$configoption.selectedoption}
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/if}

        {if !$allowDomainConflicts}
            <h4>{$LANG.licensingaddon.validdomains}</h4>
            <textarea rows="2" class="form-control" readonly="true">{$validdomain}</textarea>
        {/if}

        {if !$allowIpConflicts}
            <h4>{$LANG.licensingaddon.validips}</h4>
            <textarea rows="2" class="form-control" readonly="true">{$validip}</textarea>
        {/if}

        {if !$allowDirectoryConflicts}
            <h4>{$LANG.licensingaddon.validdirectory}</h4>
            <textarea rows="2" class="form-control" readonly="true">{$validdirectory}</textarea>
        {/if}

        <h4>{$LANG.licensingaddon.status}</h4>
        <p>
            {$status}
            {if $suspendreason}({$suspendreason}){/if}
        </p>

    </div>

</div>

<div class="row">
    <div class="col-sm-4 text-center">
        <h4>{$LANG.clientareahostingregdate}</h4>
        {$regdate}
    </div>
    <div class="col-sm-4 text-center">
        <h4>{$LANG.clientareahostingnextduedate}</h4>
        {$nextduedate}
    </div>
    <div class="col-sm-4 text-center">
        <h4>{$LANG.orderbillingcycle}</h4>
        {$billingcycle}
    </div>
</div>

<div class="row">
    {if $firstpaymentamount neq $recurringamount}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.firstpaymentamount}</h4>
        {$firstpaymentamount}
    </div>
    {/if}
    {if $billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderpaymenttermfreeaccount}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.recurringamount}</h4>
        {$recurringamount}
    </div>
    {/if}
    {if $firstpaymentamount neq $recurringamount || ($billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderpaymenttermfreeaccount)}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.orderpaymentmethod}</h4>
        {$paymentmethod}
    </div>
    {/if}
</div>

{if $customfields}
    <div class="row">
        {foreach from=$customfields item=field}
            <div class="col-sm-4 text-center">
                <h4>{$field.name}</h4>
                {if $field.value}{$field.value}{else}-{/if}
            </div>
        {/foreach}
    </div>
{/if}
