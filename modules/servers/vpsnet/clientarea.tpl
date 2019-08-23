{if $vpsnet.allowmanagement}

{literal}
<style>
#vpsnetcont {
    margin: 10px;
    padding: 10px;
    background-color: #fff;
    -moz-border-radius: 10px;
    -webkit-border-radius: 10px;
    -o-border-radius: 10px;
    border-radius: 10px;
}
#vpsnetcont table {
    width: 100%;
}
#vpsnetcont table tr th {
    padding: 4px;
    background-color: #1A4D80;
    color: #fff;
    font-weight: bold;
    text-align: center;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    -o-border-radius: 3px;
    border-radius: 3px;
}
#vpsnetcont table tr td {
    padding: 4px;
    border-bottom: 1px solid #efefef;
}
#vpsnetcont table tr td.fieldlabel {
    width: 175px;
    text-align: right;
    font-weight: bold;
    background-color: #efefef;
}
#vpsnetcont .tools {
    padding: 10px 0 0 15px;
}
</style>
{/literal}

{if $bwgraphs}

{literal}
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          {/literal}{$vpsnet.datatable}{literal}
        ]);

        var options = {
          title: "Network Usage - Hourly",
          hAxis: {title: "Time Period"},
          vAxis: {title: "Bandwidth (GB)"},
          legend: {position: "in"}
        };

        var chart = new google.visualization.AreaChart(document.getElementById("bwchart"));
        chart.draw(data, options);
      }
    </script>
{/literal}

<div id="vpsnetcont">
<div id="bwchart" style="width: 100%; height: 400px;"></div>
</div>

{elseif $vpsnet.managebackups}

<div id="vpsnetcont">
The list below shows all the backups for your virtual machine, along with the last time each of these backups was run.<br /><br />
<table cellspacing="1">
<tr><th>Type</th><th>State</th><th>Date/Time</th><th>Size</th><th>Restore</th><th>Delete</th></tr>
{foreach from=$backups item=backup}
<tr><td>{$backup.type}</td><td>{$backup.state}</td><td>{$backup.lastupdated}</td><td>{$backup.size}</td><td><a href="clientarea.php?action=productdetails&modop=custom&a=restorebackup&id={$serviceid}&addonid={$addonid}&bid={$backup.id}" onclick="if (confirm('Are you sure you wish to restore this backup?')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/backup.png" align="absmiddle" /></a></td><td><a href="clientarea.php?action=productdetails&modop=custom&a=deletebackup&id={$serviceid}&addonid={$addonid}&bid={$backup.id}" onclick="if (confirm('Are you sure you wish to delete this backup?')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/deletebackup.png" align="absmiddle" /></a></td></tr>
{foreachelse}
<tr><td colspan="6">No Backups Found</td></tr>
{/foreach}
</table>
<div class="tools">
<a href="clientarea.php?action=productdetails&modop=custom&a=snapshotbackup&id={$serviceid}&addonid={$addonid}" onclick="if (confirm('Are you sure you want to create a new snapshot?')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/backup.png" align="absmiddle" /> Create a new Snapshot</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&rsyncbackups=1&id={$serviceid}&addonid={$addonid}"><img src="./modules/servers/vpsnet/img/restore.png" align="absmiddle" /> Rsync Backups</a>
</div>
</div>

{elseif $vpsnet.rsyncbackups}

<div id="vpsnetcont">
<table cellspacing="1">
<tr><td class="fieldlabel">Username</td><td>{$rsync.username}</td></tr>
<tr><td class="fieldlabel">Password</td><td>{$rsync.password}</td></tr>
<tr><td class="fieldlabel">Quota</td><td>{$rsync.quota}</td></tr>
</table>
<div class="tools">
<a href="clientarea.php?action=productdetails&modop=custom&a=snapshotbackup&id={$serviceid}&addonid={$addonid}" onclick="if (confirm('Are you sure you want to create a new snapshot?')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/backup.png" align="absmiddle" /> Create a new Snapshot</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&rsyncbackups=1&id={$serviceid}&addonid={$addonid}"><img src="./modules/servers/vpsnet/img/backup.png" align="absmiddle" /> Rsync Backups</a>
</div>
</div>

{else}

