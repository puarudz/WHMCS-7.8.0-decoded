{$infobox}

<h2 class="pull-left">
    #{$tid} - {$subject}
    <select name="ticketstatus" id="ticketstatus" class="form-control select-inline">
        {foreach $statuses as $statusitem}
            <option value="{$statusitem.title}"{if $statusitem.title eq $status} selected{/if} style="color:{$statusitem.color}">{$statusitem.title}</option>
        {/foreach}
    </select>
    <a href="supporttickets.php#" onclick="$('#ticketstatus').val('Closed');$('#ticketstatus').trigger('change');return false">{$_ADMINLANG.global.close}</a>
</h2>

<span class="ticketlastreply">{$_ADMINLANG.support.lastreply}: {$lastreply}</span>
<input type="hidden" id="lastReplyId" value="{$lastReplyId}" />
<input type="hidden" id="currentSubject" value="{$subject}" />
<input type="hidden" id="currentCc" value="{$cc}" />
<input type="hidden" id="currentUserId" value="{$userid}" />
<input type="hidden" id="currentStatus" value="{$status}" />

<div class="clearfix"></div>

<div class="client-notes">
    {foreach $clientnotes as $note}
        <div class="panel panel-warning">
            <div class="panel-heading">
                {$note.adminuser}
                <div class="pull-right">
                    {$note.modified}
                    &nbsp;
                    <a href="clientsnotes.php?userid={$note.userid}&action=edit&id={$note.id}" class="btn btn-default btn-xs">
                        <i class="fas fa-pencil-alt"></i>
                        {$_ADMINLANG.global.edit}
                    </a>
                </div>
            </div>
            <div class="panel-body">
                {$note.note}
            </div>
        </div>
    {/foreach}
</div>

{foreach $addons_html as $addon_html}
    <div class="addon-html-output-container">
        {$addon_html}
    </div>
{/foreach}

<div class="alert alert-info text-center{if !$replyingadmin} hidden{/if}" role="alert" id="replyingAdminMsg">
    {$replyingadmin.name} {$_ADMINLANG.support.viewedandstarted} @ {$replyingadmin.time}
</div>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tab0" role="tab" data-toggle="tab">{$_ADMINLANG.support.addreply}</a></li>
    <li><a href="#tab1" role="tab" data-toggle="tab">{$_ADMINLANG.support.addnote}</a></li>
    <li><a href="#tab2" role="tab" data-toggle="tab" onclick="loadTab(2, 'customfields', 0)">{$_ADMINLANG.setup.customfields}</a></li>
    <li><a href="#tab3" role="tab" data-toggle="tab" onclick="loadTab(3, 'tickets', 0)">{$_ADMINLANG.support.clienttickets}</a></li>
    <li><a href="#tab4" role="tab" data-toggle="tab" onclick="loadTab(4, 'clientlog', 0)">{$_ADMINLANG.support.clientlog}</a></li>
    <li><a href="#tab5" role="tab" data-toggle="tab">{$_ADMINLANG.fields.options}</a></li>
    <li><a href="#tab6" role="tab" data-toggle="tab" onclick="loadTab(6, 'ticketlog', 0)">{$_ADMINLANG.support.ticketlog}</a></li>
</ul>
<div class="tab-content admin-tabs">
  <div class="tab-pane active" id="tab0">

    <form method="post" action="{$smarty.server.PHP_SELF}?action=viewticket&id={$ticketid}" enctype="multipart/form-data" name="replyfrm" id="frmAddTicketReply" data-no-clear="true">
        <input type="hidden" name="postreply" value="1" />

        <textarea name="message" id="replymessage" rows="14" class="form-control bottom-margin-10">{if $signature}



