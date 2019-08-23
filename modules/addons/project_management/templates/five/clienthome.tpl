<p>{$numitems} {$LANG.recordsfound}, {$LANG.page} {$pagenumber} {$LANG.pageof} {$totalpages}</p>

<table class="table table-striped table-framed table-centered">
    <thead>
        <tr>
            <th{if $orderby eq "title"} class="headerSort{$sort}"{/if}><a href="index.php?m=project_management&orderby=title">{$_lang.title}</a></th>
            <th{if $orderby eq "created"} class="headerSort{$sort}"{/if}><a href="index.php?m=project_management&orderby=created">{$_lang.created}</a></th>
            <th{if $orderby eq "duedate"} class="headerSort{$sort}"{/if}><a href="index.php?m=project_management&orderby=duedate">{$_lang.duedate}</a></th>
            <th{if $orderby eq "status"} class="headerSort{$sort}"{/if}><a href="index.php?m=project_management&orderby=status">{$_lang.status}</a></th>
            <th{if $orderby eq "lastmodified"} class="headerSort{$sort}"{/if}><a href="index.php?m=project_management&orderby=lastmodified">{$_lang.lastmodified}</a></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
{foreach $projects as $project}
        <tr>
            <td><strong>{$project.title}</strong></td>
            <td>{$project.created}</td>
            <td>{$project.duedate}</td>
            <td>{$project.status}</td>
            <td>{$project.lastmodified}</td>
            <td class="textcenter"><a class="btn btn-info" href="{$smarty.server.PHP_SELF}?m=project_management&a=view&id={$project.id}"> <i class="icon icon-white icon-list-alt"></i> {$LANG.clientareaviewdetails}</a></td>
        </tr>
{foreachelse}
        <tr>
            <td colspan="6" class="textcenter">{$LANG.norecordsfound}</td>
        </tr>
{/foreach}
    </tbody>
</table>

<div class="pagination">
    <ul>
        <li class="prev{if !$prevpage} disabled{/if}"><a href="{if $prevpage}index.php?m=project_management&amp;page={$prevpage}{else}javascript:return false;{/if}">&larr; {$LANG.previouspage}</a></li>
        <li class="next{if !$nextpage} disabled{/if}"><a href="{if $nextpage}index.php?m=project_management&amp;page={$nextpage}{else}javascript:return false;{/if}">{$LANG.nextpage} &rarr;</a></li>
    </ul>
</div>

<br /><br />
