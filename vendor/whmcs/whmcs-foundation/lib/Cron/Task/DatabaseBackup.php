<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class DatabaseBackup extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $accessLevel = \WHMCS\Scheduling\Task\TaskInterface::ACCESS_SYSTEM;
    protected $defaultPriority = 5000;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Create a database backup and deliver via FTP or email";
    protected $defaultName = "Database Backup";
    protected $systemName = "DatabaseBackup";
    protected $outputs = array("completed" => array("defaultValue" => 0, "identifier" => "completed", "name" => "Backup Completed"));
    protected $icon = "fas fa-database";
    protected $isBooleanStatus = true;
    protected $successCountIdentifier = "completed";
    protected $zipFile = NULL;
    public function __invoke()
    {
        $this->doCpanel();
        $this->doFtpAndEmail();
        return $this;
    }
    protected function doCpanel()
    {
        $complete = false;
        if ($this->isBackupSystemActive("cpanel")) {
            try {
                $this->requestCPanelBackup();
                logActivity("Cron Job: Remote cPanel Backup Requested");
                $complete = true;
            } catch (\Exception $e) {
                logActivity("Cron Job: cPanel Remote Backup Failed" . $e->getMessage());
            }
        }
        return $complete;
    }
    protected function doFtpAndEmail()
    {
        $complete = false;
        if ($this->isBackupSystemActive("email") || $this->isBackupSystemActive("ftp")) {
            if (class_exists("ZipArchive")) {
                try {
                    if (!$this->generateDatabaseBackupZipFile()) {
                        throw new \WHMCS\Exception("Backup File Generation Failed");
                    }
                    $whmcsApplicationConfig = \App::getApplicationConfig();
                    $databaseName = $whmcsApplicationConfig->getDatabaseName();
                    $attachmentName = sprintf("%s_backup_%s.zip", $databaseName, date("Ymd_His"));
                    if ($this->isBackupSystemActive("email")) {
                        $this->emailZip($this->zipFile, $attachmentName);
                        logActivity("Cron Job: Email Backup - Sent Successfully");
                    }
                    if ($this->isBackupSystemActive("ftp")) {
                        $remoteFile = \WHMCS\Config\Setting::getValue("FTPBackupDestination") . $attachmentName;
                        $this->ftpZip($this->zipFile, $remoteFile);
                        logActivity("Cron Job: FTP Backup - Completed Successfully");
                    }
                    $msg = "Backup Complete";
                    $this->output("completed")->write(1);
                    $complete = true;
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    $msg = "Database Backup Sending Failed - PHPMailer Exception" . " - " . $e->getMessage() . "(Subject: WHMCS Database Backup)";
                    $this->output("completed")->write(0);
                } catch (\Exception $e) {
                    $this->output("completed")->write(0);
                    $msg = $e->getMessage();
                }
                unlink($this->zipFile);
            } else {
                $this->output("completed")->write(0);
                $msg = "Database backup unavailable due to missing required Zip extension";
            }
        } else {
            $this->output("completed")->write(0);
            $msg = "Database Backup requested but backups are not configured.";
        }
        logActivity("Cron Job: " . $msg);
        return $complete;
    }
    protected function isBackupSystemActive($system)
    {
        $activeBackupSystems = \WHMCS\Config\Setting::getValue("ActiveBackupSystems");
        if ($activeBackupSystems) {
            $activeBackupSystems = explode(",", $activeBackupSystems);
        }
        if (!is_array($activeBackupSystems)) {
            $activeBackupSystems = array();
        }
        if (0 < count($activeBackupSystems) && in_array($system, $activeBackupSystems)) {
            return true;
        }
        return false;
    }
    protected function generateDatabaseBackupZipFile()
    {
        $tempZipFile = tempnam(sys_get_temp_dir(), "zip");
        $tempDatabaseFile = tempnam(sys_get_temp_dir(), "sql");
        $whmcsApplicationConfig = \App::getApplicationConfig();
        $databaseName = $whmcsApplicationConfig->getDatabaseName();
        $complete = false;
        try {
            logActivity("Cron Job: Starting Backup Generation");
            logActivity("Cron Job: Starting Backup Database Dump");
            $databaseConnection = \App::getDatabaseObj();
            $database = new \WHMCS\Database\Dumper\Database($databaseConnection);
            $database->dumpTo($tempDatabaseFile);
            logActivity("Cron Job: Backup Database Dump Complete");
            $zipFileForSend = $this->createZipFile($tempZipFile, $tempDatabaseFile, $databaseName);
            if (!file_exists($zipFileForSend)) {
                throw new \WHMCS\Exception("An unknown error occurred adding the generated sql to the archive.");
            }
            $this->zipFile = $zipFileForSend;
            $complete = true;
        } catch (\Exception $e) {
            logActivity("Cron Job: ERROR : " . $e->getMessage());
        }
        unlink($tempDatabaseFile);
        return $complete;
    }
    protected function createZipFile($tempZipFile, $tempDatabaseFile, $databaseName)
    {
        logActivity("Cron Job: Starting Backup Zip Creation");
        $zip = new \ZipArchive();
        $res = $zip->open($tempZipFile, \ZipArchive::CREATE);
        if ($res !== true) {
            $msg = "Cron Job: Backup Generation Failed. Error Code: " . $res;
            logActivity($msg);
        } else {
            $filename = (string) $databaseName . ".sql";
            if (!$zip->addFile($tempDatabaseFile, $filename)) {
                throw new \WHMCS\Exception("An unknown error occurred adding the generated sql to the archive");
            }
            $zip->setArchiveComment("WHMCS Generated MySQL Backup");
            $zip->close();
            logActivity("Cron Job: Backup Generation Completed");
        }
        return $tempZipFile;
    }
    protected function emailZip($zipFile, $attachmentName)
    {
        if (!\WHMCS\Config\Setting::getValue("DailyEmailBackup")) {
            throw new \WHMCS\Exception("No Daily Email Address Configured");
        }
        $mail = new \WHMCS\Mail(\WHMCS\Config\Setting::getValue("SystemEmailsFromName"), \WHMCS\Config\Setting::getValue("SystemEmailsFromEmail"));
        $mail->Subject = "WHMCS Database Backup";
        $mail->Body = "Backup File Attached";
        $mail->AddAddress(\WHMCS\Config\Setting::getValue("DailyEmailBackup"));
        $mail->AddAttachment($zipFile, $attachmentName);
        $result = $mail->Send();
        $mail->clearAllRecipients();
        if (!$result) {
            throw new \WHMCS\Exception($mail->ErrorInfo);
        }
        return $this;
    }
    protected function ftpZip($zipFile, $remoteFile)
    {
        $ftpSecureMode = \WHMCS\Config\Setting::getValue("FTPSecureMode");
        $ftpSecureMode ? $this->doSftpBackup($zipFile, $remoteFile) : $this->doFtpBackup($zipFile, $remoteFile);
        return $this;
    }
    protected function requestCPanelBackup()
    {
        $server = new \WHMCS\Module\Server();
        $server->load("cpanel");
        $server->call("request_backup", array("serverip" => "", "serverhostname" => \WHMCS\Config\Setting::getValue("CpanelBackupHostname"), "serverusername" => \WHMCS\Config\Setting::getValue("CpanelBackupWHMUsername"), "serveraccesshash" => decrypt(\WHMCS\Config\Setting::getValue("CpanelBackupAPIToken")), "serverhttpprefix" => "https", "serverport" => "2087", "serversecure" => true, "dest" => \WHMCS\Config\Setting::getValue("CpanelBackupDestination"), "hostname" => \WHMCS\Config\Setting::getValue("CpanelBackupDestinationHostname"), "user" => \WHMCS\Config\Setting::getValue("CpanelBackupDestinationUser"), "pass" => decrypt(\WHMCS\Config\Setting::getValue("CpanelBackupDestinationPassword")), "email" => \WHMCS\Config\Setting::getValue("CpanelBackupNotifyEmail"), "port" => \WHMCS\Config\Setting::getValue("CpanelBackupDestinationPort"), "rdir" => \WHMCS\Config\Setting::getValue("CpanelBackupDestinationDirectory"), "username" => \WHMCS\Config\Setting::getValue("CpanelBackupUsername")));
        return $this;
    }
    protected function doSftpBackup($zipFile, $remoteFile)
    {
        $ftp_server = \WHMCS\Config\Setting::getValue("FTPBackupHostname");
        if (!$ftp_server) {
            throw new \WHMCS\Exception("SFTP Hostname Required");
        }
        $ftp_port = \WHMCS\Config\Setting::getValue("FTPBackupPort");
        $ftp_user = \WHMCS\Config\Setting::getValue("FTPBackupUsername");
        $ftp_pass = decrypt(\WHMCS\Config\Setting::getValue("FTPBackupPassword"));
        if (!$ftp_port) {
            $ftp_port = "22";
        }
        $ftp_server = str_replace(array("ftp://", "sftp://"), "", $ftp_server);
        $sftp = new \phpseclib\Net\SFTP($ftp_server, $ftp_port);
        if (!@$sftp->login($ftp_user, $ftp_pass)) {
            throw new \WHMCS\Exception("SFTP Backup - Login Failed");
        }
        $upload = $sftp->put($remoteFile, $zipFile, \phpseclib\Net\SFTP::SOURCE_LOCAL_FILE);
        $sftp->disconnect();
        if (!$upload) {
            throw new \WHMCS\Exception("SFTP Backup - Uploading Failed");
        }
        return $this;
    }
    protected function doFtpBackup($zipFile, $remoteFile)
    {
        $ftp_server = \WHMCS\Config\Setting::getValue("FTPBackupHostname");
        if (!$ftp_server) {
            throw new \WHMCS\Exception("FTP Hostname Required");
        }
        $ftp_port = \WHMCS\Config\Setting::getValue("FTPBackupPort");
        $ftp_user = \WHMCS\Config\Setting::getValue("FTPBackupUsername");
        $ftp_pass = decrypt(\WHMCS\Config\Setting::getValue("FTPBackupPassword"));
        if (!$ftp_port) {
            $ftp_port = "21";
        }
        $ftp_server = str_replace("ftp://", "", $ftp_server);
        $ftpConnection = @ftp_connect($ftp_server, $ftp_port ?: 21);
        if (!$ftpConnection) {
            throw new \WHMCS\Exception("FTP Backup - Could not connect to " . $ftp_server);
        }
        if (!ftp_login($ftpConnection, $ftp_user, $ftp_pass)) {
            throw new \WHMCS\Exception("FTP Backup - Login Failed");
        }
        if (\WHMCS\Config\Setting::getValue("FTPPassiveMode")) {
            ftp_pasv($ftpConnection, true);
        }
        $upload = ftp_put($ftpConnection, $remoteFile, $zipFile, FTP_BINARY);
        ftp_close($ftpConnection);
        if (!$upload) {
            throw new \WHMCS\Exception("FTP Backup - Uploading Failed");
        }
        return $this;
    }
}

?>