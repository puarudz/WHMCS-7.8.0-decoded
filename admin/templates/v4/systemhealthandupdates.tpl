{function convertLevel psrLevel=info}{if $psrLevel eq 'error'}danger
    {elseif $psrLevel eq 'warning'}warning
    {elseif $psrLevel eq 'notice'}success
    {else}info{/if}{/function}

{function getIcon psrLevel=info}<i class="fas {if $psrlevel eq 'error'}fa-times
 {elseif $psrlevel eq 'warning'}fa-warning
 {elseif $psrlevel eq 'notice'}fa-check
 {else}fa-info-circle{/if}"></i>&nbsp;{/function}

{function renderNews}
    {if $keyChecks->has('updateNews')}
        <h2 class="text-center">{$_ADMINLANG['healthCheck']['news']}</h2>
        <div class="container-fluid">
            <div class="row">
                {foreach $keyChecks.updateNews->getBody() as $article}
                    <div id="{$keyChecks.updateNews->getName()}-{$article@iteration}" class="panel panel-default">
                        <div class="panel-heading">
                            <a href="{$article.link}" class="autoLinked">
                                <i class="far fa-newspaper"></i>
                                {$article.headline}
                            </a>
                        </div>
                        <div class="panel-body">
                            {$article.text|truncate:120}
                            <div class="text-right top-margin-5">
                                <a href="{$article.link}" id="news{$article@iteration}" class="autoLinked btn btn-default btn-xs">
                                    {$_ADMINLANG['healthCheck']['readMore']}...
                                </a>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {/if}
{/function}

<div class="row systemHealthAndUpdates">
    <div class="col-md-4">
        <div id="{$keyChecks.version->getName()}" class="alert text-center version alert-{convertLevel psrLevel=$keyChecks.version->getSeverityLevel()}">
            <span class="version-number">{$installedVersionNumberParts.0}</span>
            <span class="version-label">{$installedVersionNumberParts.1}</span>
            <p>{$keyChecks.version->getBody()}</p>
            <div class="row">
                <div class="col-xs-6">
                    <a href="{$installedVersionReleaseNotes}" target="_blank" class="btn btn-default btn-block btn-hide-overflow">
                        <i class="far fa-file-alt"></i>
                        Release Notes
                    </a>
                </div>
                <div class="col-xs-6">
                    <a href="{$installedVersionChangelog}" target="_blank" class="btn btn-default btn-block btn-hide-overflow">
                        <i class="fas fa-cog"></i>
                        Changelog
                    </a>
                </div>
            </div>
        </div>
        {if $keyChecks->has('systemRequirements')}
            <div class="alert alert-danger systemRequirementLight">
                <ul class="fa-ul">
                    {foreach $keyChecks.systemRequirements->getBody() as $requirement}
                        <li><i class="fa-li fas fa-times"></i>{$requirement}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        <div class="hidden-sm hidden-xs">
            {renderNews}
        </div>
    </div>

    <div class="col-md-8 right-col">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="far fa-heart"></i>
                    Health Status Summary
                </h3>
            </div>
            <div class="panel-body health-summary">
                {if $warningChecks eq 0 and $dangerChecks eq 0}
                    <div class="passing">
                        <i class="fas fa-check-circle"></i>
                        {lang key="healthCheck.allChecksPassed" count=$successfulChecks}
                    </div>
                {else}
                    <div class="row">
                        <div class="col-md-4 passing">
                            <i class="fas fa-check-circle"></i>
                            {lang key="healthCheck.checksPassed" count=$successfulChecks}
                        </div>
                        <div class="col-md-4 warning">
                            <i class="fas {if $warningChecks > 0}fa-exclamation-triangle{else}fa-thumbs-up{/if}"></i>
                            {lang key="healthCheck.checksNeedAttention" count=$warningChecks}
                        </div>
                        <div class="col-md-4 failures">
                            <i class="fas fa-times-circle"></i>
                            {lang key="healthCheck.checksFailed" count=$dangerChecks}
                        </div>
                    </div>
                {/if}

                <div class="progress health-progress-bar">
                    <div class="progress-bar progress-bar-success progress-bar-striped" style="width: {$checkPercentages.successful}%">
                        <span class="sr-only">{lang key="healthCheck.checksPassed" count=$checkPercentages.successful|cat:'%'}</span>
                    </div>
                    <div class="progress-bar progress-bar-warning progress-bar-striped" style="width: {$checkPercentages.warning}%">
                        <span class="sr-only">{lang key="healthCheck.checksNeedAttention" count=$checkPercentages.warning|cat:'%'}</span>
                    </div>
                    <div class="progress-bar progress-bar-danger progress-bar-striped" style="width: {$checkPercentages.danger}%">
                        <span class="sr-only">{lang key="healthCheck.checksFailed" count=$checkPercentages.danger|cat:'%'}</span>
                    </div>
                </div>
            </div>
        </div>

        {foreach $regularChecks as $id => $check}
            <div id="{$id}" class="panel panel-{convertLevel psrLevel=$check->getSeverityLevel()}">
                <div class="panel-heading">{getIcon psrLevel=$check->getSeverityLevel()}{$check->getTitle()}</div>
                <div class="panel-body panel-body-overflow-auto">
                    {$check->getBody()}
                </div>
            </div>
        {/foreach}

        <div id="exportButtons" class="panel">
            <div class="row">
                <div class="col-xs-6">
                    <a href="systemhealthandupdates.php?export=json" class="btn btn-default btn-block">
                        <i class="fas fa-code"></i>
                        {lang key="healthCheck.exportAsJson"}
                    </a>
                </div>
                <div class="col-xs-6">
                    <a href="systemhealthandupdates.php?export=text" class="btn btn-default btn-block">
                        <i class="far fa-file-alt"></i>
                        {lang key="healthCheck.exportAsText"}
                    </a>
                </div>
            </div>
        </div>

        <div class="hidden-lg hidden-md">
            {renderNews}
        </div>
    </div>
</div>
