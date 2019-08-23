<link href="modules/addons/project_management/css/client.css" rel="stylesheet" type="text/css" />

<div class="projectmanagement">

    <div class="infobar">
        <div class="hidden-xs">
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
        <div class="visible-xs text-center">
            {$_lang.created}: <strong>{$project.created}</strong><br />
            {if in_array('staff',$features)}{$_lang.assignedto}: <strong>{$project.adminname}</strong><br />{/if}
            {$_lang.duedate}: <strong>{$project.duedate}</strong><br />
            {if in_array('time',$features)}{$_lang.totaltime}: <strong>{$project.totaltime}</strong><br />{/if}
            {$_lang.status}: <strong>{$project.status}</strong>
        </div>
    </div>

    {if $taskAddSuccess}
        {include file="$template/includes/alert.tpl" type="success" msg=$_lang.taskaddedsuccess textcenter=true}
    {/if}

    {if $fileUploadSuccess}
        {include file="$template/includes/alert.tpl" type="success" msg=$_lang.fileuploadsuccess textcenter=true}
    {/if}

    {if $fileUploadFailed}
        {include file="$template/includes/alert.tpl" type="danger" msg=$_lang.fileuploadfailed textcenter=true}
    {/if}

    {if $fileUploadDisallowed}
        {include file="$template/includes/alert.tpl" type="danger" msg=$_lang.fileuploaddisallowed textcenter=true}
    {/if}

    {if in_array('tasks', $features)}
        <div class="row">
            <div class="col-sm-8">

                <h3><i class="fas fa-tasks"></i> {$_lang.tasks}</h3>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th>{$_lang.taskdetail}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$tasks key=tasknum item=task}
                            <tr>
                                <td class="text-center">{$tasknum}</td>
                                <td>
                                    {$task.task}
                                    {if $task.completed}
                                        <span class="label label-danger">{$_lang.completed}</span>
                                    {elseif $task.duein}
                                        <span class="taskdue">{$task.duein}, {$task.duedate}</span>
                                    {/if}
                                    {if $task.times}
                                        <table class="table table-striped table-framed timedetail">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">{$_lang.starttime}</th>
                                                    <th class="text-center">{$_lang.stoptime}</th>
                                                    <th class="text-center">{$_lang.duration}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {foreach from=$task.times item=time}
                                                        <tr>
                                                            <td class="text-center">{$time.start}</td>
                                                            {if $time.end}<td class="text-center">{$time.end}</td>
                                                            <td class="text-center">{$time.duration}</td>
                                                            {else}<td colspan="2" class="text-center">{$_lang.inprogress}</td>
                                                            {/if}
                                                        </tr>
                                                {/foreach}
                                                <tr>
                                                    <td colspan="2" class="text-right"><strong>{$_lang.total}</strong></td>
                                                    <td class="text-center"><strong>{$task.totaltime}</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>

                {if in_array('addtasks',$features)}
                    <form method="post" action="index.php?m=project_management&a=view">
                        <input type="hidden" name="id" value="{$project.id}" />
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fas fa-plus"></i> {$_lang.addtask}</span>
                            <input type="text" name="newtask" class="form-control">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">
                                    {$_lang.add}
                                </button>
                            </span>
                        </div>
                    </form>
                {/if}

                <br />

                {include file="$template/includes/alert.tpl" type="info" msg=$_lang.projectguidance}

            </div>
            <div class="col-sm-4">
    {/if}

                <h3><i class="fas fa-comments"></i> {$_lang.associatedtickets}</h3>
                {foreach from=$tickets item=ticket}
                    <p>
                        <a href="viewticket.php?tid={$ticket.tid}&c={$ticket.c}">#{$ticket.tid}</a><br />
                        <small>{$ticket.title}</small>
                    </p>
                {foreachelse}
                    <p class="text-muted">{$_lang.none}</p>
                {/foreach}

                <h3><i class="fas fa-credit-card"></i> {$_lang.associatedinvoices}</h3>
                {foreach from=$invoices item=invoice}
                    <p>
                        <a href="viewinvoice.php?id={$invoice.id}" target="_blank">
                            {$LANG.invoicenumber}{$invoice.id}
                        </a>
                        {if $invoice.status eq "Paid"}
                            <span class="label label-success">{$invoice.status}</span>
                        {elseif $invoice.status eq "Unpaid"}
                            <span class="label label-danger">{$invoice.status}</span>
                        {else}
                            <span class="label label-default">{$invoice.status}</span>
                        {/if}
                        <br />
                        <small>{$invoice.total}</small>
                    </p>
                {foreachelse}
                    <p class="text-muted">{$_lang.none}</p>
                {/foreach}

                {if in_array('time', $features)}
                    <div class="panel panel-default text-center">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="far fa-clock"></i> {$_lang.timetracking}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="totaltime">
                                {$project.totaltime}
                                <small>{$_lang.hours}</small>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <small>
                                <a href="#" onclick="showTimeLogs();return false">{$_lang.showhidetimelogs}</a>
                            </small>
                        </div>
                    </div>
                {/if}

                {if in_array('files', $features)}

                    <h3><i class="fas fa-cloud-download-alt"></i> {$_lang.fileuploads}</h3>
                    {foreach from=$attachments key=attachnum item=attachment}
                        <p><i class="fas fa-file"></i> <a href="modules/addons/project_management/project_management.php?action=dl&projectid={$project.id}&i={$attachnum}">{$attachment.filename}</a></p>
                    {foreachelse}
                        <p>{$_lang.none}</p>
                    {/foreach}

                    <p>
                        <button type="button" class="btn btn-default" onclick="showFileUpload()" id="btnFileUpload">
                            <i class="fas fa-plus"></i>
                            {$_lang.addfile}
                        </button>
                    </p>

                    <div id="containerUploadFile" class="hidden">
                        <form method="post" action="{$smarty.server.PHP_SELF}?m=project_management&a=view" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="{$project.id}" />
                            <input type="hidden" name="upload" value="true" />
                            <p><input type="file" name="attachments[]" class="form-control" /></p>
                            <p><input type="submit" value="{$_lang.upload}" class="btn btn-success btn-block" /></p>
                        </form>
                        <small class="text-muted">
                            {$_lang.allowedExtensions}<br />
                            {$allowedExtensions}
                        </small>
                    </div>

                {/if}

    {if in_array('tasks', $features)}
            </div>
        </div>
    {/if}

</div>

<script>
function showFileUpload() {
    if (!jQuery("#containerUploadFile").is(":visible")) {
        jQuery('#containerUploadFile').hide().removeClass('hidden');
    }
    jQuery('#containerUploadFile').slideToggle();
}
function showTimeLogs() {
    jQuery('.timedetail').fadeToggle();
}
</script>
