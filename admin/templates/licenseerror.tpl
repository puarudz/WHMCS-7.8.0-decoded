<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {if $licenseError eq "suspended"}
            {assign var='pageTitle' value='License Suspended'}
        {elseif $licenseError eq "pending"}
            {assign var='pageTitle' value='License Key Pending'}
        {elseif $licenseError eq "invalid"}
            {assign var='pageTitle' value='Invalid License'}
        {elseif $licenseError eq "expired"}
            {assign var='pageTitle' value='Expired License'}
        {elseif $licenseError eq "version"}
            {assign var='pageTitle' value='Renewal Required'}
        {elseif $licenseError eq "noconnection"}
            {assign var='pageTitle' value='Connection Error'}
        {elseif $licenseError eq "error"}
            {assign var='pageTitle' value='Licensing Error'}
        {elseif $licenseError eq "change"}
            {assign var='pageTitle' value='Update License Key'}
        {/if}
        <title>WHMCS - {$pageTitle}</title>

        <link href="templates/login.min.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
      </head>
      <body>
        <div class="login-container">
            <h1 class="logo">
                <a href="login.php">
                    <img src="{$BASE_PATH_IMG}/whmcs.png" alt="WHMCS" />
                </a>
            </h1>
            <div class="login-body"{if $licenseError eq "noconnection" or $licenseError eq "change"} style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;"{/if}>
                <h2>{$pageTitle}</h2>
                {if $infoMsg}
                    <div class="alert alert-info text-center" role="alert">
                        {$infoMsg}
                    </div>
                {/if}
                {if $successMsg}
                    <div class="alert alert-success text-center" role="alert">
                        {$successMsg}
                    </div>
                {/if}
                {if $errorMsg}
                    <div class="alert alert-danger text-center" role="alert">
                        {$errorMsg}
                    </div>
                {/if}

                {if $licenseError eq "suspended"}
                    <p>Your license key has been suspended.  Possible reasons for this include:</p>
                    <ul>
                        <li>Your license is overdue on payment</li>
                        <li>Your license has been suspended for being used on a banned
                            domain</li>
                        <li>Your license was found to be being used against the End User
                            License Agreement</li>
                    </ul>
                {elseif $licenseError eq "pending"}
                    <p>The WHMCS License Key you just tried to access is still pending. This error occurs when we have not yet received the payment for your license.</p>
                {elseif $licenseError eq "invalid"}
                    <p>Your license key is invalid. Possible reasons for this include:</p>
                    <ul>
                        <li>The license key has been entered incorrectly</li>
                        <li>The domain being used to access your install has changed</li>
                        <li>The IP address your install is located on has changed</li>
                        <li>The directory you are using has changed</li>
                    </ul>
                    <p>
                        If required, you can reissue your license on-demand from our client
                        area @ <a href="https://www.whmcs.com/members/clientarea.php"
                                  target="_blank">www.whmcs.com/members/clientarea.php</a> which will
                        update the allowed location details.
                    </p>
                {elseif $licenseError eq "expired"}
                    <p>Your license key has expired!  To resolve this you can:</p>
                    <ul>
                        <li>Check your email for a copy of the invoice or payment reminders</li>
                        <li>Order a new license from <a href="https://www.whmcs.com/order"
                                                        target="_blank">www.whmcs.com/order</a></li>
                    </ul>
                    <p>
                        If you feel this message to be an error, please contact us @ <a
                                href="https://www.whmcs.com/support" target="_blank">www.whmcs.com/support</a>
                    </p>
                {elseif $licenseError eq "version"}
                    <p>
                        You are using an Owned License for which the support & updates
                        validity period expired before this release. Therefore in order to
                        use this version of WHMCS, you first need to renew your support &
                        updates access. You can do this from our client area @ <a
                                href="https://www.whmcs.com/members/clientarea.php" target="_blank">www.whmcs.com/members/clientarea.php</a>
                    </p>
                    <p>
                        If you feel this message to be an error, please contact us @ <a
                                href="https://www.whmcs.com/support" target="_blank">www.whmcs.com/support</a>
                    </p>
                {elseif $licenseError eq "noconnection"}
                    <p>WHMCS has not been able to verify your license for the last few days.</p>
                    <p>Before you can access your WHMCS Admin Area again, the license
                        needs to be validated successfully. Please check & ensure that you
                        don't have a firewall or DNS rule blocking outgoing connections to
                        our website.</p>
                    <p>
                        For further assistance, please refer to the <a
                                href="https://docs.whmcs.com/License_Troubleshooting"
                                target="_blank">License Troubleshooting</a> documentation.
                    </p>
                {elseif $licenseError eq "error"}
                    <p>Unable to perform license validation due to the following <strong>local server</strong> configuration issue:</p>
                    <div class="alert alert-danger">
                        {$licenseCheckError}
                    </div>
                    <p>Please resolve the error shown above to enable license validation to complete successfully.</p>
                    <p>
                        For further assistance, please refer to the <a
                                href="https://docs.whmcs.com/License_Troubleshooting"
                                target="_blank">License Troubleshooting</a> documentation.
                    </p>
                {elseif $licenseError eq "change"}
                    <p>You can change your license key by entering your admin login details
                        and new key below. Requires full admin access permissions.</p>
                    <form method="post"
                          action="?licenseerror=change&updatekey=true">
                        <div class="form-group">
                            <input type="text" class="form-control" name="username" placeholder="Username" autofocus>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control" name="password" placeholder="Password">
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="newlicensekey" placeholder="New License Key">
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <input type="submit" value="Change License Key" class="btn btn-primary btn-block">
                            </div>
                        </div>
                    </form>
                {/if}
            </div>
            <div class="footer">
                {if $licenseError neq "noconnection" and $licenseError neq "error" and $licenseError neq "change"}
                    <a href="licenseerror.php?licenseerror=change">Click here to enter a new license key.</a>
                {/if}
            </div>
        </div>
        <div class="poweredby text-center">
            <a href="https://www.whmcs.com/" target="_blank">Powered by WHMCS</a>
        </div>
        <script type="text/javascript" src="templates/login.min.js"></script>
    </body>
</html>
