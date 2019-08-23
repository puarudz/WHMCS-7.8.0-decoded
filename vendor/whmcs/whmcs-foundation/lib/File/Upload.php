<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File;

class Upload extends \WHMCS\File
{
    protected $uploadedFile = NULL;
    protected $uploadFilename = NULL;
    protected $uploadTmpName = NULL;
    public function __construct($nameOrFile, $key = NULL)
    {
        if ($nameOrFile instanceof \Zend\Diactoros\UploadedFile) {
            $this->uploadedFile = $nameOrFile;
        } else {
            if (!isset($_FILES[$nameOrFile])) {
                throw new \WHMCS\Exception\File\NotUploaded("Check name and key parameters.");
            }
            if (is_numeric($key)) {
                $this->uploadFilename = $_FILES[$nameOrFile]["name"][$key];
                $this->uploadTmpName = $_FILES[$nameOrFile]["tmp_name"][$key];
            } else {
                $this->uploadFilename = $_FILES[$nameOrFile]["name"];
                $this->uploadTmpName = $_FILES[$nameOrFile]["tmp_name"];
            }
            if (!$this->isUploaded()) {
                throw new \WHMCS\Exception\File\NotUploaded(\Lang::trans("filemanagement.nofileuploaded"));
            }
            if (!$this->isFileNameSafe($this->getCleanName())) {
                throw new \WHMCS\Exception(\Lang::trans("filemanagement.invalidname"));
            }
        }
    }
    public static function getUploadedFiles($name)
    {
        $attachments = array();
        $uploadedFiles = \WHMCS\Http\Message\ServerRequest::fromGlobals()->getUploadedFiles();
        if (isset($uploadedFiles[$name])) {
            if (is_array($uploadedFiles[$name])) {
                foreach ($uploadedFiles[$name] as $file) {
                    if ($file->getClientFilename() && $file->getClientMediaType()) {
                        $attachments[] = new self($file);
                    }
                }
            } else {
                if ($uploadedFiles[$name]->getClientFilename() && $uploadedFiles[$name]->getClientMediaType()) {
                    $attachments[] = new self($uploadedFiles[$name]);
                }
            }
        }
        return $attachments;
    }
    public function store(Filesystem $storage, $fileNameToSave)
    {
        $stream = $this->uploadedFile->getStream()->detach();
        $storage->writeStream($fileNameToSave, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        return $fileNameToSave;
    }
    public function storeAsClientFile()
    {
        $storage = \Storage::clientFiles();
        while (true) {
            $fileNameToSave = "file" . (new \WHMCS\Utility\Random())->number(6) . "_" . $this->getCleanName();
            if (!$storage->has($fileNameToSave)) {
                break;
            }
        }
        return $this->store($storage, $fileNameToSave);
    }
    public function storeAsDownload()
    {
        $storage = \Storage::downloads();
        $fileNameToSave = $this->getCleanName();
        return $this->store($storage, $fileNameToSave);
    }
    public function storeAsEmailAttachment()
    {
        $storage = \Storage::emailAttachments();
        while (true) {
            $fileNameToSave = "attach" . (new \WHMCS\Utility\Random())->number(6) . "_" . $this->getCleanName();
            if (!$storage->has($fileNameToSave)) {
                break;
            }
        }
        return $this->store($storage, $fileNameToSave);
    }
    public function storeAsEmailTemplateAttachment()
    {
        $storage = \Storage::emailTemplateAttachments();
        while (true) {
            $fileNameToSave = (new \WHMCS\Utility\Random())->number(6) . "_" . $this->getCleanName();
            if (!$storage->has($fileNameToSave)) {
                break;
            }
        }
        return $this->store($storage, $fileNameToSave);
    }
    public function storeAsProjectFile($projectId)
    {
        $storage = \Storage::projectManagementFiles($projectId);
        while (true) {
            $fileNameToSave = (new \WHMCS\Utility\Random())->number(6) . "_" . $this->getCleanName();
            if (!$storage->has($fileNameToSave)) {
                break;
            }
        }
        return $this->store($storage, $fileNameToSave);
    }
    public function storeAsTicketAttachment()
    {
        $storage = \Storage::ticketAttachments();
        while (true) {
            $fileNameToSave = (new \WHMCS\Utility\Random())->number(6) . "_" . $this->getCleanName();
            if (!$storage->has($fileNameToSave)) {
                break;
            }
        }
        return $this->store($storage, $fileNameToSave);
    }
    public function getFileName()
    {
        return !is_null($this->uploadedFile) ? $this->uploadedFile->getClientFilename() : $this->uploadFilename;
    }
    public function getFileTmpName()
    {
        return $this->uploadTmpName;
    }
    public function getSize()
    {
        return $this->uploadedFile->getSize();
    }
    public function getClientMediaType()
    {
        return $this->uploadedFile->getClientMediaType();
    }
    public function getCleanName()
    {
        return preg_replace("/[^a-zA-Z0-9-_. ]/", "", $this->getFileName());
    }
    public function isUploaded()
    {
        return is_uploaded_file($this->getFileTmpName());
    }
    public function move($dest_dir = "", $prefix = "")
    {
        if (!is_writeable($dest_dir)) {
            throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.checkPermissions"));
        }
        $destinationPath = $this->generateUniqueDestinationPath($dest_dir, $prefix);
        if (!move_uploaded_file($this->getFileTmpName(), $destinationPath)) {
            throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.checkAvailableDiskSpace"));
        }
        return basename($destinationPath);
    }
    protected function generateUniqueDestinationPath($dest_dir, $prefix)
    {
        mt_srand($this->makeRandomSeed());
        $i = 1;
        while ($i <= 30) {
            $rand = mt_rand(100000, 999999);
            $destinationPath = $dest_dir . DIRECTORY_SEPARATOR . str_replace("{RAND}", $rand, $prefix) . $this->getCleanName();
            $file = new \WHMCS\File($destinationPath);
            if ($file->exists()) {
                if (strpos($prefix, "{RAND}") === false) {
                    throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.fileAlreadyExists"));
                }
                $i++;
            } else {
                return $destinationPath;
            }
        }
        throw new \WHMCS\Exception(\Lang::trans("filemanagement.couldNotSaveFile") . " " . \Lang::trans("filemanagement.noUniqueName"));
    }
    protected function makeRandomSeed()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (double) $sec + (double) $usec * 100000;
    }
    public function checkExtension()
    {
        return self::isExtensionAllowed($this->getFileName());
    }
    public static function isExtensionAllowed($filename)
    {
        if ($filename[0] == ".") {
            return false;
        }
        $whmcs = \DI::make("app");
        $alwaysBannedExtensions = array("php", "cgi", "pl", "htaccess");
        $extensionArray = explode(",", strtolower($whmcs->get_config("TicketAllowedFileTypes")));
        $filenameParts = pathinfo($filename);
        $fileExtension = strtolower($filenameParts["extension"]);
        if (in_array($fileExtension, $alwaysBannedExtensions)) {
            return false;
        }
        if (in_array("." . $fileExtension, $extensionArray)) {
            return true;
        }
        return false;
    }
    public function contents()
    {
        return file_get_contents($this->getFileTmpName());
    }
    public function setFilename($name)
    {
        $this->uploadFilename = $name;
    }
}

?>