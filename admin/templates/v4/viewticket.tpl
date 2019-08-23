{$infobox}

<h2>#{$tid} - {$subject} <select name="ticketstatus" id="ticketstatus" class="form-control select-inline" style="font-size:18px;">
{foreach from=$statuses item=statusitem}
<option{if $statusitem.title eq $status} selected{/if} style="color:{$statusitem.color}">{$statusitem.title}</option>
{/foreach}
</select> <a href="#" onclick="$('#ticketstatus').val('Closed');$('#ticketstatus').trigger('change');return false">{$_ADMINLANG.global.close}</a></h2>

<div class="ticketlastreply">{$_ADMINLANG.support.lastreply}: {$lastreply}</div>
<input type="hidden" id="lastReplyId" value="{$lastReplyId}" />
<input type="hidden" id="currentSubject" value="{$subject}" />
<input type="hidden" id="currentCc" value="{$cc}" />
<input type="hidden" id="currentUserId" value="{$userid}" />
<input type="hidden" id="currentStatus" value="{$status}" />

{if $clientnotes}
<div id="clientsimportantnotes">
{foreach from=$clientnotes item=note}
    <div class="panel panel-warning">
        <div class="panel-heading">
            {$note.adminuser}
            <div class="pull-right">
                {$note.modified}
            </div>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-11">
                    {$note.note}
                </div>
                <div class="col-md-1 pull-right text-right">
                    <a href="clientsnotes.php?userid={$note.userid}&action=edit&id={$note.id}">
                        <img src="images/edit.gif" width="16" height="16" align="absmiddle" />
                    </a>
                </div>
            </div>
        </div>
    </div>
{/foreach}
</div>
{/if}
<div class="pull-right">
    {if $watchingTicket}
        <button class="btn btn-danger btn-xs" id="watch-ticket" type="button" data-admin-full-name="{$adminFullName}" data-admin-id="{$adminid}" data-ticket-id="{$ticketid}" data-type="unwatch">
            {lang key="support.unwatchTicket"}
        </button>
    {else}
        <button class="btn btn-info btn-xs" id="watch-ticket" type="button" data-admin-full-name="{$adminFullName}" data-admin-id="{$adminid}" data-ticket-id="{$ticketid}" data-type="watch">
            {lang key="support.watchTicket"}
        </button>
    {/if}
</div>

{foreach from=$addons_html item=addon_html}
<div style="margin-bottom:15px;">{$addon_html}</div>
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
<textarea name="message" id="replymessage" rows="14" class="form-control" style="margin:0 0 10px 0;">{if $signature}



