<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version600beta4 extends IncrementalVersion
{
    protected $updateActions = array("updateExpiredDomainNoticeTemplate", "adjustAffiliatePayoutDefault");
    protected function updateExpiredDomainNoticeTemplate()
    {
        $messageHash = "023123ac1c3df89147f7b56a2b5ec18b";
        $query = "SELECT md5(`message`) as message FROM tblemailtemplates WHERE `name` = 'Expired Domain Notice'" . " AND `language` = '';";
        $result = mysql_query($query);
        $data = mysql_fetch_assoc($result);
        if ($data["message"] == $messageHash) {
            $message = "<p>Dear {\$client_name},</p>" . PHP_EOL . "<p>The domain name listed below expired {\$domain_days_after_expiry} days ago.</p>" . PHP_EOL . "<p>{\$domain_name}</p>" . PHP_EOL . "<p>To ensure that the domain isn't registered by someone else, you should renew it now." . " To renew the domain, please visit the following page and follow the steps shown:" . " <a title=\"{\$whmcs_url}/cart.php?gid=renewals\"" . " href=\"{\$whmcs_url}/cart.php?gid=renewals\">{\$whmcs_url}/cart.php?gid=renewals</a>" . "</p>" . PHP_EOL . "<p>Due to the domain expiring, the domain will not be accessible so any" . " web site or email services associated with it will stop working. You may be able to renew it for up to" . " 30 days after the renewal date.</p>" . PHP_EOL . "<p>{\$signature}</p>";
            $query = "UPDATE tblemailtemplates SET message = '" . mysql_real_escape_string($message) . "'" . " WHERE `name` = 'Expired Domain Notice' AND language = '';";
            mysql_query($query);
        }
        return $this;
    }
    protected function adjustAffiliatePayoutDefault()
    {
        $currentAffiliatePayout = \WHMCS\Config\Setting::getValue("AffiliatePayout");
        if ($currentAffiliatePayout == "0.00") {
            \WHMCS\Config\Setting::setValue("AffiliatePayout", "25.00");
        }
        return $this;
    }
}

?>