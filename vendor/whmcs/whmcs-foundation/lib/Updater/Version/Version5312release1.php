<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version5312release1 extends IncrementalVersion
{
    protected $runUpdateCodeBeforeDatabase = true;
    protected function runUpdateCode()
    {
        mysql_query("ALTER TABLE tblproducts DROP COLUMN upgradechargefullcycle");
        $subject = "Closed Ticket Bounce Message";
        $message = "<p>{\$client_name},</p>" . PHP_EOL . "<p>Your email to our ticket system could not be accepted because the ticket" . " being responded to has already been closed.</p>" . PHP_EOL . "<p>{if \$client_id}If you wish to reopen this ticket, you can do so from our client area:" . PHP_EOL . "{\$ticket_link}" . "{else}To open a new ticket, please visit:</p>" . PHP_EOL . "<p><a href=\"{\$whmcs_url}/submitticket.php\">{\$whmcs_url}/submitticket.php</a>" . "{/if}</p>" . PHP_EOL . "<p>This is an automated response from our support system.</p>" . PHP_EOL . "<p>{\$signature}</p>";
        $query = "INSERT INTO `tblemailtemplates`\n(`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`,\n  `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES\n('', 'support', 'Closed Ticket Bounce Message', '" . $subject . "', '" . $message . "', '', '', '', '', '', '', '', 0);";
        mysql_query($query);
        $query = "SELECT count(*) FROM tblemailtemplates WHERE name=\"Expired Domain Notice\"";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $templateExists = !empty($data[0]) ? true : false;
        if (!$templateExists) {
            $subject = "Expired Domain Notice";
            $message = "<p>Dear {\$client_name},</p>" . PHP_EOL . "<p>The domain name listed below expired {\$domain_days_after_expiry} ago.</p>" . PHP_EOL . "<p>{\$domain_name}</p>" . PHP_EOL . "<p>To ensure the domain does become registered by someone else, you should renew it now." . " To renew the domain, please visit the following page and follow the steps shown:" . " <a title=\"{\$whmcs_url}/cart.php?gid=renewals\"" . " href=\"{\$whmcs_url}/cart.php?gid=renewals\">{\$whmcs_url}/cart.php?gid=renewals</a>" . "</p>" . PHP_EOL . "<p>Due to the domain expiring, the domain will not be accessible so any" . " web site or email services associated with it will stop working. You may be able to renew it for up to" . " 30 days after the renewal date.</p>" . PHP_EOL . "<p>{\$signature}</p>";
            $query = "INSERT INTO `tblemailtemplates`\n(`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`,\n  `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES\n('', 'domain', 'Expired Domain Notice', '" . $subject . "', '" . $message . "', '', '', '', '', '', '', '', 0);";
            mysql_query($query);
        }
        $tableCreate = "CREATE TABLE IF NOT EXISTS `tbldomainreminders` (\n  `id` int(10) NOT NULL AUTO_INCREMENT,\n  `domain_id` int(10) NOT NULL,\n  `date` date NOT NULL,\n  `recipients` text COLLATE utf8_unicode_ci NOT NULL,\n  `type` tinyint(4) NOT NULL,\n  `days_before_expiry` tinyint(4) NOT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8  COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
        mysql_query($tableCreate);
        return $this;
    }
}

?>