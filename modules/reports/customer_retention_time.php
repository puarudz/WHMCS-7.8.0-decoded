<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Product\Group;
use WHMCS\Product\Product;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
/** @var WHMCS\Application $whmcs */
$include_active = $whmcs->get_req_var('include_active');
$checked = $include_active ? ' checked' : '';
$reportdata["title"] = "Average Customer Retention Time";
$reportdata["description"] = "This report calculates and provides you with the average lifetime of products, " . "services, addons and domains - that is the number of days between the registration date and the termination " . "date. Averages are displayed by product and the associated billing cycle, and are displayed both as a " . "number of days value and a years/months value.";
$reportdata["headertext"] = <<<EOT
<div class="text-center">
    <form method="post" action="{$_SERVER['PHP_SELF']}?report={$report}">
        <label class="checkbox-inline">
            <input type="checkbox" value="true" onchange="this.form.submit()" name="include_active"{$checked}>
            Include Active Products & Services (assuming active until Next Due Date) in Calculation of Average Retention Time
        </label>
    </form>
</div>
EOT;
$statuses = array('Cancelled', 'Terminated', 'Expired');
if ($include_active) {
    $statuses = array_merge($statuses, array('Active', 'Suspended'));
}
$reportdata['tableheadings'] = array(AdminLang::trans('products.productname'), AdminLang::trans('fields.billingcycle'), AdminLang::trans('reports.productCount'), AdminLang::trans('reports.averageDaysActive'), AdminLang::trans('reports.averageYearsMonthsActive'));
/** @var WHMCS\Product\Group[] $productGroups */
$productGroups = Group::all();
foreach ($productGroups as $productGroup) {
    $groupRows = array();
    /** @var WHMCS\Product\Product[] $products */
    $products = Product::where('gid', '=', $productGroup->id)->get();
    foreach ($products as $product) {
        /** @var StdClass[] $services */
        $services = Capsule::table('tblhosting')->where('packageid', '=', $product->id)->where('regdate', '!=', '0000-00-00')->whereIn('domainstatus', $statuses)->whereNotIn('billingcycle', array('Free Account', 'Free', 'One Time'))->selectRaw('count(id) as count, billingcycle, AVG(DATEDIFF(IF(`termination_date` != \'0000-00-00\', `termination_date`, `nextduedate`), `regdate`)) as avg_days')->groupBy('billingcycle')->get();
        if ($services) {
            foreach ($services as $service) {
                $dateTime = new DateTime();
                $newDateTime = $dateTime->diff(new DateTime(date("Y-m-d H:i:s", strtotime(sprintf('-%s Days', round($service->avg_days))))));
                $yearsMonths = '';
                if ($newDateTime->y) {
                    $yearsMonths .= $newDateTime->y . ' ' . AdminLang::trans('calendar.years');
                }
                $decimalDays = 0;
                if ($newDateTime->d) {
                    $decimalDays = round($newDateTime->d / 30, 1);
                }
                $yearsMonths .= ' ' . ($newDateTime->m + $decimalDays) . ' ' . ($newDateTime->m == 1 && $decimalDays == 0 ? AdminLang::trans('calendar.month') : AdminLang::trans('calendar.months'));
                $billingCycle = strtolower(str_replace(array(' ', '-'), '', $service->billingcycle));
                $groupRows[] = array($product->name, AdminLang::trans('billingcycles.' . $billingCycle), $service->count, round($service->avg_days), $yearsMonths);
            }
        }
    }
    if ($groupRows) {
        $reportdata['tablevalues'][][] = "**{$productGroup->name}";
        foreach ($groupRows as $row) {
            $reportdata['tablevalues'][] = $row;
        }
    }
}
/** @var StdClass[] $productAddons */
$productAddons = Capsule::table('tbladdons')->get();
$addonRows = array();
foreach ($productAddons as $productAddon) {
    /** @var StdClass[] $addons */
    $addons = Capsule::table('tblhostingaddons')->where('addonid', '=', $productAddon->id)->where('regdate', '!=', '0000-00-00')->whereIn('status', $statuses)->whereNotIn('billingcycle', array('Free Account', 'Free', 'One Time'))->selectRaw('count(id) as count, billingcycle, AVG(DATEDIFF(IF(`termination_date` != \'0000-00-00\', `termination_date`, `nextduedate`), `regdate`)) as avg_days')->groupBy('billingcycle')->get();
    if ($addons) {
        foreach ($addons as $addon) {
            $dateTime = new DateTime();
            $newDateTime = $dateTime->diff(new DateTime(date("Y-m-d H:i:s", strtotime(sprintf('-%s Days', round($addon->avg_days))))));
            $yearsMonths = '';
            if ($newDateTime->y) {
                $yearsMonths .= $newDateTime->y . ' ' . AdminLang::trans('calendar.years');
            }
            $decimalDays = 0;
            if ($newDateTime->d) {
                $decimalDays = round($newDateTime->d / 30, 1);
            }
            $yearsMonths .= ' ' . ($newDateTime->m + $decimalDays) . ' ' . ($newDateTime->m == 1 && $decimalDays == 0 ? AdminLang::trans('calendar.month') : AdminLang::trans('calendar.months'));
            $billingCycle = strtolower(str_replace(array(' ', '-'), '', $addon->billingcycle));
            $addonRows[] = array($productAddon->name, AdminLang::trans('billingcycles.' . $billingCycle), $addon->count, round($addon->avg_days), $yearsMonths);
        }
    }
}
/**
 * Custom Defined Addons
 */
