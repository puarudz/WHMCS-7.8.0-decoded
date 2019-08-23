<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use App;
use WHMCS\Module\AbstractWidget;
/**
 * NetworkStatus Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class NetworkStatus extends AbstractWidget
{
    protected $title = 'Network Status';
    protected $description = 'An overview of Network Status.';
    protected $weight = 80;
    protected $cache = true;
    public function getData()
    {
        return localAPI('GetServers', array('fetchStatus' => App::getFromRequest('refresh')));
    }
    public function generateOutput($data)
    {
        $output = '';
        foreach ($data['servers'] as $server) {
            $online = $data['fetchStatus'] ? (bool) $server['status']['http'] : null;
            if ($data['fetchStatus']) {
                $uptime = $server['status']['uptime'] ? $server['status']['uptime'] : '-';
                $load = $server['status']['load'] ? $server['status']['load'] : '-';
            } else {
                $uptime = $load = 'N/A';
            }
            $serverAddress = $server['hostname'] ? $server['hostname'] : $server['ipaddress'];
            $output .= '
<div class="item">
    <div class="name">
        <div class="data">' . $server['name'] . '</div>
        <div class="note"><a href="http://' . $serverAddress . '" target="_blank">' . $serverAddress . '</a></div>
    </div>
    <div class="stats text-center">
        <div class="status">
            <div class="data color-' . (is_null($online) || $online ? 'green' : 'pink') . '">' . (is_null($online) ? 'N/A' : ($online ? 'Online' : 'Offline')) . '</div>
            <div class="note">Status</div>
        </div>
        <div class="uptime">
            <div class="data">' . (is_null($uptime) ? 'N/A' : $uptime) . '</div>
            <div class="note">Uptime</div>
        </div>
        <div class="load">
            <div class="data text-info">' . (is_null($load) ? 'N/A' : $load) . '</div>
            <div class="note">Avg. load</div>
        </div>
    </div>
</div>';
        }
        if (count($data['servers']) == 0) {
            $output = '<div class="text-center">
                No servers configured.
                <br /><br />
                <a href="configservers.php" class="btn btn-primary btn-sm">Add Your First Now</a>
                <br /><br />
            </div>';
        }
        return <<<EOF
<div class="widget-content-padded">
    <div class="items-wrapper">
        {$output}
    </div>
</div>
EOF;
    }
}

?>