<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use WHMCS\Carbon;
use WHMCS\Module\AbstractWidget;
use WHMCS\User\Client;
use WHMCS\Utility\GeoIp;
/**
 * Client Activity Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class ClientActivity extends AbstractWidget
{
    protected $title = 'Client Activity';
    protected $description = 'Recent online clients.';
    protected $weight = 70;
    protected $cache = true;
    protected $cacheExpiry = 300;
    protected $requiredPermission = 'List Clients';
    public function getData()
    {
        return array('activeCount' => (int) Client::where('status', '=', 'Active')->count(), 'onlineCount' => (int) Client::where('lastlogin', '>', Carbon::now()->subHour()->toDateTimeString())->count(), 'recentActiveClients' => Client::orderBy('lastlogin', 'desc')->limit(25)->get(array('id', 'firstname', 'lastname', 'companyname', 'ip', 'lastlogin'))->toArray());
    }
    public function generateOutput($data)
    {
        $activeClients = number_format((int) $data['activeCount']);
        $usersOnline = number_format((int) $data['onlineCount']);
        $clients = array();
        foreach ($data['recentActiveClients'] as $client) {
            // If there is no lastlogin setting, or its been set to a timestamp like 0000-00-00, we show N/A
            $clientLastLogin = empty($client['lastlogin']) || strpos($client['lastlogin'], '0000') === 0 ? "N/A" : Carbon::createFromFormat('Y-m-d H:i:s', $client['lastlogin'])->diffForHumans();
            $clients[] = '<div class="client">
        <div class="last-login">' . $clientLastLogin . '</div>
        <a href="clientssummary.php?userid=' . $client['id'] . '" class="link">' . $client['firstname'] . ' ' . $client['lastname'] . ($client['companyname'] ? ' (' . $client['companyname'] . ')' : '') . '</a>' . GeoIp::getLookupHtmlAnchor($ip, 'ip-address') . '</div>';
        }
        $clientOutput = implode($clients);
        return <<<EOF

<div class="icon-stats">
    <div class="row">
        <div class="col-sm-6">
            <div class="item">
                <div class="icon-holder text-center color-orange">
                    <a href="clients.php?status=Active">
                        <i class="pe-7s-user"></i>
                    </a>
                </div>
                <div class="data">
                    <div class="note">
                        <a href="clients.php?status=Active">Active Clients</a>
                    </div>
                    <div class="number">
                        <a href="clients.php?status=Active">
                            <span class="color-orange">{$activeClients}</span>
                            <span class="unit">Active</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="item">
                <div class="icon-holder text-center color-green">
                    <i class="pe-7s-smile"></i>
                </div>
                <div class="data">
                    <div class="note">
                        Users Online
                    </div>
                    <div class="number">
                        <span class="color-green">{$usersOnline}</span>
                        <span class="unit">Last Hour</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="clients-list">
    {$clientOutput}
</div>
EOF;
    }
}

?>