/** @var StdClass[] $addons */
$addons = Capsule::table('tblhostingaddons')->where('addonid', '=', '0')->where('regdate', '!=', '0000-00-00')->whereIn('status', $statuses)->whereNotIn('billingcycle', array('Free Account', 'Free', 'One Time'))->selectRaw('name, count(id) as count, billingcycle, AVG(DATEDIFF(IF(`termination_date` != \'0000-00-00\', `termination_date`, `nextduedate`), `regdate`)) as avg_days')->groupBy('name', 'billingcycle')->get();
if ($addons) {
    foreach ($addons as $addon) {
        $dateTime = new DateTime();
        $newDateTime = $dateTime->diff(new DateTime(date("Y-m-d H:i:s", strtotime(sprintf('-%s Days', round($addon->avg_days))))));
        $yearsMonths = '';
        if ($newDateTime->y) {
            $yearsMonths .= $newDateTime->y . ' ' . AdminLang::trans('calendar.years');
        }
        $decimalDays = 0;
        if ($newDateTime->d) {
            $decimalDays = round($newDateTime->d / 30, 1);
        }
        $yearsMonths .= ' ' . ($newDateTime->m + $decimalDays) . ' ' . ($newDateTime->m == 1 && $decimalDays == 0 ? AdminLang::trans('calendar.month') : AdminLang::trans('calendar.months'));
        $billingCycle = strtolower(str_replace(array(' ', '-'), '', $addon->billingcycle));
        $addonRows[] = array($addon->name, AdminLang::trans('billingcycles.' . $billingCycle), $addon->count, round($addon->avg_days), $yearsMonths);
    }
}
if ($addonRows) {
    $reportdata['tablevalues'][][] = '**' . AdminLang::trans('addons.productaddons');
    foreach ($addonRows as $row) {
        $reportdata['tablevalues'][] = $row;
    }
}
/** @var StdClass[] $domainTlds */
$domainTlds = Capsule::table('tbldomainpricing')->get();
$domainRows = array();
foreach ($domainTlds as $domainTld) {
    /** @var StdClass[] $domains */
    $domains = Capsule::table('tbldomains')->where('domain', 'LIKE', "%{$domainTld->extension}")->where('registrationdate', '!=', '0000-00-00')->whereIn('status', $statuses)->selectRaw('count(id) as count, registrationperiod, AVG(DATEDIFF(IF(`expirydate` != \'0000-00-00\', `expirydate`, `nextduedate`), `registrationdate`)) as avg_days')->groupBy('registrationperiod')->get();
    if ($domains) {
        foreach ($domains as $domain) {
            $dateTime = new DateTime();
            $newDateTime = $dateTime->diff(new DateTime(date("Y-m-d H:i:s", strtotime(sprintf('-%s Days', round($domain->avg_days))))));
            $domainRows[] = array($domainTld->extension, $domain->registrationperiod . ' ' . AdminLang::trans('calendar.years'), $domain->count, round($domain->avg_days), $newDateTime->y . ' ' . AdminLang::trans('calendar.years'));
        }
    }
}
if ($domainRows) {
    $reportdata['tablevalues'][][] = '**' . AdminLang::trans('fields.tld');
    foreach ($domainRows as $row) {
        $reportdata['tablevalues'][] = $row;
    }
}

?>