<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class PackagesFile
{
    protected static function flattenMessageArray(array $data)
    {
        static $maxDepth = 10;
        $cleanContent = function ($content) {
            return str_replace("", "", $content);
        };
        $items = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                try {
                    if (0 < --$maxDepth) {
                        $value = static::flattenMessageArray($value);
                    } else {
                        throw new \WHMCS\Exception("Max depth reached when validating notification messages");
                    }
                } finally {
                    $maxDepth++;
                }
            } else {
                $value = $cleanContent($value);
                $items[] = $cleanContent($key) . "" . $value;
            }
        }
        return implode("", $items);
    }
    public static function getValidationHashBase($packageVersion, array $messages)
    {
        return $packageVersion . "" . static::flattenMessageArray($messages);
    }
    protected function generateValidationHash(\WHMCS\Version\SemanticVersion $packageVersion, array $messages)
    {
        $hashBase = static::getValidationHashBase($packageVersion->getCanonical(), $messages);
        return hash("sha256", $hashBase, true);
    }
    protected function isNotificationsSignatureValid(\WHMCS\Version\SemanticVersion $packageVersion, array $messages, $signature, $certificate)
    {
        $x509 = new \phpseclib\File\X509();
        if (!$x509->loadX509($certificate)) {
            throw new ComposerUpdateException("Cannot load validation certificate");
        }
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->loadKey($x509->getPublicKey());
        $rsa->setSignatureMode(\phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
        return $rsa->verify($this->generateValidationHash($packageVersion, $messages), base64_decode($signature));
    }
    private function replacePackageNotificationsWithPlaceholder(array $package)
    {
        $placeholderNotification = new \WHMCS\Installer\Update\UpdateNotification("Update Message Validation Issue", "This update contains notification messages that WHMCS could not validate." . " Please contact support to know more.", \WHMCS\Installer\Update\UpdateNotification::STYLE_WARNING, "fa-asterisk", "I understand, continue with update");
        $package["extra"]["notifications"] = array("messages" => array($placeholderNotification->toArray()), "sig" => "");
        return $package;
    }
    public function validateNotificationSignatures(array $data)
    {
        if (!isset($data["packages"][ComposerWrapper::PACKAGE_NAME])) {
            throw new ComposerUpdateException("Missing required package name");
        }
        $updateTempPath = \WHMCS\Config\Setting::getValue("UpdateTempPath");
        if (!is_dir($updateTempPath) || !is_writable($updateTempPath)) {
            $updateTempPath = sys_get_temp_dir();
        }
        $certificateManager = new CertificateManager($updateTempPath);
        $minVersionWithNotifications = new \WHMCS\Version\SemanticVersion("7.5.0-alpha.2");
        foreach ($data["packages"][ComposerWrapper::PACKAGE_NAME] as $versionString => &$package) {
            $packageVersion = new \WHMCS\Version\SemanticVersion($versionString);
            if (!isset($package["extra"]["notifications"])) {
                if (!\WHMCS\Version\SemanticVersion::compare($packageVersion, $minVersionWithNotifications, "<")) {
                    $package = $this->replacePackageNotificationsWithPlaceholder($package);
                }
                continue;
            }
            $notifications = $package["extra"]["notifications"];
            if (!isset($notifications["messages"]) || empty($notifications["sig"])) {
                $package = $this->replacePackageNotificationsWithPlaceholder($package);
                continue;
            }
            try {
                $certificate = $certificateManager->getValidCodeSigningCertificateContent($package["extra"]["keyIdentifier"]);
                if (!$this->isNotificationsSignatureValid($packageVersion, $notifications["messages"], $notifications["sig"], $certificate)) {
                    throw new \WHMCS\Exception("Invalid notifications signature");
                }
            } catch (\Exception $e) {
                $package = $this->replacePackageNotificationsWithPlaceholder($package);
            }
        }
        unset($package);
        return $data;
    }
}

?>