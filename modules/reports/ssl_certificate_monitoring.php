<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Domain\Ssl\Status;
/** @type array $reportdata */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$refreshButtonNumber = 25;
$showAll = App::getFromRequest('showall');
$title = 'SSL Certificate Monitoring';
$reportdata['title'] = $title;
$reportdata['description'] = 'Displays a list of domains with their SSL status, if available.' . '<p>';
if (!$showAll) {
    $reportdata['description'] .= '<a href="' . $requeststr . '&showall=1" class="btn btn-default btn-sm">' . 'Show inactive domains' . '</a>';
} else {
    $reportdata['description'] .= '<a href="' . $requeststr . '&showall=0" class="btn btn-default btn-sm">' . 'Hide inactive domains' . '</a> ';
}
$reportdata['description'] .= ' <button type="button" class="refresh-ssl btn btn-default btn-sm">' . 'Re-validate SSL Status' . '</button></p>';
$reportdata['tableheadings'][] = 'Domain Name';
$reportdata['tableheadings'][] = 'Status';
$reportdata['tableheadings'][] = 'Issuer';
$reportdata['tableheadings'][] = 'Expiry';
$reportdata['tableheadings'][] = 'Last Update';
$unionQuery = Capsule::table('tbldomains')->select(['domain', 'userid']);
if (!$showAll) {
    $unionQuery->whereIn('status', ['Active', 'Grace']);
}
$data = Capsule::table('tblhosting')->where('domain', '!=', '')->select(['domain', 'userid'])->union($unionQuery)->orderBy('domain');
if (!$showAll) {
    $data->whereIn('domainstatus', ['Active', 'Completed']);
}
$reportKeys = ['in30Days', 'in90Days', 'in180Days', 'moreThan180Days', 'noSsl'];
$reportData = [$reportKeys[0] => [], $reportKeys[1] => [], $reportKeys[2] => [], $reportKeys[3] => [], $reportKeys[4] => []];
$today = Carbon::now();
$domainsArray = [];
/** @var stdClass $record */
foreach ($data->get() as $record) {
    $isDomain = str_replace('.', '', $record->domain) != $record->domain;
    if (!$isDomain) {
        continue;
    }
    if (in_array($domain, $domainsArray)) {
        continue;
    }
    $sslStatus = Status::factory($record->userid, $record->domain)->disableAutoResync();
    $expiryDate = $sslStatus->expiryDate;
    $reportKey = $reportKeys[4];
    if ($expiryDate) {
        $daysUntilExpiry = $expiryDate->diffInDays($today);
        $expiryDate = $expiryDate->endOfDay()->toAdminDateTimeFormat();
        switch (true) {
            case $daysUntilExpiry <= 30:
                $reportKey = $reportKeys[0];
                break;
            case $daysUntilExpiry <= 90:
                $reportKey = $reportKeys[1];
                break;
            case $daysUntilExpiry <= 180:
                $reportKey = $reportKeys[2];
                break;
            default:
                $reportKey = $reportKeys[3];
        }
    }
    if (!$expiryDate) {
        $expiryDate = '-';
    }
    $html = '<img src="%s" data-toggle="tooltip" title="%s" class="%s" data-domain="%s" data-user-id="%d" />';
    $image = sprintf($html, $sslStatus->getImagePath(), $sslStatus->getTooltipContent(), 'ssl-state', $record->domain, $record->userid);
    $issuerName = '';
    if ($sslStatus->issuerName) {
        $issuerName = $sslStatus->issuerOrg;
        if (!$issuerName) {
            $issuerName = $sslStatus->issuerName;
        }
    }
    $reportData[$reportKey][] = [$record->domain, $image, '<span class="issuer">' . ($issuerName ?: '-') . '</span>', '<span class="expiry">' . $expiryDate . '</span>', '<span class="updated">' . ($sslStatus->updated_at ? $sslStatus->updated_at->diffForHumans() : '-') . '</span>'];
    $domainsArray[] = $record->domain;
}
$rowCount = 0;
foreach ($reportKeys as $reportKey) {
    if (count($reportData[$reportKey]) === 0 && $reportKey == $reportKeys[count($reportKeys) - 1]) {
        continue;
    }
    $reportdata['tablevalues'][$rowCount][] = "**" . AdminLang::trans('sslState.' . $reportKey);
    $rowCount++;
    if (count($reportData[$reportKey]) === 0) {
        $reportdata['tablevalues'][$rowCount][] = '*+' . AdminLang::trans('global.norecordsfound');
        $rowCount++;
        continue;
    }
    if ($reportKey != $reportKeys[count($reportKeys) - 1]) {
        usort($reportData[$reportKey], function ($first, $second) {
            if ($first[3] == $second[3]) {
                return 0;
            }
            $expiryOne = Carbon::createFromFormat('Y-m-d H:i:s', toMySQLDate($first[3]));
            $expiryTwo = Carbon::createFromFormat('Y-m-d H:i:s', toMySQLDate($second[3]));
            return $expiryOne->lt($expiryTwo) ? -1 : 1;
        });
    }
    foreach ($reportData[$reportKey] as $reportDatum) {
        $reportdata['tablevalues'][$rowCount] = $reportDatum;
        $rowCount++;
    }
}
$langLoading = AdminLang::trans('global.loading');
$loadingImg = DI::make('asset')->getImgPath() . '/ssl/ssl-loading.gif';
$lastUpdated = Carbon::now()->diffForHumans();
$reportdata['footertext'] = <<<JAVASCRIPT
<script>
    jQuery(document).ready(function() {
        jQuery('[data-toggle="tooltip"]').tooltip();
        var startCount = 0,
            totalCount = jQuery('.ssl-state').length,
            processing = 0,
            refreshAmount = {$refreshButtonNumber},
            timed = null;
        if (refreshAmount > totalCount) {
            refreshAmount = totalCount;
        }
        jQuery(document).on('click', '.refresh-ssl', function() {
            jQuery(this).attr('disabled', true).addClass('disabled');
            jQuery('.ssl-state').each(function (index) {
                if (index >= startCount) {
                    processing += 1;
                    var self = jQuery(this);
                    var domain = self.data('domain');
                    var userid = self.data('user-id');

                    self.attr('src', '{$loadingImg}')
                                .attr('title', '{$langLoading}')
                                .tooltip('fixTitle');

                    WHMCS.http.jqClient.post(
                        WHMCS.adminUtils.getAdminRouteUrl('/domains/ssl-check'),
                        {
                            'domain': domain,
                            'userid': userid,
                            'details': true,
                            'token': csrfToken
                        },
                        function (data) {
                            self.attr('src', data.image)
                                .attr('title', data.tooltip)
                                .tooltip('fixTitle')
                                .attr('class', data.class)
                                .closest('tr').find('span.issuer').html(data.issuerName).end()
                                .find('span.expiry').html(data.expiryDate).end()
                                .find('span.updated').html('{$lastUpdated}');
                            processing -= 1;
                        }
                    );
                }
                if (
                    index === ((startCount + refreshAmount) - 1)
                    || index === (totalCount - 1)
                ) {
                    timed = setInterval(checkProcessing, 1000);
                    startCount = index + 1;
                    if (startCount >= totalCount) {
                        startCount = 0;
                    }
                    return false;
                }
            });
        });
        function checkProcessing()
        {
            if (processing === 0) {
                jQuery('.refresh-ssl').attr('disabled', false)
                    .removeClass('disabled');
                clearInterval(timed);
            }
        }
    });
</script>
JAVASCRIPT
;

?>