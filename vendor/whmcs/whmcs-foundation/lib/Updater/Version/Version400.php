<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version400 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        global $license;
        include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "functions.php";
        if (empty($_REQUEST["nomd5"]) || !$_REQUEST["nomd5"]) {
            $query = "SELECT id, password FROM tblclients";
            $result = mysql_query($query);
            while ($data = mysql_fetch_assoc($result)) {
                $password = decrypt($data["password"]);
                $password = $this->v4GenerateClientPW($password);
                $id = $data["id"];
                $upd_query = "UPDATE tblclients SET password = '" . $password . "' WHERE id = " . $id . ";";
                mysql_query($upd_query);
            }
            $query = "INSERT into tblconfiguration VALUES ('NOMD5', '');";
            mysql_query($query);
        } else {
            $query = "INSERT into tblconfiguration VALUES ('NOMD5', 'on');";
            mysql_query($query);
        }
        $query = "SELECT id, category FROM tblknowledgebase";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $category = $data["category"];
            $query = "INSERT INTO tblknowledgebaselinks (categoryid,articleid) VALUES ('" . $category . "','" . $id . "')";
            mysql_query($query);
        }
        mysql_query("ALTER TABLE `tblknowledgebase` DROP `category`");
        $existingcurrency = array();
        $query = "SELECT * FROM tblconfiguration WHERE setting LIKE 'Currency%'";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $existingcurrency[$data["setting"]] = $data["value"];
        }
        $query = "TRUNCATE tblcurrencies";
        mysql_query($query);
        $query = "INSERT INTO `tblcurrencies` (`id`, `code`, `prefix`, `suffix`, `format`, `rate`, `default`) VALUES (1, '" . $existingcurrency["Currency"] . "', '" . $existingcurrency["CurrencySymbol"] . "', ' " . $existingcurrency["Currency"] . "', 1, 1.00000, 1)";
        mysql_query($query);
        $query = "DELETE FROM tblconfiguration WHERE setting='Currency' OR setting='CurrencySymbol'";
        mysql_query($query);
        $query = "SELECT * FROM tblproducts WHERE paytype!='free' ORDER BY id ASC";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $paytype = $data["paytype"];
            $msetupfee = $data["msetupfee"];
            $qsetupfee = $data["qsetupfee"];
            $ssetupfee = $data["ssetupfee"];
            $asetupfee = $data["asetupfee"];
            $bsetupfee = $data["bsetupfee"];
            $monthly = $data["monthly"];
            $quarterly = $data["quarterly"];
            $semiannual = $data["semiannual"];
            $annual = $data["annual"];
            $biennial = $data["biennial"];
            if ($paytype == "recurring") {
                if ($monthly <= 0) {
                    $monthly = "-1";
                }
                if ($quarterly <= 0) {
                    $quarterly = "-1";
                }
                if ($semiannual <= 0) {
                    $semiannual = "-1";
                }
                if ($annual <= 0) {
                    $annual = "-1";
                }
                if ($biennial <= 0) {
                    $biennial = "-1";
                }
            }
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('product','1','" . $id . "','" . $msetupfee . "','" . $qsetupfee . "','" . $ssetupfee . "','" . $asetupfee . "','" . $bsetupfee . "','" . $monthly . "','" . $quarterly . "','" . $semiannual . "','" . $annual . "','" . $biennial . "')";
            mysql_query($query);
        }
        $query = "SELECT * FROM tblproductconfigoptionssub ORDER BY id ASC";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $setup = $data["setup"];
            $monthly = $data["monthly"];
            $quarterly = $data["quarterly"];
            $semiannual = $data["semiannual"];
            $annual = $data["annual"];
            $biennial = $data["biennial"];
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('configoptions','1','" . $id . "','" . $setup . "','" . $setup . "','" . $setup . "','" . $setup . "','" . $setup . "','" . $monthly . "','" . $quarterly . "','" . $semiannual . "','" . $annual . "','" . $biennial . "')";
            mysql_query($query);
        }
        $query = "SELECT * FROM tbladdons ORDER BY id ASC";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $setupfee = $data["setupfee"];
            $recurring = $data["recurring"];
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('addon','1','" . $id . "','" . $setupfee . "','0','0','0','0','" . $recurring . "','0','0','0','0')";
            mysql_query($query);
        }
        $domainpricing = array();
        $query = "SELECT * FROM tbldomainpricing ORDER BY id ASC";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $extension = $data["extension"];
            $regperiod = $data["registrationperiod"];
            if ($data["register"] != "0.00" && $data["transfer"] <= 0) {
                $data["transfer"] = "-1";
            }
            if ($data["register"] != "0.00" && $data["renew"] <= 0) {
                $data["renew"] = "-1";
            }
            $domainpricing[$extension][$regperiod]["register"] = $data["register"];
            $domainpricing[$extension][$regperiod]["transfer"] = $data["transfer"];
            $domainpricing[$extension][$regperiod]["renew"] = $data["renew"];
        }
        $query = "SELECT DISTINCT extension FROM tbldomainpricing";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $extension = $data["extension"];
            $query = "SELECT id FROM tbldomainpricing WHERE extension='" . $extension . "' ORDER BY registrationperiod ASC";
            $result2 = mysql_query($query);
            $data = mysql_fetch_assoc($result2);
            $id = $data["id"];
            $query = "DELETE FROM tbldomainpricing WHERE extension='" . $extension . "' AND id!='" . $id . "'";
            mysql_query($query);
        }
        $query = "SELECT * FROM tbldomainpricing ORDER BY id ASC";
        $result = mysql_query($query);
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $extension = $data["extension"];
            $inserttype = "register";
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('domain" . $inserttype . "','1','" . $id . "','" . $domainpricing[$extension][1][$inserttype] . "','" . $domainpricing[$extension][2][$inserttype] . "','" . $domainpricing[$extension][3][$inserttype] . "','" . $domainpricing[$extension][4][$inserttype] . "','" . $domainpricing[$extension][5][$inserttype] . "','" . $domainpricing[$extension][6][$inserttype] . "','" . $domainpricing[$extension][7][$inserttype] . "','" . $domainpricing[$extension][8][$inserttype] . "','" . $domainpricing[$extension][9][$inserttype] . "','" . $domainpricing[$extension][10][$inserttype] . "')";
            mysql_query($query);
            $inserttype = "transfer";
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('domain" . $inserttype . "','1','" . $id . "','" . $domainpricing[$extension][1][$inserttype] . "','" . $domainpricing[$extension][2][$inserttype] . "','" . $domainpricing[$extension][3][$inserttype] . "','" . $domainpricing[$extension][4][$inserttype] . "','" . $domainpricing[$extension][5][$inserttype] . "','" . $domainpricing[$extension][6][$inserttype] . "','" . $domainpricing[$extension][7][$inserttype] . "','" . $domainpricing[$extension][8][$inserttype] . "','" . $domainpricing[$extension][9][$inserttype] . "','" . $domainpricing[$extension][10][$inserttype] . "')";
            mysql_query($query);
            $inserttype = "renew";
            $query = "INSERT INTO tblpricing (type,currency,relid,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,monthly,quarterly,semiannually,annually,biennially) VALUES ('domain" . $inserttype . "','1','" . $id . "','" . $domainpricing[$extension][1][$inserttype] . "','" . $domainpricing[$extension][2][$inserttype] . "','" . $domainpricing[$extension][3][$inserttype] . "','" . $domainpricing[$extension][4][$inserttype] . "','" . $domainpricing[$extension][5][$inserttype] . "','" . $domainpricing[$extension][6][$inserttype] . "','" . $domainpricing[$extension][7][$inserttype] . "','" . $domainpricing[$extension][8][$inserttype] . "','" . $domainpricing[$extension][9][$inserttype] . "','" . $domainpricing[$extension][10][$inserttype] . "')";
            mysql_query($query);
        }
        mysql_query("ALTER TABLE `tblproducts` DROP `msetupfee`,DROP `qsetupfee`,DROP `ssetupfee`,DROP `asetupfee`,DROP `bsetupfee`,DROP `monthly`,DROP `quarterly`,DROP `semiannual`,DROP `annual`,DROP `biennial`");
        mysql_query("ALTER TABLE `tbldomainpricing`  DROP `registrationperiod`,  DROP `register`,  DROP `transfer`,  DROP `renew`");
        mysql_query("ALTER TABLE `tblproductconfigoptionssub` DROP `setup`,DROP `monthly`,DROP `quarterly`,DROP `semiannual`,DROP `annual`,DROP `biennial`");
        mysql_query("ALTER TABLE `tbladdons`  DROP `recurring`,  DROP `setupfee`");
        mysql_query("ALTER TABLE `mod_licensing` ADD `lastaccess` DATE NOT NULL");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.whmcs.com/license/v4upgrade.php?licensekey=" . $license);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $this;
    }
    protected function v4GenerateClientPW($plain, $salt = "")
    {
        if (!$salt) {
            $seeds = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ#!%()#!%()#!%()";
            $seeds_count = strlen($seeds) - 1;
            for ($i = 0; $i < 5; $i++) {
                $salt .= $seeds[rand(0, $seeds_count)];
            }
        }
        $pw = md5($salt . html_entity_decode($plain)) . ":" . $salt;
        return $pw;
    }
}

?>