<div class="alert alert-info text-center">

    {$ADDONLANG.licenseVerificationToolInfo}<br />
    {$ADDONLANG.reportUnlicensed}

</div>

<h3>{$ADDONLANG.enterDomain}</h3>

<form method="post" action="index.php?m=licensing">
    <div class="row">
        <div class="col-sm-9 col-md-10">
            <div class="input-group input-group-lg">
                <span class="input-group-addon" id="sizing-addon1">http://</span>
                <input type="text" name="domain" class="form-control" placeholder="support.domain.com" value="{$domain}">
            </div>
        </div>
        <div class="col-sm-3 col-md-2">
            <input type="submit" value="{$ADDONLANG.check}" class="btn btn-danger btn-lg btn-block" />
        </div>
    </div>
</form>

<br />

{if !$check}

    <h3>{$ADDONLANG.howToUse}:</h3>

    <ul>
        <li>{$ADDONLANG.enterDomain}</li>
        <li>{$ADDONLANG.domainExample}</li>
        <li>{$ADDONLANG.noWWW}</li>
    </ul>

{else}

    <h3>Search Results</h3>

    {if $results}

        <div class="alert alert-success text-center">

            <strong>{$ADDONLANG.licenseMatch}</strong><br />
            {$ADDONLANG.licenseMatchInfo}

        </div>

    {else}

        <div class="alert alert-warning text-center">

            <strong>{$ADDONLANG.noLicenseMatch}</strong><br />
            {$ADDONLANG.noLicenseMatchInfo}

        </div>

    {/if}

{/if}

<br />

<h2 class="text-center">{$ADDONLANG.thankYou}</h2>

<br />
<br />
<br />
