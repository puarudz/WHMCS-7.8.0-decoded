<div class="system-health-export-buttons clearfix hidden-xs">
    <a href="systemhealthandupdates.php?export=json" class="btn btn-link pull-right">
        <i class="fas fa-code fa-fw"></i>
        {lang key="healthCheck.exportAsJson"}
    </a>
    <a href="systemhealthandupdates.php?export=text" class="btn btn-link pull-right">
        <i class="far fa-file-alt fa-fw"></i>
        {lang key="healthCheck.exportAsText"}
    </a>
</div>

<div class="health-status-blocks">
    <div class="row health-status-col-margin">
        <div class="col-sm-4">
            <div class="health-status-block health-status-block-success clearfix">
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="detail">
                    <span class="count">{$successfulChecks}</span>
                    <span class="desc">{lang key="healthCheck.successfulChecks"}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="health-status-block health-status-block-warning clearfix">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="detail">
                    <span class="count">{$warningChecks}</span>
                    <span class="desc">{lang key="healthCheck.warningChecks"}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="health-status-block health-status-block-danger clearfix">
                <div class="icon">
                    <i class="fas fa-times"></i>
                </div>
                <div class="detail">
                    <span class="count">{$dangerChecks}</span>
                    <span class="desc">{lang key="healthCheck.dangerChecks"}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row health-status-col-margin">
    <div class="health-status-col health-status-col-danger">

        <div id="{$id}" class="panel panel-health-check panel-health-check-danger">
            <div class="panel-heading">
                <i class="fas fa-times-circle"></i>
                {lang key="healthCheck.dangerChecks"}
                <span class="pull-right clickable">
                    <i class="glyphicon glyphicon-chevron-up"></i>
                </span>
            </div>
            <div class="panel-body">

                {foreach $checks.danger as $id => $check}
                    <div id="{$id}" class="panel">
                        <div class="panel-heading">

                            {$check->getTitle()}
                        </div>
                        <div class="panel-body">
                            {$check->getBody()}
                        </div>
                    </div>
                {foreachelse}
                    <div id="{$id}" class="panel">
                        <div class="panel-heading">
                            {lang key="healthCheck.noChecksFailedTitle"}
                        </div>
                        <div class="panel-body">
                            {lang key="healthCheck.noDangerChecksFailedDesc"}
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
    <div class="health-status-col">

        <div id="{$id}" class="panel panel-health-check panel-health-check-warning">
            <div class="panel-heading">
                <i class="fas fa-exclamation-triangle"></i>
                {lang key="healthCheck.warningChecks"}
                <span class="pull-right clickable">
                    <i class="glyphicon glyphicon-chevron-up"></i>
                </span>
            </div>
            <div class="panel-body">

                {foreach $checks.warning as $id => $check}
                    <div id="{$id}" class="panel">
                        <div class="panel-heading">
                            {$check->getTitle()}
                        </div>
                        <div class="panel-body">
                            {$check->getBody()}
                        </div>
                    </div>
                {foreachelse}
                    <div id="{$id}" class="panel">
                        <div class="panel-heading">
                            {lang key="healthCheck.noChecksFailedTitle"}
                        </div>
                        <div class="panel-body">
                            {lang key="healthCheck.noWarningChecksFailedDesc"}
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
    <div class="health-status-col health-status-col-success">

        <div class="panel panel-health-check panel-health-check-success">
            <div class="panel-heading">
                <i class="fas fa-check"></i>
                {lang key="healthCheck.successfulChecks"}
                <span class="pull-right clickable">
                    <i class="glyphicon glyphicon-chevron-up"></i>
                </span>
            </div>
            <div class="panel-body">

                {foreach $checks.success as $id => $check}
                    <div id="{$id}" class="panel">
                        <div class="panel-heading">
                            {$check->getTitle()}
                        </div>
                        <div class="panel-body">
                            {$check->getBody()}
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
</div>

<div class="text-center visible-xs">
    <a href="systemhealthandupdates.php?export=json" class="btn btn-link">
        <i class="fas fa-code fa-fw"></i>
        {lang key="healthCheck.exportAsJson"}
    </a>
    <a href="systemhealthandupdates.php?export=text" class="btn btn-link">
        <i class="far fa-file-alt fa-fw"></i>
        {lang key="healthCheck.exportAsText"}
    </a>
</div>
