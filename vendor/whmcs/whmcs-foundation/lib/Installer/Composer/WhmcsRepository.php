<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class WhmcsRepository extends \Composer\Repository\ComposerRepository
{
    const REPOSITORY_TYPE = "whmcs-composer";
    protected function fetchFile($filename, $cacheKey = NULL, $sha256 = NULL, $storeLastModifiedTime = false)
    {
        $fallbackUrl = "";
        $cacheSuffix = "";
        $baseFilename = basename(parse_url($filename, PHP_URL_PATH));
        if ($baseFilename === "packages.json") {
            if ($filename != ComposerUpdate::getRepositoryUrl() . "packages.json") {
                $cacheSuffix = "S&U";
            }
            $fallbackUrl = ComposerUpdate::getAllVersionsRepositoryUrl();
        }
        $cache = new \WHMCS\TransientData();
        $cacheKey = "UpdatePackagesDataFile" . $cacheSuffix;
        $storage = \DI::make("runtimeStorage");
        if (!is_null($storage["updaterUseCachedPackagesFile"]) && $storage["updaterUseCachedPackagesFile"] === true) {
            $cachedData = $cache->retrieve($cacheKey);
            if (!empty($cachedData)) {
                $decodedCachedData = json_decode($cachedData, true);
                if (is_array($decodedCachedData)) {
                    return $decodedCachedData;
                }
            }
        }
        try {
            $data = parent::fetchFile($filename, $cacheKey, $sha256, $storeLastModifiedTime);
            $data = (new PackagesFile())->validateNotificationSignatures($data);
            $cache->store($cacheKey, json_encode($data), 48 * 60 * 60);
            return $data;
        } catch (\Composer\Downloader\TransportException $e) {
            if ($this->isClientError($e->getStatusCode()) && !empty($fallbackUrl)) {
                $this->url = $fallbackUrl;
                $this->baseUrl = rtrim($fallbackUrl, "/");
                $data = parent::fetchFile($fallbackUrl . "packages.json", $cacheKey);
                $cache->store($cacheKey, json_encode($data), 48 * 60 * 60);
                return $data;
            }
            throw $e;
        }
    }
    private function isClientError($errorCode)
    {
        $intErrorCode = (int) $errorCode;
        return 400 <= $intErrorCode && $intErrorCode <= 499;
    }
}

?>