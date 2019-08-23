<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

final class Certificate
{
    private $keyIdentifier = "";
    private $authorityKeyIdentifier = "";
    private $serialNumber = "";
    private $certificateContent = "";
    private $certsDir = "";
    private $certSubdir = "";
    private $isCached = false;
    private $certificateType = 0;
    private $canSignCerts = false;
    private $canSignCrls = false;
    private $canSignCode = false;
    private $isCa = false;
    private $cacheEnabled = true;
    private $x509 = NULL;
    const CACHE_SIGNING_SUBDIR = "signing";
    const CACHE_INTERMEDIATE_SUBDIR = "intermediate";
    const TYPE_CODE_SIGNING = 1;
    const TYPE_INTERMEDIATE = 2;
    public function __construct($certType)
    {
        $this->clear();
        $this->certificateType = $certType;
    }
    public function clear()
    {
        $this->x509 = null;
        $this->isCached = false;
        $this->keyIdentifier = "";
        $this->authorityKeyIdentifier = "";
        $this->serialNumber = "";
        $this->certificateContent = "";
        $this->certificateType = 0;
        $this->canSignCerts = false;
        $this->canSignCrls = false;
        $this->canSignCode = false;
        $this->isCa = false;
        $this->cacheEnabled = true;
        return $this;
    }
    public function getCertsDir()
    {
        return $this->certsDir;
    }
    public function setCertsDir($value)
    {
        if (!is_dir($value)) {
            throw new ComposerUpdateException("Invalid certs cache directory");
        }
        $this->certsDir = $value;
        $this->setSubdir($this->getCertificateType() == self::TYPE_INTERMEDIATE ? self::CACHE_INTERMEDIATE_SUBDIR : self::CACHE_SIGNING_SUBDIR);
        return $this;
    }
    public function getSubdir()
    {
        return $this->certSubdir;
    }
    public function setSubdir($value)
    {
        $fullPath = $this->getCertsDir() . DIRECTORY_SEPARATOR . $value;
        if (!is_dir($fullPath)) {
            throw new ComposerUpdateException("Invalid cert subdirectory");
        }
        $allowedSubdirs = array(self::CACHE_SIGNING_SUBDIR, self::CACHE_INTERMEDIATE_SUBDIR);
        if (!in_array($value, $allowedSubdirs)) {
            throw new ComposerUpdateException("Disallowed subdirectory");
        }
        $this->certSubdir = $value;
        return $this;
    }
    public function getKeyIdentifier()
    {
        return $this->keyIdentifier;
    }
    public function getAuthorityKeyIdentifier()
    {
        return $this->authorityKeyIdentifier;
    }
    public function getCertificateContent()
    {
        return $this->certificateContent;
    }
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }
    public function getIsCa()
    {
        return $this->isCa;
    }
    public function getCanSignCerts()
    {
        return $this->canSignCerts;
    }
    public function getCanSignCrls()
    {
        return $this->canSignCrls;
    }
    public function getCanSignCode()
    {
        return $this->canSignCode;
    }
    public function getCacheEnabled()
    {
        return $this->cacheEnabled;
    }
    public function setCacheEnabled($value)
    {
        $this->cacheEnabled = $value;
        return $this;
    }
    public static function convertKeyIdentifier($base64KeyIdentifier)
    {
        $unpacked = unpack("H*", base64_decode($base64KeyIdentifier));
        return strtoupper($unpacked[1]);
    }
    public function setCertificateContent($value)
    {
        try {
            $this->x509 = new \phpseclib\File\X509();
            $certData = $this->x509->loadX509($value);
            if (!$certData) {
                throw new ComposerUpdateException("Invalid certificate content");
            }
            $this->certificateContent = $value;
            $this->isCached = false;
            $basicConstraints = $this->x509->getExtension("id-ce-basicConstraints");
            if (!is_array($basicConstraints)) {
                throw new ComposerUpdateException("The basicConstraints attribute is not set - certificate cannot be loaded");
            }
            $this->isCa = $basicConstraints["cA"] ? true : false;
            $keyUsage = $this->x509->getExtension("id-ce-keyUsage");
            if (!is_array($keyUsage)) {
                throw new ComposerUpdateException("The keyUsage attribute is not set - certificate cannot be loaded");
            }
            $this->canSignCerts = in_array("keyCertSign", $keyUsage);
            $this->canSignCrls = in_array("cRLSign", $keyUsage);
            $nsCertType = $this->x509->getExtension("netscape-cert-type");
            if (!is_array($nsCertType)) {
                throw new ComposerUpdateException("The nsCertType attribute is not set - certificate cannot be loaded");
            }
            $this->canSignCode = in_array("ObjectSigning", $nsCertType);
            switch ($this->certificateType) {
                case self::TYPE_INTERMEDIATE:
                    if (!$this->isCa) {
                        throw new ComposerUpdateException("An intermediate certificate must have CA bit set");
                    }
                    if (!$this->canSignCerts) {
                        throw new ComposerUpdateException("An intermediate certificate must be able to sign certificates");
                    }
                    if (!$this->canSignCrls) {
                        throw new ComposerUpdateException("An intermediate certificate must be able to sign CRLs");
                    }
                    break;
                case self::TYPE_CODE_SIGNING:
                    if (!$this->canSignCode) {
                        throw new ComposerUpdateException("A code signing certificate must be able to sign code");
                    }
                    break;
                default:
                    throw new ComposerUpdateException("Unsupported certificate type");
            }
            $this->keyIdentifier = self::convertKeyIdentifier($this->x509->currentKeyIdentifier);
            $keyIdentEncoded = $this->x509->getExtension("id-ce-authorityKeyIdentifier");
            if (is_array($keyIdentEncoded) && isset($keyIdentEncoded["keyIdentifier"])) {
                $this->authorityKeyIdentifier = self::convertKeyIdentifier($keyIdentEncoded["keyIdentifier"]);
                $snObject = $certData["tbsCertificate"]["serialNumber"];
                $this->serialNumber = $snObject->toString();
                return $this;
            }
            throw new ComposerUpdateException("Failed to decode key information");
        } catch (\Exception $e) {
            $this->clear();
            throw $e;
        }
    }
    private function getCacheFilename($keyIdentifier)
    {
        $fingerprint = strtoupper($keyIdentifier);
        return $this->getCertsDir() . DIRECTORY_SEPARATOR . $this->getSubdir() . DIRECTORY_SEPARATOR . $fingerprint . ".crt";
    }
    public function loadFromCache($keyIdentifier)
    {
        $cacheFile = $this->getCacheFilename($keyIdentifier);
        if (file_exists($cacheFile)) {
            $this->setCertificateContent(file_get_contents($cacheFile));
            $this->isCached = true;
            return true;
        }
        return false;
    }
    public function checkLoaded()
    {
        if (!$this->isLoaded()) {
            throw new ComposerUpdateException("Certificate not loaded");
        }
    }
    public function saveToCache()
    {
        $this->checkLoaded();
        $cachedFile = $this->getCacheFilename($this->keyIdentifier);
        if (false === file_put_contents($cachedFile, $this->getCertificateContent())) {
            throw new ComposerUpdateException("Failed to store certificate: " . $this->keyIdentifier);
        }
        $this->isCached = true;
        return $this;
    }
    public function deleteFromCache()
    {
        $this->checkLoaded();
        $cachedFile = $this->getCacheFilename($this->keyIdentifier);
        if (file_exists($cachedFile) && !unlink($cachedFile)) {
            throw new ComposerUpdateException("Failed to delete certificate: " . $this->keyIdentifier);
        }
        return $this;
    }
    public function getIsCached()
    {
        return $this->isCached;
    }
    public function isLoaded()
    {
        return $this->x509 instanceof \phpseclib\File\X509 ? true : false;
    }
    public function getCertificateType()
    {
        return $this->certificateType;
    }
    public function validateSignedBy(Certificate $caCertificate)
    {
        $this->checkLoaded();
        $caCertificate->checkLoaded();
        if (!$caCertificate->getIsCa()) {
            throw new ComposerUpdateException("Specified certificate is supposed to be CA but is not marked as CA");
        }
        if (!$caCertificate->getCanSignCerts()) {
            throw new ComposerUpdateException("Specified CA certificate cannot sign certificates");
        }
        $cert = new \phpseclib\File\X509();
        if (!$cert->loadCA($caCertificate->getCertificateContent())) {
            throw new ComposerUpdateException("Failed to load the CA certificate");
        }
        if (!$cert->loadX509($this->getCertificateContent())) {
            throw new ComposerUpdateException("Failed to load certificate - it may be damaged");
        }
        if ($cert->validateSignature()) {
            if (!$this->getIsCached() && $this->getCacheEnabled()) {
                $this->saveToCache();
            }
            return $this;
        }
        if ($this->getIsCached()) {
            $this->deleteFromCache();
        }
        throw new ComposerUpdateException("Failed to validate certificate signature");
    }
}

?>