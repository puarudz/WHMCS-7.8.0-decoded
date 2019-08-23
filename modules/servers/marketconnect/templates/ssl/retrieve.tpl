{if $errorMessage}

    <div class="alert alert-warning">
        <i class="fas fa-times fa-fw"></i>
        {$errorMessage}
    </div>

    <p>You can only retrieve the certificate once it has been issued by the certificate authority.</p>
    <p>If you only recently submitted the configuration information, please allow time for security checks to be completed and the certificate issued. For DV orders this is typically under 24 hours. For OV and EV level certificates it can take up to 3-5 days.</p>
    <p>If you continue to see this message after that time, please <a href="submitticket.php">contact support</a>.</p>

    <br><br>

{else}

    <div class="alert alert-success">
        <i class="fas fa-check fa-fw"></i>
        Your certificate has been successfully retrieved.
    </div>

    <p>Copy your certificate from the text below.</p>

    <textarea rows="15" class="form-control">
    {$certificate}
    </textarea>

    <br>

    <div class="well">
        <h4>Installing Your Certificate</h4>
        <p>To install your certificate, you must upload the certificate shown above to your web hosting server/control panel. The exact method for doing this varies depending upon the hosting environment being used.</p>
        <p>For further instructions, please refer to the <a href="https://knowledge.digicert.com/solution/SO16226.html" target="_blank">RapidSSL Installation Support</a> page.</p>
    </div>

{/if}
