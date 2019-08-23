<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version532 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        $query = "SELECT message FROM tblemailtemplates WHERE type='domain' and name='Upcoming Domain Renewal Notice'";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $currentMsgSum = md5($data["message"]);
        $origMsgSum = "7556f88474b1aca229b73b6683735625";
        if ($currentMsgSum == $origMsgSum) {
            $message = "<p>Dear {\$client_name},</p>" . "\n" . "<p>{if \$days_until_expiry}The domain(s) listed below are due to expire within the next {\$days_until_expiry} days.{else}The domain(s) listed below are going to expire in {\$domain_days_until_expiry} days. Renew now before " . "it''s " . "too late...{/if}</p>" . "\n" . "<p>{if \$expiring_domains}{foreach from=\$expiring_domains item=domain}{\$domain.name} - {\$domain.nextduedate} <strong>({\$domain.days} Days)</strong><br />{/foreach}{elseif \$domains}{foreach from=\$domains item=domain}{\$domain.name} - {\$domain.nextduedate}<br />{/foreach}{else}{\$domain_name} - {\$domain_next_due_date} <strong>({\$domain_days_until_nextdue} Days)</strong>{/if}</p>" . "\n" . "<p>To ensure the domain does not expire, you should renew it now. You can do this from the domains management section of our client area here: {\$whmcs_link}</p>" . "\n" . "<p>Should you allow the domain to expire, you will be able to renew it for up to 30 days after the renewal date. During this time, the domain will not be accessible so any web site or email services associated with it will stop working.</p>" . "\n" . "<p>{\$signature}</p>" . "\n";
            $query = "UPDATE tblemailtemplates SET message='" . $message . "' WHERE type='domain' and name='Upcoming Domain Renewal Notice'";
            $result = mysql_query($query);
        }
        return $this;
    }
}

?>