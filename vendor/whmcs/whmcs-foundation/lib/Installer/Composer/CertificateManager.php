<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class CertificateManager
{
    private $updateTempDir = "";
    private $certsDir = "";
    private $certMetadata = array();
    private $environmentErrors = array();
    private $rootCaCertificateKeyIdentifier = "";
    private $trustedCertificates = array();
    private $validationChain = array();
    private $authorityOfCrlCurrentlyObtained = "";
    private $crls = array();
    const KEYSERVER_URL_TESTING = "https://pki.dev.whmcs.com/";
    const KEYSERVER_URL_PRODUCTION = "https://pki.whmcs.com/";
    const CERTS_CACHE_SUBDIR = "certs";
    const MAX_CA_LEVELS = 2;
    const ROOT_CA_CERTIFICATE_CONTENT = "-----BEGIN CERTIFICATE-----\n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\n-----END CERTIFICATE-----";
    public function __construct($updateTempPath)
    {
        $this->setUpdateTempDir($updateTempPath)->initEnvironment();
    }
    public function getUpdateTempDir()
    {
        return $this->updateTempDir;
    }
    public function setUpdateTempDir($value)
    {
        $this->updateTempDir = $value;
        return $this;
    }
    public function getRootCaCertificateContent()
    {
        return self::ROOT_CA_CERTIFICATE_CONTENT;
    }
    private function getRootCaKeyIdentifier()
    {
        if (!$this->rootCaCertificateKeyIdentifier) {
            $x509 = new \phpseclib\File\X509();
            if (!$x509->loadX509($this->getRootCaCertificateContent())) {
                throw new ComposerUpdateException("Cannot load built-in Root CA certificate");
            }
            $this->rootCaCertificateKeyIdentifier = Certificate::convertKeyIdentifier($x509->currentKeyIdentifier);
        }
        return $this->rootCaCertificateKeyIdentifier;
    }
    private function initEnvironment()
    {
        $certsDirectory = "";
        $this->environmentErrors = UpdateEnvironment::initEnvironment($this->updateTempDir);
        if (empty($this->environmentErrors)) {
            $certsDirectory = $this->updateTempDir . DIRECTORY_SEPARATOR . self::CERTS_CACHE_SUBDIR;
            $directoriesToCreate = array($certsDirectory, $certsDirectory . DIRECTORY_SEPARATOR . Certificate::CACHE_SIGNING_SUBDIR, $certsDirectory . DIRECTORY_SEPARATOR . Certificate::CACHE_INTERMEDIATE_SUBDIR);
            foreach ($directoriesToCreate as $newDir) {
                if (!is_dir($newDir) && !@mkdir($newDir)) {
                    $this->environmentErrors[] = \AdminLang::trans("update.notWritablePath", array(":path" => $newDir));
                    break;
                }
            }
        }
        $this->validateEnvironment();
        $this->certsDir = $certsDirectory;
        return $this;
    }
    public function getEnvironmentErrors()
    {
        return $this->environmentErrors;
    }
    public function isEnvironmentValid()
    {
        $this->environmentErrors = array_filter($this->environmentErrors);
        return empty($this->environmentErrors) ? true : false;
    }
    private function validateEnvironment()
    {
        if (!$this->isEnvironmentValid()) {
            throw new ComposerUpdateException(implode("\n", $this->environmentErrors));
        }
        return $this;
    }
    private function getUrl($url)
    {
        $allowedNonSslHosts[] = "keys.dev.whmcs.com";
        if ("https" !== strtolower(parse_url($url, PHP_URL_SCHEME)) && !in_array(strtolower(parse_url($url, PHP_URL_HOST)), $allowedNonSslHosts)) {
            throw new ComposerUpdateException("Cannot work with non-SSL URLs");
        }
        $handler = new \GuzzleHttp\Ring\Client\StreamHandler();
        $guzzle = new \GuzzleHttp\Client(array("verify" => true, "handler" => $handler));
        try {
            $result = $guzzle->get($url);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new ComposerUpdateException("Unable to download file from URL: " . $url . " Message " . $e->getMessage());
        }
        return (string) $result->getBody();
    }
    public static function getKeyServerUrl()
    {
        return ComposerUpdate::isUsingInternalUpdateResources() ? self::KEYSERVER_URL_TESTING : self::KEYSERVER_URL_PRODUCTION;
    }
    public function getPathFromKeyserver($path)
    {
        return $this->getUrl(self::getKeyServerUrl() . $path);
    }
    private function getCertMetadata()
    {
        $this->validateEnvironment();
        if (empty($this->certMetadata)) {
            try {
                $jsonContent = $this->getPathFromKeyserver("certs.json");
            } catch (ComposerUpdateException $e) {
                throw new ComposerUpdateException("Failed to get certificate metadata from keyserver. Error: " . $e->getMessage());
            }
            $jsonCertData = json_decode($jsonContent, true);
            if (is_array($jsonCertData)) {
                $this->certMetadata = $jsonCertData;
            } else {
                throw new ComposerUpdateException("Failed to decode certificate metadata from the keyserver");
            }
        }
        return $this->certMetadata;
    }
    private function parseCrlPackage($crlPackage)
    {
        while (!empty($crlPackage)) {
            $crl = CertificateCrl::popFromPackage($crlPackage);
            if (is_null($crl)) {
                break;
            }
            $crlAuthorityKeyIdentifier = $crl->getAuthorityKeyIdentifier();
            if (strlen($crlAuthorityKeyIdentifier) === 0) {
                throw new ComposerUpdateException("Invalid CRL");
            }
            if (strcasecmp($crlAuthorityKeyIdentifier, $this->getRootCaKeyIdentifier()) === 0) {
                $crlAuthorityCertificate = $this->createCertificate(Certificate::TYPE_INTERMEDIATE, $this->getRootCaCertificateContent());
            } else {
                $crlAuthorityCertificate = $this->getTrustedCertificate($crlAuthorityKeyIdentifier, Certificate::TYPE_INTERMEDIATE);
            }
            $crl->validateSignedBy($crlAuthorityCertificate);
            $this->crls[$crl->getAuthorityKeyIdentifier()] = $crl;
        }
        return $this;
    }
    private function reloadCrls()
    {
        $this->getCertMetadata();
        $this->crls = array();
        $crls = array();
        try {
            $crlUrls = $this->certMetadata["crls"];
            foreach ($crlUrls as $url) {
                $crls[] = $this->getPathFromKeyserver($url);
            }
            $crlPackage = implode("\n", $crls);
        } catch (ComposerUpdateException $e) {
            throw new ComposerUpdateException("Failed to get CRL package from keyserver. Error: " . $e->getMessage());
        }
        $this->parseCrlPackage($crlPackage);
        return $this;
    }
    private function getCrlForCertificate(Certificate $certificate)
    {
        $crlKey = $certificate->getAuthorityKeyIdentifier();
        if (empty($this->crls)) {
            if ($crlKey == $this->authorityOfCrlCurrentlyObtained) {
                throw new ComposerUpdateException("Invalid CRL chain, repeat CRL request for authority: " . $crlKey . " It means that a CRL signed by this authority was not found in the CRL package.");
            }
            $this->authorityOfCrlCurrentlyObtained = $crlKey;
            $this->reloadCrls();
        }
        try {
            if (isset($this->crls[$crlKey])) {
                return $this->crls[$crlKey];
            }
            $certificate->deleteFromCache();
            throw new ComposerUpdateException("Unable to find a suitable CRL for authority key identifier: " . $crlKey);
        } catch (\Exception $e) {
            $this->authorityOfCrlCurrentlyObtained = "";
            throw $e;
        }
    }
    private function validateCertificateNotRevoked(Certificate $certificate)
    {
        $this->getCrlForCertificate($certificate)->checkCertificateNotRevoked($certificate);
        return $this;
    }
    private function addTrustedCertificate(Certificate $certificate)
    {
        $this->trustedCertificates[$certificate->getKeyIdentifier()] = $certificate;
        return $this;
    }
    private function createCertificate($certificateType, $certificateContent = "")
    {
        $certificate = new Certificate($certificateType);
        $certificate->setCertsDir($this->certsDir);
        if ($certificateContent) {
            $certificate->setCertificateContent($certificateContent);
        }
        return $certificate;
    }
    private function obtainCertificate($keyIdentifier, $certType)
    {
        $suppliedKeyIdentifier = $keyIdentifier;
        $keyIdentifier = preg_replace("/[^a-z0-9]+/i", "", trim($keyIdentifier));
        if ($keyIdentifier === "") {
            throw new ComposerUpdateException("Invalid key identifier: " . $suppliedKeyIdentifier);
        }
        $certificate = $this->createCertificate($certType);
        if (!$certificate->loadFromCache($keyIdentifier)) {
            $this->getCertMetadata();
            $metadataKey = $certType == Certificate::TYPE_INTERMEDIATE ? "intermediate_cert_path" : "signing_cert_path";
            try {
                $certificateContent = $this->getPathFromKeyserver($this->certMetadata[$metadataKey] . strtoupper($keyIdentifier) . ".crt");
                $certificate->setCertificateContent($certificateContent);
            } catch (ComposerUpdateException $e) {
                if ($certificate->getCertificateType() == Certificate::TYPE_INTERMEDIATE) {
                    $caLevel = count($this->validationChain);
                    $message = "Error retrieving step " . $caLevel . " intermediate certificate from keyserver.";
                    if (stripos($e->getMessage(), "Not Found") !== false && self::MAX_CA_LEVELS <= $caLevel) {
                        $message .= " This may mean an attempt to validate trust chain with an incorrect local Root CA certificate.";
                    }
                } else {
                    $message = "Error retrieving code signing certificate from keyserver.";
                }
                throw new ComposerUpdateException($message . " Key identifier: " . $keyIdentifier . ", meta key: " . $metadataKey . ". Error: " . $e->getMessage());
            }
        }
        if ($certificate->isLoaded()) {
            $loadedCertificateKeyIdentifier = $certificate->getKeyIdentifier();
            if (strcasecmp($keyIdentifier, $loadedCertificateKeyIdentifier) !== 0) {
                throw new ComposerUpdateException("Obtained certificate does not match the requested identifier. " . "Requested identifier: " . $keyIdentifier . ", obtained certificate has identifier: " . $loadedCertificateKeyIdentifier);
            }
            return $certificate;
        }
        throw new ComposerUpdateException("Failed to obtain certificate. " . "Key identifier: " . $keyIdentifier);
    }
    private function validateCertificateSignatureIsTrusted(Certificate $certificate)
    {
        $keyIdentifier = $certificate->getKeyIdentifier();
        $trustedAuthorityKeyIdentifier = $certificate->getAuthorityKeyIdentifier();
        if (strlen($keyIdentifier) == 0) {
            throw new ComposerUpdateException("No certificate key identifier found");
        }
        if (strlen($trustedAuthorityKeyIdentifier) == 0) {
            throw new ComposerUpdateException("No signing authority key identifier found");
        }
        if (strcasecmp($trustedAuthorityKeyIdentifier, $keyIdentifier) === 0) {
            throw new ComposerUpdateException("Self-signed certificates are not allowed: " . $keyIdentifier);
        }
        if (isset($this->validationChain[$keyIdentifier])) {
            $this->validationChain = array();
            throw new ComposerUpdateException("Circular references are not allowed");
        }
        if (self::MAX_CA_LEVELS <= count(array_keys($this->validationChain))) {
            throw new ComposerUpdateException("Unsupported CA certificate configuration");
        }
        $this->validationChain[$keyIdentifier] = true;
        try {
            if (strcasecmp($trustedAuthorityKeyIdentifier, $this->getRootCaKeyIdentifier()) === 0) {
                $trustedAuthorityCertificate = $this->createCertificate(Certificate::TYPE_INTERMEDIATE, $this->getRootCaCertificateContent());
            } else {
                $trustedAuthorityCertificate = $this->getTrustedCertificate($trustedAuthorityKeyIdentifier, Certificate::TYPE_INTERMEDIATE);
            }
            $certificate->validateSignedBy($trustedAuthorityCertificate);
            unset($this->validationChain[$keyIdentifier]);
            return $this;
        } catch (\Exception $e) {
            $this->validationChain = array();
            throw $e;
        }
    }
    private function getTrustedCertificate($keyIdentifier, $certType)
    {
        $keyIdentifier = strtoupper($keyIdentifier);
        if (isset($this->trustedCertificates[$keyIdentifier])) {
            return $this->trustedCertificates[$keyIdentifier];
        }
        $certificate = $this->obtainCertificate($keyIdentifier, $certType);
        $this->validateCertificateSignatureIsTrusted($certificate)->validateCertificateNotRevoked($certificate)->addTrustedCertificate($certificate);
        return $certificate;
    }
    public function getValidCodeSigningCertificateContent($keyIdentifier)
    {
        return $this->getTrustedCertificate($keyIdentifier, Certificate::TYPE_CODE_SIGNING)->getCertificateContent();
    }
}

?>