{$signature}{/if}</textarea>

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td class="fieldlabel">
            {$_ADMINLANG.fields.status}
        </td>
        <td class="fieldarea">
            <select name="status" class="form-control select-inline">
                {foreach from=$statuses item=statusitem}
                    <option value="{$statusitem.title}" style="color:{$statusitem.color}"{if $statusitem.title eq "Answered"} selected{/if}>{$statusitem.title}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">
            {$_ADMINLANG.support.priority}
        </td>
        <td class="fieldarea">
            <select name="priority" class="form-control select-inline">
                <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                <option value="High">{$_ADMINLANG.status.high}</option>
                <option value="Medium">{$_ADMINLANG.status.medium}</option>
                <option value="Low">{$_ADMINLANG.status.low}</option>
            </select>
        </td>
    </tr>
    <tr>
        <td width="15%" class="fieldlabel">
            {$_ADMINLANG.support.department}
        </td>
        <td class="fieldarea">
            <select name="deptid" class="form-control select-inline">
                <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                {foreach from=$departments item=department}
                    <option value="{$department.id}">{$department.name}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">
            {$_ADMINLANG.support.flag}
        </td>
        <td class="fieldarea">
            <select name="flagto" class="form-control select-inline">
                <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                <option value="0">{$_ADMINLANG.global.none}</option>
                {foreach from=$staff item=staffmember}
                    <option value="{$staffmember.id}">{$staffmember.name}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td width="15%" class="fieldlabel">
            Tools
        </td>
        <td class="fieldarea">
            <input type="button" value="{$_ADMINLANG.support.insertpredef}" class="btn btn-default" id="insertpredef" />
            <input type="button" value="{$_ADMINLANG.support.insertkblink}" class="btn btn-default" onclick="window.open('supportticketskbarticle.php','kbartwnd','width=500,height=400,scrollbars=yes')" />

            <div id="prerepliescontainer">
                <div class="box">
                    <div style="float:right;"><input type="text" id="predefq" size="25" value="{$_ADMINLANG.global.search}" onfocus="this.value=(this.value=='{$_ADMINLANG.global.search}') ? '' : this.value;" onblur="this.value=(this.value=='') ? '{$_ADMINLANG.global.search}' : this.value;" /></div>
                    <div id="prerepliescontent">{$predefinedreplies}</div>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">
            {$_ADMINLANG.support.attachments}
        </td>
        <td class="fieldarea">
            <div class="row">
                <div class="col-sm-8">
                    <input type="file" name="attachments[]" class="form-control" />
                    <div id="fileuploads"></div>
                </div>
                <div class="col-sm-4 top-margin-5">
                    <a href="#" id="add-file-upload" class="btn btn-success btn-xs add-file-upload" data-more-id="fileuploads"><i class="fas fa-plus"></i> {$_ADMINLANG.support.addmore}</a>
                </div>
            </div>
        </td>
    </tr>
    {if $userid}
    <tr>
        <td class="fieldlabel">
            {$_ADMINLANG.support.addbilling}
        </td>
        <td class="fieldarea">
            <input type="text" name="billingdescription" class="form-control input-250 input-inline" value="{$_ADMINLANG.support.toinvoicedes}" onfocus="if(this.value=='{$_ADMINLANG.support.toinvoicedes}')this.value=''" /> @ <input type="text" name="billingamount" class="form-control input-100 input-inline" value="{$_ADMINLANG.fields.amount}" />
            <select name="billingaction" class="form-control select-inline">
                <option value="3" /> {$_ADMINLANG.billableitems.invoiceimmediately}</option>
                <option value="0" /> {$_ADMINLANG.billableitems.dontinvoicefornow}</option>
                <option value="1" /> {$_ADMINLANG.billableitems.invoicenextcronrun}</option>
                <option value="2" /> {$_ADMINLANG.billableitems.addnextinvoice}</option>
            </select>
        </td>
    </tr>
    {/if}
</table>

    <div class="btn-container">
        <input type="submit" value="{$_ADMINLANG.support.addresponse} &raquo;" name="postreply" class="btn btn-primary" id="postreplybutton" />
        <div style="display:inline-block;">
            <label class="checkbox-inline">
                &nbsp;
                <input type="checkbox" name="returntolist" value="1"{if $returnToList == true} checked{/if} />
                {$_ADMINLANG.support.returnToTicketList}
            </label>
        </div>
    </div>

</form>

  </div>
  <div class="tab-pane" id="tab1">

<form method="post" action="{$smarty.server.PHP_SELF}?action=viewticket&id={$ticketid}" enctype="multipart/form-data" id="frmAddTicketNote" data-no-clear="false">
    <input type="hidden" name="postaction" value="note" />

