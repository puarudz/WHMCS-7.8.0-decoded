<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use AdminLang;
use App;
use Carbon\Carbon;
use WHMCS\MarketConnect\Balance;
use WHMCS\MarketConnect\MarketConnect as MarketConnectConnector;
use WHMCS\MarketConnect\Promotion;
use WHMCS\Module\AbstractWidget;
/**
 * MarketConnect Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class MarketConnect extends AbstractWidget
{
    protected $title = 'MarketConnect';
    protected $description = 'An overview of MarketConnect.';
    protected $weight = 45;
    protected $cache = true;
    protected $cacheExpiry = 6 * 60;
    protected $requiredPermission = 'View MarketConnect Balance';
    public function getData()
    {
        $isMarketConnectConfigured = MarketConnectConnector::isAccountConfigured();
        if ($isMarketConnectConfigured) {
            $activeServices = MarketConnectConnector::getActiveServices();
        }
        return ['isConfigured' => $isMarketConnectConfigured, 'activeServices' => $isMarketConnectConfigured ? $activeServices : []];
    }
    public function generateOutput($data)
    {
        $isConfigured = $data['isConfigured'];
        $activeServices = $data['activeServices'];
        $balance = (new Balance())->loadFromCache();
        try {
            if (App::getFromRequest('refresh')) {
                $balance->updateViaApi();
            } else {
                $balance->setCacheTimeout(6)->updateViaApiIfExpired();
            }
        } catch (\Exception $e) {
            // Exception will likely be an auth error
            // On exception, continue using cached data or assume zero
        }
        $balanceAmount = number_format($balance->getBalance(), 2, '.', ',');
        $balanceLastUpdated = $balance->getLastUpdatedDiff();
        $langManage = AdminLang::trans('home.manage');
        $langSellingStatus = AdminLang::trans('marketConnect.sellingStatus');
        $langDepositFunds = AdminLang::trans('marketConnect.depositFunds');
        $langYourBalance = AdminLang::trans('marketConnect.yourBalance');
        $langLastUpdated = AdminLang::trans('marketConnect.lastUpdated');
        $langBalance = AdminLang::trans('fields.balance');
        $langPromotions = AdminLang::trans('global.promotions');
        $services = [];
        foreach (Promotion::SERVICES as $service) {
            $isActive = in_array($service['vendorSystemName'], $activeServices);
            $services[] = '<div class="service ' . ($isActive ? 'selling' : 'not-selling') . '">
                <img src="../assets/img/marketconnect/' . $service['vendorSystemName'] . '/logo-sml.png">
                <span class="title">' . $service['serviceTitle'] . '<br>by ' . $service['vendorName'] . '</span>
                ' . ($isActive ? '<span class="label label-success">Selling</span>' : '<span class="label label-default">Not Selling</span>') . '
            </div>';
        }
        if ($isConfigured) {
            $accountOutput = '<form method="post" action="marketconnect.php">
        <input type="hidden" name="action" value="sso">
        <div class="balance-wrapper">
            <div class="pull-right text-right">
                <button type="submit" class="btn btn-default btn-deposit">
                    <i class="fas fa-credit-card fa-fw"></i>
                    ' . $langDepositFunds . '
                </button><br>
                <a href="https://marketplace.whmcs.com/promotions" target="_blank" class="btn btn-default btn-promo">
                    <i class="fas fa-ticket-alt fa-fw"></i>
                    ' . $langPromotions . '
                </a>
            </div>
            <h4>' . $langYourBalance . '</h4>
            <strong>' . $balanceAmount . '</strong>
            Points
            <small>' . $langLastUpdated . ': ' . $balanceLastUpdated . '</small>
        </div>
    </form>';
        } else {
            $accountOutput = '<div class="balance-wrapper promo-wrapper">
        MarketConnect gives you access to resell market leading services to your customers in minutes. <a href="#">Learn more &raquo;</a>
    </div>';
        }
        return '<div class="widget-content-padded">

    <a href="marketconnect.php" class="btn btn-default btn-manage pull-right">
        ' . $langManage . '
    </a>
    <h4>' . $langSellingStatus . '</h4>

    <div class="selling-status">
        ' . implode($services) . '
    </div>

    ' . $accountOutput . '

</div>';
    }
}

?>