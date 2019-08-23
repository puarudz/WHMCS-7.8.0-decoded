<link href="modules/addons/project_management/css/client.css" rel="stylesheet" type="text/css" />

<div class="projectmanagement">

<h2>&nbsp;&nbsp; &raquo; &nbsp; {$project.title}</h2>

<div class="infobar">

<table width="100%">
<tr>
<th>{$_lang.created}</th>
{if in_array('staff',$features)}<th>{$_lang.assignedto}</th>{/if}
<th>{$_lang.duedate}</th>
{if in_array('time',$features)}<th>{$_lang.totaltime}</th>{/if}
<th style="border:0;">{$_lang.status}</th>
</tr>
<tr>
<td>{$project.created}</td>
{if in_array('staff',$features)}<td>{$project.adminname}</td>{/if}
<td>{$project.duedate}</td>
{if in_array('time',$features)}<td>{$project.totaltime}</td>{/if}
<td style="border:0;">{$project.status}</td>
</tr>
</table>

</div>

{if in_array('tasks',$features)}
<div class="rightcol">
{/if}

<h2>{$_lang.associatedtickets}</h2>

{if $tickets}
    {foreach from=$tickets item=ticket}
        <p> &raquo; <a href="viewticket.php?tid={$ticket.tid}&c={$ticket.c}">#{$ticket.tid} - {$ticket.title}</a></p>
    {/foreach}
{else}
    <p>{$_lang.none}</p>
{/if}

<br />

<h2>{$_lang.associatedinvoices}</h2>

{if $invoices}
    {foreach from=$invoices item=invoice}
        <p> &raquo; <a href="viewinvoice.php?id={$invoice.id}">{$LANG.invoicenumber}{$invoice.id}</a> - {$invoice.total} <span class="label {$invoice.rawstatus}">{$invoice.status}</span></p>
    {/foreach}
{else}
    <p>{$_lang.none}</p>
{/if}

<br />

{if in_array('time',$features)}

<h2>{$_lang.timetracking}</h2>

<div class="totaltime">{$project.totaltime} <small>{$_lang.hours}</small></div>

<p><a href="#" onclick="$('.timedetail').fadeToggle();return false">{$_lang.showhidetimelogs}</a></p>

<br />

{/if}

{if in_array('files',$features)}

<h2>{$_lang.fileuploads}</h2>

{if $attachments}
    {foreach from=$attachments key=attachnum item=attachment}
        <p><img src="images/txt.png" /> <a href="modules/addons/project_management/project_management.php?action=dl&projectid={$project.id}&i={$attachnum}">{$attachment.filename}</a></p>
    {/foreach}
{else}
    <p>{$_lang.none}</p>
{/if}

<br />
{if $fileUploadDisallowed}
    <div class="errorbox">
        {$_lang.fileuploaddisallowed}
    </div>
    <br />
{/if}
<div id="uploadfile">
<form method="post" action="{$smarty.server.PHP_SELF}?m=project_management&a=view" enctype="multipart/form-data">
<input type="hidden" name="id" value="{$project.id}" />
<input type="hidden" name="upload" value="true" />
<p><input type="file" name="attachments[]" style="width:70%;" /></p>
<p><input type="submit" value="{$_lang.upload}" class="btn btn-success" /></p>
</form>
{$_lang.allowedExtensions}{$allowedExtensions}
</div>
<p><input type="button" value="{$_lang.addfile}" class="btn btn-primary" onclick="$(this).fadeOut();$('#uploadfile').fadeIn();" /></p>

{/if}

{if in_array('tasks',$features)}
</div>

<div class="leftcol">

<h2>{$_lang.tasks}</h2>

<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th width="25" class="textcenter">#</th>
            <th>{$_lang.taskdetail}</th>
        </tr>
    </thead>
    <tbody>
{foreach from=$tasks key=tasknum item=task}
        <tr>
            <td class="textcenter">{$tasknum}</td>
            <td>{if $task.completed}<span class="label active">{$_lang.completed}</span>{/if} {$task.task}{if $task.duein} <span class="taskdue">{$task.duein}, {$task.duedate}</span>{/if}{if $task.times}
<table class="table table-striped table-framed timedetail">
    <thead>
        <tr>
            <th class="textcenter">{$_lang.starttime}</th>
            <th class="textcenter">{$_lang.stoptime}</th>
            <th class="textcenter">{$_lang.duration}</th>
        </tr>
    </thead>
    <tbody>
{foreach from=$task.times item=time}
        <tr>
            <td class="textcenter">{$time.start}</td>
            {if $time.end}<td class="textcenter">{$time.end}</td>
            <td class="textcenter">{$time.duration}</td>
            {else}<td colspan="2" class="textcenter">{$_lang.inprogress}</td>
            {/if}
        </tr>
{/foreach}
        <tr>
            <td colspan="2" class="textright"><strong>{$_lang.total}</strong></td>
            <td class="textcenter"><strong>{$task.totaltime}</strong></td>
        </tr>
    </tbody>
</table>
{/if}</td>
        </tr>
{foreachelse}
        <tr>
            <td colspan="6" class="textcenter">{$LANG.norecordsfound}</td>
        </tr>
{/foreach}
    </tbody>
</table>

{if in_array('addtasks',$features)}
<form method="post" action="index.php?m=project_management&a=view">
<input type="hidden" name="id" value="{$project.id}" />
<p>{$_lang.addtask}: <input type="text" name="newtask" style="width:60%;" /> <input type="submit" value="{$_lang.add}" class="btn btn-primary" /></p>
</form>
{/if}

<br />

<p>{$_lang.projectguidance}</p>

</div>
{/if}

<div class="clear"></div>

<br />

</div>

<form method="post" action="index.php?m=project_management">
    <p><input type="submit" value="{$LANG.clientareabacklink}" class="btn" /></p>
</form>