{$signature}{/if}</textarea>

        <div class="row ticket-reply-edit-options">
            <div class="col-sm-3">
                <select name="deptid" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setDepartment} -</option>
                    {foreach $departments as $department}
                        <option value="{$department.id}">{$department.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-sm-3">
                <select name="flagto" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setAssignment} -</option>
                    <option value="0">{$_ADMINLANG.global.none}</option>
                    {foreach $staff as $staffmember}
                        <option value="{$staffmember.id}">{$staffmember.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-sm-3">
                <select name="priority" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setPriority} -</option>
                    <option value="High">{$_ADMINLANG.status.high}</option>
                    <option value="Medium">{$_ADMINLANG.status.medium}</option>
                    <option value="Low">{$_ADMINLANG.status.low}</option>
                </select>
            </div>
            <div class="col-sm-3">
                <select name="status" class="form-control selectize-select" data-value-field="id">
                    {foreach $statuses as $statusitem}
                        <option value="{$statusitem.title}" style="color:{$statusitem.color}"{if $statusitem.title eq "Answered"} selected{/if}>{$statusitem.title}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="ticket-reply-submit-options clearfix">
            <div class="pull-left">
                <button type="button" class="btn btn-default btns-padded" id="btnAttachFiles">
                    <i class="far fa-file"></i>
                    &nbsp;
                    {$_ADMINLANG.support.attachFiles}
                </button>
                <button type="button" class="btn btn-default btns-padded" id="insertpredef">
                    <i class="fas fa-pencil-alt"></i>
                    &nbsp;
                    {$_ADMINLANG.support.insertpredef}
                </button>
                <div class="dropdown btns-padded">
                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMoreOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <i class="fas fa-cog"></i>
                        {$_ADMINLANG.support.moreOptions}
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMoreOptions">
                        <li><a href="#" id="btnInsertKbArticle">{$_ADMINLANG.support.insertkblink}</a></li>
                        <li><a href="#" id="btnAddBillingEntry">{$_ADMINLANG.support.addbilling}</a></li>
                    </ul>
                </div>
            </div>
            <div class="pull-right">
                <button type="submit" class="btn btn-primary pull-right" name="postreply" id="btnPostReply" value="true">
                    <i class="fas fa-reply"></i>
                    {$_ADMINLANG.support.reply}
                </button>
                <div class="return-to-ticket-list">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="returntolist" value="1"{if $returnToList == true} checked{/if} />
                        {$_ADMINLANG.support.returnToTicketList}
                    </label>
                </div>
            </div>
            <div class="clearfix"></div>
            <div id="ticketPredefinedReplies" class="inner-container">
                <div class="predefined-replies-search">
                    <input type="text" id="predefq" size="25" value="{$_ADMINLANG.global.search}" onfocus="this.value=(this.value=='{$_ADMINLANG.global.search}') ? '' : this.value;" onblur="this.value=(this.value=='') ? '{$_ADMINLANG.global.search}' : this.value;" />
                </div>
                <div id="prerepliescontent">
                    {$predefinedreplies}
                </div>
            </div>
            <div id="ticketReplyAttachments" class="inner-container">
                <div class="row">
                    <div class="col-sm-9">
                        <input type="file" name="attachments[]" class="form-control" />
                        <div id="fileuploads"></div>
                    </div>
                    <div class="col-sm-3">
                        <a href="#" id="add-file-upload" class="btn btn-success btn-block add-file-upload" data-more-id="fileuploads"><i class="fas fa-plus"></i> {$_ADMINLANG.support.addmore}</a>
                    </div>
                </div>
            </div>
            <div id="ticketReplyBillingEntry" class="inner-container">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel">
                            {$_ADMINLANG.support.addbilling}
                        </td>
                        <td class="fieldarea">
                            <div class="form-inline">
                                <input type="text" name="billingdescription" size="50" placeholder="{$_ADMINLANG.support.toinvoicedes}" class="form-control" /> @ <input type="text" name="billingamount" size="10" placeholder="{$_ADMINLANG.fields.amount}" class="form-control" />
                                <select name="billingaction" class="form-control select-inline">
                                    <option value="3" /> {$_ADMINLANG.billableitems.invoiceimmediately}</option>
                                    <option value="0" /> {$_ADMINLANG.billableitems.dontinvoicefornow}</option>
                                    <option value="1" /> {$_ADMINLANG.billableitems.invoicenextcronrun}</option>
                                    <option value="2" /> {$_ADMINLANG.billableitems.addnextinvoice}</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </form>

  </div>
  <div class="tab-pane" id="tab1">

    <form method="post" action="{$smarty.server.PHP_SELF}?action=viewticket&id={$ticketid}" enctype="multipart/form-data" id="frmAddTicketNote" data-no-clear="false">
        <input type="hidden" name="postaction" value="note" />

        <textarea name="message" id="replynote" rows="14" class="form-control"></textarea>

        <div class="row ticket-reply-edit-options">
            <div class="col-sm-3">
                <select name="deptid" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setDepartment} -</option>
                    {foreach $departments as $department}
                        <option value="{$department.id}">{$department.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-sm-3">
                <select name="flagto" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setAssignment} -</option>
                    <option value="0">{$_ADMINLANG.global.none}</option>
                    {foreach $staff as $staffmember}
                        <option value="{$staffmember.id}">{$staffmember.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-sm-3">
                <select name="priority" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setPriority} -</option>
                    <option value="High">{$_ADMINLANG.status.high}</option>
                    <option value="Medium">{$_ADMINLANG.status.medium}</option>
                    <option value="Low">{$_ADMINLANG.status.low}</option>
                </select>
            </div>
            <div class="col-sm-3">
                <select name="status" class="form-control selectize-select" data-value-field="id">
                    <option value="nochange" selected>- {$_ADMINLANG.support.setStatus} -</option>
                    {foreach $statuses as $statusitem}
                        <option value="{$statusitem.title}" style="color:{$statusitem.color}">{$statusitem.title}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="ticket-reply-submit-options clearfix">
            <div class="pull-left">
                <button type="button" class="btn btn-default btns-padded" id="btnNoteAttachFiles">
                    <i class="far fa-file"></i>
                    &nbsp;
                    {$_ADMINLANG.support.attachFiles}
                </button>
            </div>
            <div class="pull-right">
                <button type="submit" class="btn btn-primary pull-right" name="postreply" id="btnAddNote">
                    <i class="fas fa-reply"></i>
                    {$_ADMINLANG.support.addnote}
                </button>
                <div class="return-to-ticket-list">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="returntolist" value="1"{if $returnToList == true} checked{/if} />
                        {$_ADMINLANG.support.returnToTicketList}
                    </label>
                </div>
            </div>
            <div class="clearfix"></div>
            <div id="ticketNoteAttachments" class="inner-container">
                <div class="row">
                    <div class="col-sm-9">
                        <input type="file" name="attachments[]" class="form-control" />
                        <div id="note-file-uploads"></div>
                    </div>
                    <div class="col-sm-3">
                        <a href="#" id="add-note-file-upload" class="btn btn-success btn-block add-file-upload" data-more-id="note-file-uploads">
                            <i class="fas fa-plus"></i>
                            {$_ADMINLANG.support.addmore}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

  </div>
  <div class="tab-pane" id="tab2">

    <img src="images/loading.gif" align="top" /> {$_ADMINLANG.global.loading}

  </div>
  <div class="tab-pane" id="tab3">

    <img src="images/loading.gif" align="top" /> {$_ADMINLANG.global.loading}

  </div>
  <div class="tab-pane" id="tab4">

    <img src="images/loading.gif" align="top" /> {$_ADMINLANG.global.loading}

  </div>
  <div class="tab-pane" id="tab5">
    <form method="post" action="{$smarty.server.PHP_SELF}?action=viewticket&id={$ticketid}" id="frmTicketOptions">
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="15%" class="fieldlabel">
                    {$_ADMINLANG.support.department}
                </td>
                <td class="fieldarea">
                    <select name="deptid" class="form-control select-inline">
                        {foreach $departments as $department}
                            <option value="{$department.id}"{if $department.id eq $deptid} selected{/if}>{$department.name}</option>
                        {/foreach}
                    </select>
                </td>
                <td width="15%" class="fieldlabel">
                    {$_ADMINLANG.fields.clientname}
                </td>
                <td class="fieldarea">
                    {$userSearchDropdown}
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">
                    {$_ADMINLANG.fields.subject}
                </td>
                <td class="fieldarea">
                    <input type="text" name="subject" value="{$subject}" class="form-control input-400">
                </td>
                <td class="fieldlabel">
                    {$_ADMINLANG.support.flag}
                </td>
                <td class="fieldarea">
                    <select name="flagto" class="form-control select-inline">
                        <option value="0">{$_ADMINLANG.global.none}</option>
                            {foreach $staff as $staffmember}
                                <option value="{$staffmember.id}"{if $staffmember.id eq $flag} selected{/if}>{$staffmember.name}</option>
                            {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">
                    {$_ADMINLANG.fields.status}
                </td>
                <td class="fieldarea">
                    <select name="status" class="form-control select-inline">
                        {foreach from=$statuses item=statusitem}
                            <option{if $statusitem.title eq $status} selected{/if} style="color:{$statusitem.color}">{$statusitem.title}</option>
                        {/foreach}
                    </select>
                </td>
                <td class="fieldlabel">
                    {$_ADMINLANG.support.priority}
                </td>
                <td class="fieldarea">
                    <select name="priority" class="form-control select-inline">
                        <option value="High"{if $priority eq "High"} selected{/if}>{$_ADMINLANG.status.high}</option>
                        <option value="Medium"{if $priority eq "Medium"} selected{/if}>{$_ADMINLANG.status.medium}</option>
                        <option value="Low"{if $priority eq "Low"} selected{/if}>{$_ADMINLANG.status.low}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">
                    {$_ADMINLANG.support.ccrecipients}
                </td>
                <td class="fieldarea">
                    <input type="text" id="inputTicketCc" name="cc" value="{$cc}" class="form-control selectize-tags" placeholder="{lang key="global.none"}">
                </td>
                <td class="fieldlabel">
                    {$_ADMINLANG.support.mergeticket}
                </td>
                <td class="fieldarea">
                    <input type="text" name="mergetid"  class="form-control input-150 input-inline"> ({$_ADMINLANG.support.notocombine})
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button id="btnSaveChanges" type="submit" class="btn btn-primary" value="save">
                <i class="fas fa-save"></i>
                {lang key='global.savechanges'}
            </button>
            <input type="reset" value="{$_ADMINLANG.global.cancelchanges}" class="btn btn-default" />
        </div>
    </form>
  </div>
  <div class="tab-pane" id="tab6">

    <img src="images/loading.gif" align="top" /> {$_ADMINLANG.global.loading}

  </div>
</div>

{if $relatedservices}
    <div class="tablebg">
        <table class="datatable" id="relatedservicestbl" width="100%" border="0" cellspacing="1" cellpadding="3">
            <tr>
                <th>{$_ADMINLANG.fields.product}</th>
                <th>{$_ADMINLANG.fields.amount}</th>
                <th>{$_ADMINLANG.fields.billingcycle}</th>
                <th>{$_ADMINLANG.fields.signupdate}</th>
                <th>{$_ADMINLANG.fields.nextduedate}</th>
                <th>{$_ADMINLANG.fields.status}</th>
            </tr>
            {foreach $relatedservices as $relatedservice}
                <tr{if $relatedservice.selected} class="rowhighlight"{/if}>
                    <td>{$relatedservice.name}</td>
                    <td>{$relatedservice.amount}</td>
                    <td>{$relatedservice.billingcycle}</td>
                    <td>{$relatedservice.regdate}</td>
                    <td>{$relatedservice.nextduedate}</td>
                    <td>{$relatedservice.status}</td>
                </tr>
            {/foreach}
        </table>
    </div>
    {if $relatedservicesexpand}
        <div id="relatedservicesexpand">
            <a href="#" onclick="expandRelServices();return false" class="btn btn-default btn-xs">
                <i class="fas fa-plus"></i>
                {$_ADMINLANG.support.expand}
            </a>
        </div>
    {/if}
{else}
    <br />
{/if}

<form method="post" action="supporttickets.php" id="ticketreplies">
    <input type="hidden" name="id" value="{$ticketid}" />
    <input type="hidden" name="action" value="split" />

    <div id="ticketreplies">

        {foreach $replies as $reply}
            <div class="reply {if $reply.note} note{elseif $reply.admin} staff{/if}">
                <div class="leftcol">
                    <div class="submitter">
                        {if $reply.admin}
                            <div class="name">{$reply.admin}</div>
                            <div class="title">
                                {if $reply.note}
                                    {$_ADMINLANG.support.privateNote}
                                {else}
                                    {$_ADMINLANG.support.staff}
                                {/if}
                            </div>
                            {if $reply.rating}
                                <br />{$reply.rating}<br /><br />
                            {/if}
                        {else}
                            <div class="name">{$reply.clientname}</div>
                            <div class="title">
                                {if $reply.contactid}
                                    {$_ADMINLANG.fields.contact}
                                {elseif $reply.userid}
                                    {$_ADMINLANG.fields.client}
                                {else}
                                    <a href="mailto:{$reply.clientemail}">{$reply.clientemail}</a>
                                {/if}
                            </div>
                            {if !$reply.userid && !$reply.contactid}
                                <a href="supporttickets.php?action=viewticket&amp;id={$ticketid}&amp;blocksender=true&amp;token={$csrfToken}" class="btn btn-xs btn-small">{$_ADMINLANG.support.blocksender}</a>
                            {/if}
                        {/if}
                    </div>
                    <div class="tools">
                        <div class="editbtns{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}">
                            <img src="../assets/img/spinner.gif" width="16" height="16" class="saveSpinner" style="display: none" />
                            {if !$reply.note}
                                <input type="button" value="{$_ADMINLANG.global.edit}" onclick="editTicket('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-default" />
                            {/if}
                            {if $deleteperm}
                                <input type="button" value="{$_ADMINLANG.global.delete}" onclick="{if $reply.id}{if $reply.note}doDeleteNote('{$reply.id}');{else}doDeleteReply('{$reply.id}');{/if}{else}doDeleteTicket();{/if}" class="btn btn-xs btn-small btn-danger" />
                            {/if}
                        </div>
                        <div class="editbtns{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}" style="display:none">
                            <img src="../assets/img/spinner.gif" width="16" height="16" class="saveSpinner" style="display: none" />
                            <input type="button" value="{$_ADMINLANG.global.save}" onclick="editTicketSave('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-success" />
                            <input type="button" value="{$_ADMINLANG.global.cancel}" onclick="editTicketCancel('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-default" />
                        </div>
                    </div>
                </div>
                <div class="rightcol">
                    {if !$reply.note}
                        <div class="quoteicon">
                            <a href="#" onClick="quoteTicket('{if !$reply.id}{$ticketid}{/if}','{if $reply.id}{$reply.id}{/if}'); return false;"><img src="images/icons/quote.png" border="0" /></a>
                            {if $reply.id}
                                <input type="checkbox" name="rids[]" value="{$reply.id}" />
                            {/if}
                        </div>
                    {/if}
                    <div class="postedon">
                        {if $reply.note}
                            {$reply.admin} {$_ADMINLANG.support.postedANote}
                        {else}
                            {$_ADMINLANG.support.posted}
                        {/if}
                        {if $reply.friendlydate}
                            {$_ADMINLANG.support.on} {$reply.friendlydate}
                        {else}
                            {$_ADMINLANG.support.today}
                        {/if}
                        {$_ADMINLANG.support.at} {$reply.friendlytime}
                    </div>
                    <div class="msgwrap" id="content{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}">
                        <div class="message markdown-content">
                            {$reply.message}
                        </div>
                        {if $reply.numattachments && !$reply.attachments_removed}
                            <br />
                            <strong>{$_ADMINLANG.support.attachments}</strong>
                            <br /><br />
                            {foreach $reply.attachments as $num => $attachment}
                                {if $thumbnails}
                                    <div class="ticketattachmentcontainer">
                                        <a href="../{$attachment.dllink}"{if $attachment.isImage} data-lightbox="image-{if $reply.id}{if $reply.note}n{else}r{/if}{$reply.id}{else}t{$ticketid}{/if}"{/if}>
                                            <span class="ticketattachmentthumbcontainer">
                                                <img src="../includes/thumbnail.php?{if $reply.id}{if $reply.note}nid={else}rid={/if}{$reply.id}{else}tid={$ticketid}{/if}&amp;i={$num}" class="ticketattachmentthumb" />
                                            </span>
                                            <span class="ticketattachmentinfo">
                                                <img src="images/icons/attachment.png" align="top" />
                                                {$attachment.filename}
                                            </span>
                                        </a>
                                        <div class="ticketattachmentlinks">
                                            <small>
                                                {if $attachment.isImage}
                                                    <a href="../{$attachment.dllink}">{lang key='support.download'}</a> |
                                                {/if}
                                                <a href="{$attachment.deletelink}" onclick="return confirm('{$_ADMINLANG.support.delattachment|escape:'javascript'}')" style="color:#cc0000">
                                                    {$_ADMINLANG.support.remove}
                                                </a>
                                            </small>
                                        </div>
                                    </div>
                                {else}
                                    <a href="../{$attachment.dllink}"{if $attachment.isImage} data-lightbox="image-{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}"{/if}>
                                        <img src="images/icons/attachment.png" align="absmiddle" />
                                        {$attachment.filename}
                                    </a>
                                    <small>
                                        {if $attachment.isImage}
                                            <a href="../{$attachment.dllink}">{lang key='support.download'}</a> |
                                        {/if}
                                        <a href="{$attachment.deletelink}" onclick="return confirm('{$_ADMINLANG.support.delattachment|escape:'javascript'}')" style="color:#cc0000">
                                            {$_ADMINLANG.support.remove}
                                        </a>
                                    </small>
                                    <br />
                                {/if}
                            {/foreach}
                            <div class="clear"></div>
                        {elseif $reply.numattachments && $reply.attachments_removed}
                            <br />
                            <strong>
                                {$_ADMINLANG.support.attachments}
                            </strong>
                            ({lang key='support.attachmentsRemoved'})
                            <br /><br />
                            <ul>
                                {foreach $reply.attachments as $num => $attachment}
                                    <li>
                                        {$attachment.filename}
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        {/foreach}
    </div>

    <a href="supportticketsprint.php?id={$ticketid}" target="_blank" class="btn btn-default btn-xs">
        <i class="fas fa-print"></i>
        {$_ADMINLANG.support.viewprintable}
    </a>
    {if $repliescount > 1}
        <span style="float:right;">
            <input type="button" value="{$_ADMINLANG.support.splitticketdialogbutton}" onclick="$('#modalsplitTicket').modal('show')" class="btn btn-xs" />
        </span>
    {/if}

    {$splitticketdialog}

    <input type="hidden" name="splitdeptid" id="splitdeptid" />
    <input type="hidden" name="splitsubject" id="splitsubject" />
    <input type="hidden" name="splitpriority" id="splitpriority" />
    <input type="hidden" name="splitnotifyclient" id="splitnotifyclient" />
</form>

<script src="../assets/js/lightbox.js"></script>
