<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use WHMCS\Carbon;
use WHMCS\Clients;
use WHMCS\Module\AbstractWidget;
use WHMCS\Module\Queue as ModuleQueue;
use WHMCS\Orders;
/**
 * Badges Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class Badges extends AbstractWidget
{
    protected $title = 'Badges';
    protected $description = '';
    protected $columns = 3;
    protected $weight = 0;
    protected $wrapper = false;
    protected $cache = true;
    protected $cacheExpiry = 120;
    protected $draggable = false;
    public function getData()
    {
        $clients = new Clients();
        $orders = new Orders();
        $ticketCounts = localApi('GetTicketCounts', array());
        return array('pendingOrders' => $orders->getPendingCount(), 'ticketsAwaitingReply' => $ticketCounts['awaitingReply'], 'cancellations' => $clients->getNumberOfOpenCancellationRequests(), 'moduleQueueCount' => ModuleQueue::incomplete()->count());
    }
    public function generateOutput($data)
    {
        $pendingOrders = (int) $data['pendingOrders'];
        $awaitingReply = (int) $data['ticketsAwaitingReply'];
        $pendingCancellations = (int) $data['cancellations'];
        $moduleQueueCount = (int) $data['moduleQueueCount'];
        return <<<EOF
<div class="row home-status-badge-row">
    <div class="col-sm-3">

        <div class="health-status-block status-badge-green clearfix">
            <div class="icon">
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                </a>
            </div>
            <div class="detail">
                <a href="orders.php?status=Pending">
                    <span class="count">{$pendingOrders}</span>
                    <span class="desc">Pending Orders</span>
                </a>
            </div>
        </div>

    </div>
    <div class="col-sm-3">

        <div class="health-status-block status-badge-pink clearfix">
            <div class="icon">
                <a href="supporttickets.php">
                    <i class="fas fa-comment"></i>
                </a>
            </div>
            <div class="detail">
                <a href="supporttickets.php">
                    <span class="count">{$awaitingReply}</span>
                    <span class="desc">Tickets Waiting</span>
                </a>
            </div>
        </div>

    </div>
    <div class="col-sm-3">

        <div class="health-status-block status-badge-orange clearfix">
            <div class="icon">
                <a href="cancelrequests.php">
                    <i class="fas fa-ban"></i>
                </a>
            </div>
            <div class="detail">
                <a href="cancelrequests.php">
                    <span class="count">{$pendingCancellations}</span>
                    <span class="desc">Pending Cancellations</span>
                </a>
            </div>
        </div>

    </div>
    <div class="col-sm-3">

        <div class="health-status-block status-badge-cyan clearfix">
            <div class="icon">
                <a href="modulequeue.php">
                    <i class="fas fa-exclamation-triangle"></i>
                </a>
            </div>
            <div class="detail">
                <a href="modulequeue.php">
                    <span class="count">{$moduleQueueCount}</span>
                    <span class="desc">Pending Module Actions</span>
                </a>
            </div>
        </div>

    </div>
</div>
EOF;
    }
}

?>