<p>If you have lost your certificate, need to move servers or had a problem with the installation then you can re-issue your certificate to generate a new one.</p>

<div class="alert alert-info">
    <i class="fas fa-info-circle fa-fw"></i>
    When reissueing a certificate the domain name cannot be changed and must be exactly the same.
</div>

{include file="$template/includes/subheader.tpl" title=$LANG.sslcertinfo}

<div class="row">
    <div class="col-sm-3 text-right">
        <strong>Domain Name</strong>
    </div>
    <div class="col-sm-9">
        {$csrData.DomainName}
    </div>
</div>
{if $csrData.Organization}
    <div class="row">
        <div class="col-sm-3 text-right">
            <strong>Organization</strong>
        </div>
        <div class="col-sm-9">
            {$csrData.Organization}
        </div>
    </div>
{/if}
{if $csrData.Locality}
    <div class="row">
        <div class="col-sm-3 text-right">
            <strong>Locality</strong>
        </div>
        <div class="col-sm-9">
            {$csrData.Locality}
        </div>
    </div>
{/if}
{if $csrData.State}
    <div class="row">
        <div class="col-sm-3 text-right">
            <strong>State</strong>
        </div>
        <div class="col-sm-9">
            {$csrData.State}
        </div>
    </div>
{/if}
{if $csrData.Country}
    <div class="row">
        <div class="col-sm-3 text-right">
            <strong>Country</strong>
        </div>
        <div class="col-sm-9">
            {$csrData.Country}
        </div>
    </div>
{/if}

{include file="$template/includes/subheader.tpl" title=$LANG.sslcertapproveremail}

<p>{$LANG.sslcertapproveremaildetails}</p>

{if $errorMessage}
    <div class="alert alert-danger">
        <i class="fas fa-times"></i>
        {$errorMessage}
    </div>
{/if}

<form method="post" action="clientarea.php?action=productdetails">
    <input type="hidden" name="id" value="{$serviceid}">
    <input type="hidden" name="modop" value="custom">
    <input type="hidden" name="a" value="{$actionName}">

    <div class="form-group hidden">
        <label for="inputCsr">{$LANG.sslcsr}</label>
        <textarea name="csr" id="inputCsr" rows="10" class="form-control">{$csr}</textarea>
    </div>

    <blockquote>
        {foreach $approverEmails as $approverEmail}
            <label class="radio-inline">
                <input type="radio" name="approver_email" value="{$approverEmail}"{if $approverEmail@first} checked{/if}>
                {$approverEmail}
            </label>
            <br>
        {/foreach}
    </blockquote>

    <p class="text-center">
        <button type="submit" class="btn btn-primary">
            Continue
        </button>
        <button type="reset" class="btn btn-default">
            Cancel
        </button>
    </p>

</form>

<br><br>
