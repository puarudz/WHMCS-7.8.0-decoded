{if $status == "Reissued"}
    <div class="alert-message success">
        {$LANG.licensingaddon.reissuestatusmsg}
    </div>
{/if}

<p>
    <h4>{$LANG.licensingaddon.licensekey}:</h4>
    {$licensekey}
</p>

<p>
    <h4>{$LANG.licensingaddon.validdomains}:</h4>
    <textarea rows="2" style="width:60%;" readonly="true">{$validdomain}</textarea>
</p>

<p>
    <h4>{$LANG.licensingaddon.validips}:</h4>
    <textarea rows="2" style="width:60%;" readonly="true">{$validip}</textarea>
</p>

<p>
    <h4>{$LANG.licensingaddon.validdirectory}:</h4>
    <textarea rows="2" style="width:60%;" readonly="true">{$validdirectory}</textarea>
</p>

<p>
    <h4>{$LANG.licensingaddon.status}:</h4>
    {$status}
</p>

{if $allowreissues && $status == "Active"}
    <form method="post" action="clientarea.php?action=productdetails">
        <input type="hidden" name="id" value="{$id}" />
        <input type="hidden" name="serveraction" value="custom" />
        <input type="hidden" name="a" value="reissue" />
        <p align="center">
            <br />
            <input type="submit" value="{$LANG.licensingaddon.reissue}" class="btn btn-success" />
        </p>
    </form>
{/if}
