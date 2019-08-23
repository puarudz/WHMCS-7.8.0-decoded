{if $approverEmailChangeSuccess}

    <div class="alert alert-success">
        <i class="fas fa-check"></i>
        Approver email updated successfully!
    </div>
    <p>You will receive an email shortly to <em>{$newApproverEmail}</em> to approve the certificate.</p>
    <p>If you do not receive the email, please check any spam filters and virus protection folders in case the email has been quarantined. If you are still unable to find it, please <a href="submitticket.php">contact support</a>.</p>

{else}

    <div class="alert alert-info">
        <i class="fas fa-exclamation-triangle"></i>
        Please disable any WHOIS privacy services before proceeding.
    </div>

    <p>Select an active email address from the list below. You will receive an email to approve the SSL certificate.</p>

    {if $errorMessage}
        <div class="alert alert-danger">
            <i class="fas fa-times"></i>
            {$errorMessage} Please try again later or <a href="submitticket.php">contact support</a>.
        </div>
    {/if}

    {if count($approverEmails) > 0}

        <form method="post" action="clientarea.php?action=productdetails">
            <input type="hidden" name="id" value="{$serviceid}">
            <input type="hidden" name="modop" value="custom">
            <input type="hidden" name="a" value="{$actionName}">

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
                    Update
                </button>
                <button type="reset" class="btn btn-default">
                    Cancel
                </button>
            </p>

        </form>

    {/if}

{/if}

<br>

<div class="well">
    <h4>About the Approver Email Process</h4>
    <p>In order to issue an SSL certificate, the Certificate Authority has to validate the authenticity of the certificate order to ensure the request is legitimate and comes from an authorized owner of the domain.</p>
    <p>Email-based domain validation is the most common certificate validation mechanism for certificate orders. The certificate authority compiles a list of approved email addresses using common administrative emails (e.g. admin or webmaster) in combination with the public whois data for the domain. Only one of these emails can be used to confirm ownership.</p>
</div>
