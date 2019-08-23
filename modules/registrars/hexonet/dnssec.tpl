<h3 style="margin-bottom:25px;">DNSSEC Management</h3>

{if $successful}
    <div class="alert alert-success text-center">
        <p>{$LANG.changessavedsuccessfully}</p>
    </div>
{/if}

{if $error}
    <div class="alert alert-danger text-center">
        <p>{$error}</p>
    </div>
{/if}

<form method="POST" action="{$smarty.server.PHP_SELF}">
<input type="hidden" name="action" value="domaindetails">
<input type="hidden" name="id" value="{$domainid}">
<input type="hidden" name="modop" value="custom">
<input type="hidden" name="a" value="dnssec">
<input type="hidden" name="submit" value="1">

<h4>DS records</h4>
<table class="table table-striped">
    <thead>
        <tr>
            <th style="width:100px;">Key Tag</th>
            <th style="width:100px;">Algorithm</th>
            <th style="width:100px;">Digest Type</th>
            <th>Digest</th>
        </tr>
    </thead>
    <tbody>
        {foreach item=ds from=$secdnsds name=secdnsds}
        <tr>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index}][keytag]" value="{$ds.keytag}"></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index}][alg]" value="{$ds.alg}"></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index}][digesttype]" value="{$ds.digesttype}"></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index}][digest]" value="{$ds.digest}"></td>
        </tr>
        {/foreach}
        <tr>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index+1}][keytag]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index+1}][alg]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index+1}][digesttype]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-DS[{$smarty.foreach.secdnsds.index+1}][digest]" value=""></td>
        </tr>
    </tbody>
</table>

<h4>KEY records</h4>
<table class="table table-striped">
    <thead>
        <tr>
            <th style="width:100px;">Flags</th>
            <th style="width:100px;">Protocol</th>
            <th style="width:100px;">Algorithm</th>
            <th>Public Key</th>
        </tr>
    </thead>
    <tbody>
        {foreach item=key from=$secdnskey name=secdnskey}
        <tr>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index}][flags]" value="{$key.flags}"></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index}][protocol]" value="{$key.protocol}"></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index}][alg]" value="{$key.alg}"></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index}][pubkey]" value="{$key.pubkey}"></td>
        </tr>
        {/foreach}
        <tr>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index+1}][flags]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index+1}][protocol]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index+1}][alg]" value=""></td>
            <td><input class="form-control" type="text" name="SECDNS-KEY[{$smarty.foreach.secdnskey.index+1}][pubkey]" value=""></td>
        </tr>
    </tbody>
</table>

<h4>MaxSigLife (optional)</h4>
<table class="table table-striped">
    <tr><td><input style="width:200px;" class="form-control" type="text" name="MAXSIGLIFE" value="{$maxsiglife}"></td></tr>
</table>

<!-- <h4>Urgent (Optional)</h4>
<div class="alert alert-info">This parameter is used to ask the server operator to complete and implement the update request with high priority.<br>
The use of this flag depends on the registry operator, many TLDs will silently ignore it.</div>
<table class="table table-striped">
    <tr><td>
        <select name="URGENT">
          <option value="0">No</option>
          <option value="1">YES</option>
        </select>
    </td></tr>
</table> -->

<p class="text-center">
    <input class="btn btn-large btn-primary" type="submit" value="{$LANG.clientareasavechanges}">
</p>

</form>