<textarea name="message" id="replynote" rows="14" class="form-control"></textarea>

    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel">
                {$_ADMINLANG.fields.status}
            </td>
            <td class="fieldarea">
                <select name="status" class="form-control select-inline">
                    <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                    {foreach from=$statuses item=statusitem}
                        <option style="color:{$statusitem.color}">{$statusitem.title}</option>
                    {/foreach}
                </select>
            </td>
        <tr>
            <td class="fieldlabel">
                {$_ADMINLANG.support.priority}
            </td>
            <td class="fieldarea">
                <select name="priority" class="form-control select-inline">
                    <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                    <option value="High">{$_ADMINLANG.status.high}</option>
                    <option value="Medium">{$_ADMINLANG.status.medium}</option>
                    <option value="Low">{$_ADMINLANG.status.low}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="15%" class="fieldlabel">
                {$_ADMINLANG.support.department}
            </td>
            <td class="fieldarea">
                <select name="deptid" class="form-control select-inline">
                    <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                    {foreach from=$departments item=department}
                        <option value="{$department.id}">{$department.name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">
                {$_ADMINLANG.support.flag}
            </td>
            <td class="fieldarea">
                <select name="flagto" class="form-control select-inline">
                    <option value="nochange" selected>{$_ADMINLANG.global.nochange}</option>
                    <option value="0">{$_ADMINLANG.global.none}</option>
                    {foreach from=$staff item=staffmember}
                        <option value="{$staffmember.id}">{$staffmember.name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">
                {$_ADMINLANG.support.attachments}
            </td>
            <td class="fieldarea">
                <div class="row">
                    <div class="col-sm-8">
                        <input type="file" name="attachments[]" class="form-control" />
                        <div id="note-file-uploads"></div>
                    </div>
                    <div class="col-sm-4 top-margin-5">
                        <a href="#" id="add-note-file-upload" class="btn btn-success btn-xs add-file-upload" data-more-id="note-file-uploads"><i class="fas fa-plus"></i> {$_ADMINLANG.support.addmore}</a>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="btn-container">
        <input type="submit" value="{$_ADMINLANG.support.addnote}" class="btn btn-primary" name="postreply" />
        <div style="display:inline-block;">
            <label class="checkbox-inline">
                &nbsp;
                <input type="checkbox" name="returntolist" value="1"{if $returnToList == true} checked{/if} />
                {$_ADMINLANG.support.returnToTicketList}
            </label>
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
                    {foreach from=$departments item=department}
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
                <input type="text" name="subject" value="{$subject}" class="form-control input-300">
            </td>
            <td class="fieldlabel">
                {$_ADMINLANG.support.flag}
            </td>
            <td class="fieldarea">
                <select name="flagto" class="form-control select-inline">
                    <option value="0">{$_ADMINLANG.global.none}</option>
                    {foreach from=$staff item=staffmember}
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
                <input type="text" name="cc" value="{$cc}" class="form-control input-250 input-inline"> ({$_ADMINLANG.transactions.commaseparated})
            </td>
            <td class="fieldlabel">
                {$_ADMINLANG.support.mergeticket}
            </td>
            <td class="fieldarea">
                <input type="text" name="mergetid" class="form-control input-150 input-inline"> ({$_ADMINLANG.support.notocombine})
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
<tr><th>{$_ADMINLANG.fields.product}</th><th>{$_ADMINLANG.fields.amount}</th><th>{$_ADMINLANG.fields.billingcycle}</th><th>{$_ADMINLANG.fields.signupdate}</th><th>{$_ADMINLANG.fields.nextduedate}</th><th>{$_ADMINLANG.fields.status}</th></tr>
{foreach from=$relatedservices item=relatedservice}
<tr{if $relatedservice.selected} class="rowhighlight"{/if}><td>{$relatedservice.name}</td><td>{$relatedservice.amount}</td><td>{$relatedservice.billingcycle}</td><td>{$relatedservice.regdate}</td><td>{$relatedservice.nextduedate}</td><td>{$relatedservice.status}</td></tr>
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
{/if}

{if !$relatedservices}<br />{/if}

<form method="post" action="supporttickets.php" id="ticketreplies">
<input type="hidden" name="id" value="{$ticketid}" />
<input type="hidden" name="action" value="split" />

<div id="ticketreplies">

{foreach from=$replies item=reply}
<div class="reply{if $reply.note} note{elseif $reply.admin} staff{/if}">

<div class="leftcol">

<div class="submitter">

{if $reply.admin}

<div class="name">{$reply.admin}</div>
<div class="title">{if $reply.note}{$_ADMINLANG.support.privateNote}{else}{$_ADMINLANG.support.staff}{/if}</div>

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

{if !$reply.userid && !$reply.contactid}<input type="button" value="{$_ADMINLANG.support.blocksender}" onclick="window.location='?action=viewticket&id={$ticketid}&blocksender=true&token={$csrfToken}'" class="btn btn-xs btn-small" />{/if}

{/if}

</div>

<div class="tools">

<div class="editbtns{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}">
<img src="../assets/img/spinner.gif" width="16" height="16" class="saveSpinner" style="display: none" />
{if !$reply.note}<input type="button" value="{$_ADMINLANG.global.edit}" onclick="editTicket('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-default" />{/if}
{if $deleteperm}<input type="button" value="{$_ADMINLANG.global.delete}" onclick="{if $reply.id}{if $reply.note}doDeleteNote('{$reply.id}');{else}doDeleteReply('{$reply.id}');{/if}{else}doDeleteTicket();{/if}" class="btn btn-xs btn-small btn-danger" />{/if}
</div>
<div class="editbtns{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}" style="display:none">
<img src="../assets/img/spinner.gif" width="16" height="16" class="saveSpinner" style="display: none" />
<input type="button" value="{$_ADMINLANG.global.save}" onclick="editTicketSave('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-success" />
<input type="button" value="{$_ADMINLANG.global.cancel}" onclick="editTicketCancel('{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}')" class="btn btn-xs btn-small btn-inverse" />
</div>

</div>

</div>
<div class="rightcol">
{if !$reply.note}
<div class="quoteicon"><a href="#" onClick="quoteTicket('{if !$reply.id}{$ticketid}{/if}','{if $reply.id}{$reply.id}{/if}'); return false;"><img src="images/icons/quote.png" border="0" /></a>{if $reply.id} <input type="checkbox" name="rids[]" value="{$reply.id}" />{/if}</div>
{/if}
<div class="postedon">{if $reply.note}{$reply.admin} posted a note{else}Posted{/if} {if $reply.friendlydate}on {$reply.friendlydate}{else}today{/if} at {$reply.friendlytime}</div>

<div class="msgwrap" id="content{if $reply.id}r{$reply.id}{else}t{$ticketid}{/if}">

<div class="message markdown-content">
{$reply.message}
</div>

{if $reply.numattachments && !$reply.attachments_removed}
<br />
<strong>{$_ADMINLANG.support.attachments}</strong>
<br /><br />
{foreach from=$reply.attachments key=num item=attachment}
{if $thumbnails}
<div class="ticketattachmentcontainer">
    <a href="../{$attachment.dllink}"{if $attachment.isImage} data-lightbox="image-{if $reply.id}{if $reply.note}n{else}r{/if}{$reply.id}{else}t{$ticketid}{/if}"{/if}>
        <span class="ticketattachmentthumbcontainer">
            <img src="../includes/thumbnail.php?{if $reply.id}{if $reply.note}nid={else}rid={/if}{$reply.id}{else}tid={$ticketid}{/if}&i={$num}" class="ticketattachmentthumb" />
        </span>
        <span class="ticketattachmentinfo">
            <img src="images/icons/attachment.png" align="top" />
            {$attachment.filename}
        </span>
    </a>
    <div class="ticketattachmentlinks">
        <small>
            {if $attachment.isImage}<a href="../{$attachment.dllink}">{lang key='support.download'}</a> | {/if}
            <a href="{$attachment.deletelink}" onclick="return confirm('{$_ADMINLANG.support.delattachment|escape:'javascript'}')" style="color:#cc0000">{$_ADMINLANG.support.remove}</a>
        </small>
    </div>
</div>
{else}
<a href="../{$attachment.dllink}"><img src="images/icons/attachment.png" align="absmiddle" /> {$attachment.filename}</a> <small>{if $attachment.isImage}<a href="../{$attachment.dllink}">{lang key='support.download'}</a> | {/if}<a href="{$attachment.deletelink}" onclick="return confirm('{$_ADMINLANG.support.delattachment|escape:'javascript'}')" style="color:#cc0000">{$_ADMINLANG.support.remove}</a></small><br />
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
    <div class="ticketattachmentcontainer">
        <ul>
            {foreach $reply.attachments as $num => $attachment}
                <li>
                    {$attachment.filename}
                </li>
            {/foreach}
        </ul>
    </div>
    <div class="clear"></div>
{/if}

</div>

</div>

</div>
{/foreach}

</div>

<a href="supportticketsprint.php?id={$ticketid}" target="_blank" class="btn btn-default btn-xs">
    <i class="fas fa-print"></i>
    {$_ADMINLANG.support.viewprintable}
</a>
{if $repliescount>1}
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
