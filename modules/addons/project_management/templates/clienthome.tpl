{include file="$template/includes/tablelist.tpl" tableName="ProjectsList"}

<div class="table-container clearfix">
    <table id="tableProjectsList" class="table table-list">
        <thead>
            <tr>
                <th>{$_lang.title}</th>
                <th>{$_lang.created}</th>
                <th>{$_lang.duedate}</th>
                <th>{$_lang.status}</th>
                <th>{$_lang.lastmodified}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $projects as $project}
                <tr onclick="window.location='?m=project_management&a=view&id={$project.id}'">
                    <td><strong>{$project.title}</strong></td>
                    <td><span class="hidden">{$project.normalisedCreated}</span>{$project.created}</td>
                    <td><span class="hidden">{$project.normalisedDueDate}</span>{$project.duedate}</td>
                    <td><span class="label status status-{$project.status|strtolower|replace:' ':''}">{$project.status}</span></td>
                    <td><span class="hidden">{$project.normalisedLastModified}</span>{$project.lastmodified}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
