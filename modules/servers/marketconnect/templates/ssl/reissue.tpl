<p>If you have lost your certificate, need to move servers or had a problem with the installation then you can re-issue your certificate to generate a new one.</p>

<div class="alert alert-info">
    <i class="fas fa-info-circle fa-fw"></i>
    When reissueing a certificate the domain name cannot be changed and must be exactly the same.
</div>

{include file="$template/includes/subheader.tpl" title=$LANG.sslserverinfo}

<p>{$LANG.sslserverinfodetails}</p>

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

    <div class="form-group">
        <label for="inputCsr">{$LANG.sslcsr}</label>
        <textarea name="csr" id="inputCsr" rows="10" class="form-control">{if $csr}{$csr}{else}-----BEGIN CERTIFICATE REQUEST-----
-----END CERTIFICATE REQUEST-----{/if}</textarea>
    </div>

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
