<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class WhmcsRemoteFilesystem extends \Composer\Util\RemoteFilesystem
{
    private $io = NULL;
    public function __construct(\Composer\IO\IOInterface $io, \Composer\Config $config = NULL, array $options = array(), $disableTls = false)
    {
        parent::__construct($io, $config, $options, $disableTls);
        $this->io = $io;
    }
    protected function get($originUrl, $fileUrl, $additionalOptions = array(), $fileName = NULL, $progress = true)
    {
        $fileHost = parse_url($fileUrl, PHP_URL_HOST);
        $ourRepositoryHost = parse_url(ComposerUpdate::getRepositoryUrl(), PHP_URL_HOST);
        if ($fileHost !== $ourRepositoryHost) {
            return parent::get($originUrl, $fileUrl, $additionalOptions, $fileName, $progress);
        }
        if ($progress) {
            $this->io->writeError("    Downloading: <comment>Connecting...</comment>", false);
        }
        try {
            file_put_contents($fileName, "");
            $handler = new \GuzzleHttp\Ring\Client\StreamHandler();
            $guzzle = new \GuzzleHttp\Client(array("verify" => true, "handler" => $handler));
            $guzzle->get($fileUrl, array("save_to" => $fileName));
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException) {
                $transportException = new \Composer\Downloader\TransportException("Could not download file from " . $fileUrl . " to " . $fileName . ": " . $e->getMessage());
                $response = $e->getResponse();
                if ($response) {
                    $transportException->setHeaders($response->getHeaders());
                    $transportException->setStatusCode($response->getStatusCode());
                }
                $e = $transportException;
            }
            throw $e;
        }
        if ($progress) {
            $this->io->overwriteError("    Downloading: <comment>100%</comment>");
        }
        return true;
    }
}

?>