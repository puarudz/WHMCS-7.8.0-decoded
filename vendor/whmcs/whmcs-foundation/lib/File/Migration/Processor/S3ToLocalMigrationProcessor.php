<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration\Processor;

class S3ToLocalMigrationProcessor extends AbstractMigrationProcessor
{
    use LocalCapableProcessorTrait;
    private $localPath = NULL;
    private $s3Client = NULL;
    private $s3Bucket = NULL;
    private $s3PathPrefix = NULL;
    const DOWNLOAD_CONCURRENCY = 5;
    const TEMP_LOCAL_FILE_EXT = ".migration";
    public function setFromProvider(\WHMCS\File\Provider\StorageProviderInterface $fromProvider)
    {
        if (!$fromProvider instanceof \WHMCS\File\Provider\S3StorageProvider) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Invalid destination storage provider");
        }
        $this->s3Client = $fromProvider->createS3Client();
        $this->s3Bucket = $fromProvider->getBucket();
        $this->s3PathPrefix = $fromProvider->getPathPrefix($this->assetType);
        return $this;
    }
    public function setToProvider(\WHMCS\File\Provider\StorageProviderInterface $toProvider)
    {
        if (!$toProvider instanceof \WHMCS\File\Provider\LocalStorageProvider) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Invalid source storage provider");
        }
        $this->localPath = $toProvider->getLocalPath();
        return $this;
    }
    protected function doMigrate()
    {
        $numTotalObjects = count($this->objectsToMigrate);
        $objectIndex = 0;
        $totalDataSizeThisRun = 0;
        $cutoffTime = time() + $this->getTimeLimit();
        $failedObjects = array();
        $failureReasons = array();
        $downloadClient = new \GuzzleHttp\Client(array("allow_redirects" => true));
        $this->validateLocalPath($this->localPath);
        while ($objectIndex < $numTotalObjects) {
            $requests = array();
            $objectsMigratedInBatch = array();
            while ($objectIndex < $numTotalObjects && count($requests) < static::DOWNLOAD_CONCURRENCY) {
                $objectPath = $this->objectsToMigrate[$objectIndex++];
                if ($this->isObjectMigrated($objectPath)) {
                    continue;
                }
                $remoteObjectKey = $this->s3PathPrefix . "/" . $objectPath;
                $fullLocalFilePath = $this->localPath . DIRECTORY_SEPARATOR . $objectPath;
                $this->createDirectoriesForFile($fullLocalFilePath);
                $objectsMigratedInBatch[sha1($fullLocalFilePath)] = $objectPath;
                $command = $this->s3Client->getCommand("GetObject", array("Bucket" => $this->s3Bucket, "Key" => $remoteObjectKey));
                $presignedUrlExpiration = time() + 300;
                $presignedUrl = $this->s3Client->createPresignedRequest($command, $presignedUrlExpiration)->getUri()->__toString();
                $tempDownloadFilePath = $fullLocalFilePath . static::TEMP_LOCAL_FILE_EXT;
                file_put_contents($tempDownloadFilePath, "");
                $requests[] = $downloadClient->createRequest("GET", $presignedUrl, array("save_to" => $tempDownloadFilePath));
            }
            $pool = new \GuzzleHttp\Pool($downloadClient, $requests, array("pool_size" => static::DOWNLOAD_CONCURRENCY, "complete" => function (\GuzzleHttp\Event\CompleteEvent $event) use($objectsMigratedInBatch, &$totalDataSizeThisRun, &$failedObjects, &$failureReasons) {
                $tempFilePath = $event->getRequest()->getConfig()->get("save_to");
                $actualTargetFilePath = substr($tempFilePath, 0, strlen($tempFilePath) - strlen(static::TEMP_LOCAL_FILE_EXT));
                $responseCode = (int) $event->getResponse()->getStatusCode();
                if ($responseCode === 200) {
                    if (rename($tempFilePath, $actualTargetFilePath)) {
                        $totalDataSizeThisRun += filesize($actualTargetFilePath);
                        $pathHash = sha1($actualTargetFilePath);
                        if (array_key_exists($pathHash, $objectsMigratedInBatch)) {
                            $objectPath = $objectsMigratedInBatch[$pathHash];
                            $this->addMigratedObject($objectPath);
                        } else {
                            $failedObjects[] = $actualTargetFilePath;
                            $failureReasons[] = "The following file migration could not be positively asserted: " . $actualTargetFilePath;
                        }
                    } else {
                        $failedObjects[] = $actualTargetFilePath;
                        $failureReasons[] = "The following file could not be moved during migration: " . $tempFilePath . " to " . $actualTargetFilePath;
                    }
                } else {
                    $failedObjects[] = $actualTargetFilePath;
                    $failureReason = $event->getResponse()->getReasonPhrase();
                    if (300 <= $responseCode && $responseCode < 400) {
                        $failureReason .= ". Check that your S3 bucket name is correct";
                    }
                    $failureReasons[] = $failureReason;
                }
            }, "error" => function (\GuzzleHttp\Event\ErrorEvent $event) use(&$failedObjects, &$failureReasons) {
                $erroneousFilePath = $event->getRequest()->getConfig()->get("save_to");
                if (file_exists($erroneousFilePath)) {
                    unlink($erroneousFilePath);
                }
                $failedObjects[] = substr($erroneousFilePath, 0, strlen($erroneousFilePath) - strlen(static::TEMP_LOCAL_FILE_EXT));
                $responseCode = (int) $event->getException()->getCode();
                $failureReason = "Response code: " . $responseCode;
                if ($responseCode === 403) {
                    $failureReason .= ". Check your S3 key and secret and make sure your server time is correct.";
                }
                $failureReasons[] = $failureReason;
            }));
            $pool->wait();
            if ($this->getDataSizeLimit() <= $totalDataSizeThisRun) {
                break;
            }
            if ($cutoffTime < time()) {
                break;
            }
        }
        if ($failedObjects) {
            $uniqueFailureReasons = implode(", ", array_unique($failureReasons));
            throw new \WHMCS\Exception\Storage\AssetMigrationException(sprintf("Failed to migrate %d objects. %s", count($failedObjects), $uniqueFailureReasons));
        }
    }
}

?>