<div id="vpsnetcont">
<table cellspacing="1">
<tr><td class="fieldlabel">Hostname</td><td>{$vpsnet.hostname}</td><td class="fieldlabel">Domain Name</td><td>{$vpsnet.domain_name}</td></tr>
<tr><td class="fieldlabel">Nodes</td><td>{$vpsnet.slices_count}</td><td class="fieldlabel">Cloud</td><td>{$vpsnet.cloudname}</td></tr>
<tr><td class="fieldlabel">Initial Root Password</td><td>{$vpsnet.password}</td><td class="fieldlabel">Backups Enabled</td><td>{if $vpsnet.backups_enabled}<img src="./modules/servers/vpsnet/img/tick.png" align="absmiddle" /> Yes{else}<img src="./modules/servers/vpsnet/img/cross.png" align="absmiddle" /> No{/if}</td></tr>
<tr><td class="fieldlabel">Status</td><td>{$vpsnet.runningstatus}</td><td class="fieldlabel">IP Address</td><td>{$vpsnet.primary_ip_address.ip_address.ip_address}</td></tr>
<tr><td class="fieldlabel">Monthly Bandwidth Used</td><td>{$vpsnet.$bwused}</td><td class="fieldlabel">Deployed Storage</td><td>{$vpsnet.deployed_disk_size}</td></tr>
<tr><td class="fieldlabel">Template</td><td>{$vpsnet.templatelabel}</td><td class="fieldlabel">Licenses</td><td>None</td></tr>
</table>
<div class="tools">

{if $vpsnet.power_action_pending}
<img src="./modules/servers/vpsnet/img/running.png" align="absmiddle" /> This VPS is currently running a task. Power Management Options Not Available Until Complete.
{else}

{if $running}
<a href="clientarea.php?action=productdetails&modop=custom&a=shutdown&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to shutdown this VPS?\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/shutdown.png" align="absmiddle" /> Shutdown</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=poweroff&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to force power off this VPS?\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/poweroff.png" align="absmiddle" /> Force Power Off</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=reboot&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to reboot this VPS?\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/reboot.png" align="absmiddle" /> Graceful Reboot</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=recover&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to reboot this VPS in recovery mode? Please note: in recovery mode the login is (root) and the password is (recovery).\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/recovery.png" align="absmiddle" /> Reboot in Recovery</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=rebuild&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you want to rebuilt network for this VPS? Your virtual machine will be rebooted and the network interfaces configuration file on this virtual machine will be regenerated.\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/restart.png" align="absmiddle" /> Rebuild Network</a>
{else}
<a href="clientarea.php?action=productdetails&modop=custom&a=poweron&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to start this VPS?\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/startup.png" align="absmiddle" /> Startup</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=recover&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to start this VPS in recovery mode? Please note: in recovery mode the login is (root) and the password is (recovery).\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/recovery.png" align="absmiddle" /> Startup in Recovery</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=rebuild&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you want to rebuilt network for this VPS? Your virtual machine will be rebooted and the network interfaces configuration file on this virtual machine will be regenerated.\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/restart.png" align="absmiddle" /> Rebuild Network</a>
<a href="clientarea.php?action=productdetails&modop=custom&a=terminate&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you wish to delete this VPS? Please note: recovery is only possible for up to 12 hours after deletion, and only your last 3 deleted VPS\'s will be available for recovery.\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/delete.png" align="absmiddle" /> Delete VPS</a>&nbsp;&nbsp;
<a href="clientarea.php?action=productdetails&modop=custom&a=reinstall&id={$serviceid}&addonid={$addonid}" onclick="if (confirm(\'Are you sure you want to re-install this VPS?\')) {ldelim} return true; {rdelim} return false;"><img src="./modules/servers/vpsnet/img/restart.png" align="absmiddle" /> Re-install VPS</a>
{/if}

{/if}

</div>
</div>

{/if}

<input type="button" value="Overview" onclick="window.location='clientarea.php?action=productdetails&id={$serviceid}&addonid={$addonid}'" class="btn" /> <input type="button" value="Manage Backups" onclick="window.location='clientarea.php?action=productdetails&id={$serviceid}&addonid={$addonid}&managebackups=1'" class="btn" /> <input type="button" value="Rsync Backups" onclick="window.location='clientarea.php?action=productdetails&id={$serviceid}&addonid={$addonid}&rsyncbackups=1'" class="btn" /> <input type="button" value="View CPU Graphs" onclick="window.location='clientarea.php?action=productdetails&id={$serviceid}&addonid={$addonid}&modop=custom&a=cpugraphs'" class="btn" /> <input type="button" value="View Bandwidth Graphs" onclick="window.location='clientarea.php?action=productdetails&id={$serviceid}&addonid={$addonid}&modop=custom&a=networkgraphs'" class="btn" />

{else}

<p>Management not possible due to missing VPS ID. Please contact support.</p>

{/if}
