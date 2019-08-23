<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Config;

class ApplicationWriter
{
    private $filePath = NULL;
    public function __construct($filePath = NULL)
    {
        if (is_null($filePath)) {
            $filePath = ROOTDIR . DIRECTORY_SEPARATOR . Application::WHMCS_DEFAULT_CONFIG_FILE;
        }
        $errorMessage = "";
        if (!is_writable($filePath)) {
            $errorMessage = "Application configuration file is not writable";
        } else {
            if (is_link($filePath) || !is_file($filePath)) {
                $errorMessage = "Application configuration file is not a file";
            }
        }
        if ($errorMessage) {
            throw new \JMS\Serializer\Exception\RuntimeException($errorMessage);
        }
        $this->setFilePath($filePath);
    }
    public function getFilePath()
    {
        return $this->filePath;
    }
    protected function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }
    public function setValue($keyName, $value)
    {
        $filePath = $this->getFilePath();
        $content = $this->readFileContents($filePath);
        $content = $this->replaceContentValue($content, $keyName, $value);
        $this->writeFileContents($filePath, $content);
        return $this;
    }
    protected function readFileContents($filePath)
    {
        return file($filePath);
    }
    protected function writeFileContents($filePath, array $contents)
    {
        file_put_contents($filePath, implode("", $contents));
        return $this;
    }
    protected function replaceContentValue(array $content, $keyName, $value)
    {
        $foundLine = false;
        $openTag = null;
        $newLine = sprintf("\$%s = '%s';\n", $keyName, $value);
        foreach ($content as $ln => $line) {
            if (strpos($line, "\$" . $keyName) === 0) {
                $foundLine = true;
                $content[$ln] = $newLine;
                break;
            }
            if (is_null($openTag) && strpos($line, "<?php") === 0) {
                $openTag = $ln;
            }
        }
        if (!$foundLine) {
            if (is_null($openTag)) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not parse configuration file for update: no opening tag");
            }
            array_splice($content, $openTag + 1, 0, $newLine);
        }
        return $content;
    }
}

?>