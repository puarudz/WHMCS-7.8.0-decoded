<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class ValidatedZipDownloader extends \Composer\Downloader\ZipDownloader
{
    private $package = NULL;
    private $packageMetaCallback = NULL;
    public function download(\Composer\Package\PackageInterface $package, $path)
    {
        $this->package = $package;
        parent::download($package, $path);
    }
    public function setPackageMetadataCallback(callable $callback)
    {
        $this->packageMetaCallback = $callback;
    }
    protected function generateValidationHash($file)
    {
        $fileHash = hash_file("sha256", $file, false);
        $filename = basename(parse_url($this->package->getDistUrl(), PHP_URL_PATH));
        $version = $this->package->getPrettyVersion();
        return hash("sha256", strtoupper($fileHash) . $filename . $version, true);
    }
    protected function getPackageSignature()
    {
        $extra = $this->package->getExtra();
        return $extra["sig"];
    }
    protected function isSignatureValid($file, $certificate)
    {
        $x509 = new \phpseclib\File\X509();
        if (!$x509->loadX509($certificate)) {
            throw new ComposerUpdateException("Cannot load validation certificate");
        }
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->loadKey($x509->getPublicKey());
        $rsa->setSignatureMode(\phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
        return $rsa->verify($this->generateValidationHash($file), base64_decode($this->getPackageSignature()));
    }
    protected function validateFile($file)
    {
        $this->io->write("Validating " . $file);
        $extra = $this->package->getExtra();
        $correctHash = trim(strtolower($extra["sha"]));
        $fileHash = strtolower(hash_file("sha256", $file, false));
        if ($correctHash != "" && $correctHash === $fileHash) {
            $this->io->write("File hash was validated. Hash: " . $correctHash);
            $certificateManager = new CertificateManager(\WHMCS\Config\Setting::getValue("UpdateTempPath"));
            $certificate = $certificateManager->getValidCodeSigningCertificateContent($extra["keyIdentifier"]);
            $this->io->write("Certificate used for signature validation was loaded and validated");
            if ($this->isSignatureValid($file, $certificate)) {
                $this->io->write("File signature was validated");
            } else {
                throw new ComposerUpdateException("File signature validation failed!");
            }
        } else {
            throw new ComposerUpdateException("Incorrect hash! Received file: " . $fileHash . ", control: " . $correctHash);
        }
    }
    protected function extract($file, $path)
    {
        $this->validateFile($file);
        if (!is_null($this->packageMetaCallback)) {
            $callback = $this->packageMetaCallback;
            $callback($this->package->getName(), $this->package->getExtra());
        }
        parent::extract($file, $path);
    }
}

?>