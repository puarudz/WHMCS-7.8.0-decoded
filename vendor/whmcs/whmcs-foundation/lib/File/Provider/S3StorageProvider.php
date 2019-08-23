<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Provider;

class S3StorageProvider implements StorageProviderInterface
{
    private $config = array();
    public static function getShortName()
    {
        return "s3";
    }
    public static function getName()
    {
        return "S3";
    }
    public function isLocal()
    {
        return false;
    }
    public function applyConfiguration(array $configSettings)
    {
        $keys = array("key" => true, "secret" => true, "bucket" => true, "region" => true, "endpoint_url" => false);
        $relevantSettings = array();
        $errorFields = array();
        $friendlyNames = array();
        foreach ($this->getConfigurationFields() as $field) {
            $friendlyNames[$field["Name"]] = $field["FriendlyName"];
        }
        foreach ($keys as $key => $required) {
            if (isset($configSettings[$key])) {
                $settingValue = trim($configSettings[$key]);
                if ($key === "endpoint_url") {
                    if ($settingValue !== "" && !parse_url($settingValue, PHP_URL_SCHEME)) {
                        $settingValue = "https://" . $settingValue;
                    }
                } else {
                    if ($key === "region" && !empty($configSettings["endpoint_url"])) {
                        $required = false;
                    }
                }
            } else {
                $settingValue = "";
            }
            if ($settingValue != "" || !$required) {
                $relevantSettings[$key] = $settingValue;
            } else {
                $errorFields[] = \AdminLang::trans("validation.required", array(":attribute" => $friendlyNames[$key]));
            }
        }
        if (!empty($errorFields)) {
            throw new \WHMCS\Exception\Storage\StorageConfigurationException($errorFields);
        }
        $this->config = $relevantSettings;
    }
    public function exportConfiguration(\WHMCS\File\Configuration\StorageConfiguration $config = NULL)
    {
        if (!$config) {
            $config = \WHMCS\File\Configuration\StorageConfiguration::newRemote();
        }
        $config->name = $this->getName() . ": " . $this->getConfigSummaryText();
        $config->handler = static::class;
        $config->settings = $this->config;
        return $config;
    }
    public function getConfigurationFields()
    {
        return array(array("Name" => "key", "FriendlyName" => \AdminLang::trans("storage.s3.key"), "Type" => "text"), array("Name" => "secret", "FriendlyName" => \AdminLang::trans("storage.s3.secret"), "Type" => "text"), array("Name" => "bucket", "FriendlyName" => \AdminLang::trans("storage.s3.bucket"), "Type" => "text"), array("Name" => "region", "FriendlyName" => \AdminLang::trans("storage.s3.region"), "Type" => "text"), array("Name" => "endpoint_url", "FriendlyName" => \AdminLang::trans("storage.s3.endpointUrl"), "Type" => "text", "Placeholder" => \AdminLang::trans("global.optional"), "Description" => \AdminLang::trans("storage.s3.endpointUrlDescription")));
    }
    public function getAccessCredentialFieldNames()
    {
        return array("key", "secret");
    }
    public function createS3Client()
    {
        $settings = array("credentials" => array("key" => $this->config["key"], "secret" => $this->config["secret"]), "region" => $this->config["region"], "version" => "latest");
        if (!empty($this->config["endpoint_url"])) {
            $settings["endpoint"] = $this->config["endpoint_url"];
        }
        return new \Aws\S3\S3Client($settings);
    }
    public function getBucket()
    {
        return $this->config["bucket"];
    }
    public function getPathPrefix($assetType)
    {
        $assetPrefixes = array(\WHMCS\File\FileAsset::TYPE_CLIENT_FILES => "client_files", \WHMCS\File\FileAsset::TYPE_DOWNLOADS => "downloads", \WHMCS\File\FileAsset::TYPE_EMAIL_ATTACHMENTS => "email_attachments", \WHMCS\File\FileAsset::TYPE_EMAIL_TEMPLATE_ATTACHMENTS => "template_attachments", \WHMCS\File\FileAsset::TYPE_PM_FILES => "projects", \WHMCS\File\FileAsset::TYPE_TICKET_ATTACHMENTS => "ticket_attachments");
        if (!array_key_exists($assetType, $assetPrefixes)) {
            throw new \WHMCS\Exception\Storage\StorageException("Invalid asset type");
        }
        return $assetPrefixes[$assetType];
    }
    public function createFilesystemAdapterForAssetType($assetType, $subPath = "")
    {
        $prefix = $this->getPathPrefix($assetType);
        if ($subPath) {
            $prefix .= DIRECTORY_SEPARATOR . $subPath;
        }
        $client = $this->createS3Client();
        return new \League\Flysystem\AwsS3v3\AwsS3Adapter($client, $this->getBucket(), $prefix);
    }
    public function getConfigSummaryText()
    {
        return $this->config["bucket"] . " @ " . $this->config["region"];
    }
    public function getConfigSummaryHtml()
    {
        return $this->config["bucket"] . "<br />@" . $this->config["region"];
    }
    public function getIcon()
    {
        return "fab fa-aws";
    }
    public function testConfiguration()
    {
        $filesystem = new \WHMCS\File\Filesystem($this->createFilesystemAdapterForAssetType(\WHMCS\File\FileAsset::TYPE_CLIENT_FILES));
        $randomFilename = \Illuminate\Support\Str::random(32);
        $randomString = \Illuminate\Support\Str::random(32);
        try {
            $fileCreated = $filesystem->write($randomFilename, $randomString);
            if ($randomString !== $filesystem->read($randomFilename)) {
                throw new \WHMCS\Exception\Storage\StorageException("Failed to read test file contents");
            }
        } catch (\Aws\Exception\AwsException $e) {
            $message = $e->getAwsErrorMessage() ?: $e->getMessage();
            if (stripos($message, "cURL error 6") !== false) {
                $customErrorMessage = "Please verify the bucket name";
                if ($this->config["endpoint_url"]) {
                    $customErrorMessage .= " and endpoint URL";
                }
                $message = $customErrorMessage . ". " . $message;
            }
            throw new \WHMCS\Exception\Storage\StorageException("\"" . trim($message, ".") . ".\"");
        } finally {
            if ($fileCreated) {
                $filesystem->delete($randomFilename);
            }
        }
    }
    public function getFieldsLockedInUse()
    {
        return array("bucket", "region");
    }
    public static function getExceptionErrorMessage(\Exception $e)
    {
        if ($e instanceof \Aws\Exception\AwsException) {
            $targetFilePath = parse_url($e->getRequest()->getUri(), PHP_URL_PATH);
            $errorDescription = $e->getAwsErrorMessage() ?: $e->getMessage();
            if ($e->getResponse()->getStatusCode() === \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN) {
                $errorDescription .= ". Please verify your AWS storage settings.";
            }
            return "Could not access file: " . $targetFilePath . ". " . $errorDescription;
        }
        return $e->getMessage();
    }
}

?>