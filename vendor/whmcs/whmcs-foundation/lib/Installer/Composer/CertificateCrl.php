<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

final class CertificateCrl
{
    private $x509 = NULL;
    private $crlContent = "";
    private $authorityKeyIdentifier = "";
    const CRL_START = "-----BEGIN X509 CRL-----";
    const CRL_END = "-----END X509 CRL-----";
    public function __construct($crlContent)
    {
        $this->clear()->setCrlContent($crlContent);
    }
    public static function popFromPackage(&$crlPackage)
    {
        $startPos = strpos($crlPackage, self::CRL_START);
        $endPos = strpos($crlPackage, self::CRL_END);
        if (false === $startPos || false === $endPos) {
            return null;
        }
        $crlContent = substr($crlPackage, $startPos, $endPos + strlen(self::CRL_END));
        $crlPackage = substr($crlPackage, $endPos + strlen(self::CRL_END) + 1);
        return new static($crlContent);
    }
    public function clear()
    {
        $this->x509 = null;
        $this->authorityKeyIdentifier = "";
        $this->crlContent = "";
        return $this;
    }
    public function getAuthorityKeyIdentifier()
    {
        return $this->authorityKeyIdentifier;
    }
    public function setCrlContent($crlContent)
    {
        $this->x509 = new \phpseclib\File\X509();
        if (!$this->x509->loadCRL($crlContent)) {
            throw new ComposerUpdateException("Failed to load CRL");
        }
        $keyIdentEncoded = $this->x509->getExtension("id-ce-authorityKeyIdentifier");
        if (is_array($keyIdentEncoded) && isset($keyIdentEncoded["keyIdentifier"])) {
            $this->authorityKeyIdentifier = Certificate::convertKeyIdentifier($keyIdentEncoded["keyIdentifier"]);
            $this->crlContent = $crlContent;
        } else {
            $this->clear();
            throw new ComposerUpdateException("Failed to decode key information");
        }
    }
    public function isLoaded()
    {
        return $this->x509 instanceof \phpseclib\File\X509 ? true : false;
    }
    private function checkLoaded()
    {
        if (!$this->isLoaded()) {
            throw new ComposerUpdateException("Certificate not loaded");
        }
    }
    public function checkCertificateNotRevoked(Certificate $certificate)
    {
        $this->checkLoaded();
        $certificate->checkLoaded();
        $crlAuthorityIdentifier = $this->getAuthorityKeyIdentifier();
        $certAuthorityIdentifier = $certificate->getAuthorityKeyIdentifier();
        if (strcasecmp($crlAuthorityIdentifier, $certAuthorityIdentifier) !== 0) {
            throw new ComposerUpdateException("The CRL was signed by a different authority than the certificate being tried for revocation. " . "CRL authority: " . $crlAuthorityIdentifier . ", certificate authority: " . $certAuthorityIdentifier);
        }
        if (!$this->x509->getRevoked($certificate->getSerialNumber())) {
            return $this;
        }
        $certificate->deleteFromCache();
        $certType = $certificate->getCertificateType() == Certificate::TYPE_INTERMEDIATE ? "Intermediate" : "Code signing";
        throw new ComposerUpdateException($certType . " certificate with keyIdentifier of " . $certificate->getKeyIdentifier() . " has been revoked");
    }
    public function validateSignedBy(Certificate $caCertificate)
    {
        $this->checkLoaded();
        $caCertificate->checkLoaded();
        if (!$caCertificate->getCanSignCrls()) {
            throw new ComposerUpdateException("Specified CA certificate cannot sign CRLs");
        }
        $crl = new \phpseclib\File\X509();
        if (!$crl->loadCA($caCertificate->getCertificateContent())) {
            throw new ComposerUpdateException("Failed to load CA certificate while trying to validate CRL");
        }
        if (!$crl->loadCRL($this->crlContent)) {
            throw new ComposerUpdateException("Failed to load CRL - it may be damaged");
        }
        if ($crl->validateSignature()) {
            return $this;
        }
        throw new ComposerUpdateException("Failed to validate CRL signature");
    }
}

?>