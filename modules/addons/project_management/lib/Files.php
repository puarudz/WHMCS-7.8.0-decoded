<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Files extends BaseProjectEntity
{
    public function get($messageId = 0)
    {
        $storage = \Storage::projectManagementFiles($this->project->id);
        $this->checkAndCreateDirectoryIndexes();
        $attachments = array();
        $fileList = Models\ProjectFile::whereProjectId($this->project->id);
        if ($messageId) {
            $fileList->where("message_id", $messageId);
        }
        foreach ($fileList->get() as $file) {
            $attachment = $file->filename;
            $displayFilename = substr($attachment, 7);
            $reversedParts = explode(".", strrev($displayFilename), 2);
            $filename = strrev($reversedParts[1]);
            $extension = "." . strrev($reversedParts[0]);
            $fileSize = 0;
            $isImage = false;
            $isBrowserViewable = false;
            if ($storage->has($attachment)) {
                $fileSize = $storage->getSize($attachment);
                if (class_exists("\\finfo")) {
                    $mimeType = $storage->getMimetype($attachment);
                    $isImage = $this->isAnImage($mimeType);
                    $isBrowserViewable = $this->isBrowserViewable($mimeType);
                } else {
                    $isImage = (bool) getimagesizefromstring($storage->read($attachment));
                    $isBrowserViewable = false;
                }
            }
            $attachments[$file->id] = array("fullFilename" => $attachment, "messageId" => $file->messageId, "displayFilename" => $displayFilename, "filename" => $filename, "extension" => $extension, "filesize" => $this->formatFileSize($fileSize), "isImage" => $isImage, "browserViewable" => $isBrowserViewable, "admin" => getAdminName($file->adminId), "when" => str_replace("-", "", Helper::daysUntilDate($file->createdAt)));
        }
        return $attachments;
    }
    public function upload()
    {
        foreach (\WHMCS\File\Upload::getUploadedFiles("file") as $uploadedFile) {
            $file = new Models\ProjectFile();
            $file->projectId = $this->project->id;
            $file->filename = $uploadedFile->storeAsProjectFile($this->project->id);
            $file->adminId = \WHMCS\Session::get("adminid");
            $file->messageId = 0;
            $file->save();
            $this->project->log()->add("File Uploaded: " . $this->formatFilenameForDisplay($uploadedFile->getCleanName()));
            $reversedParts = explode(".", strrev($uploadedFile->getCleanName()), 2);
            $fileExtension = "." . strrev($reversedParts[0]);
            $fileCount = Models\ProjectFile::where("project_id", $this->project->id)->count();
            return array("key" => $file->id, "fileCount" => $fileCount, "admin" => getAdminName(), "extension" => $fileExtension, "filename" => str_replace($fileExtension, "", $uploadedFile->getCleanName()), "filesize" => $this->formatFileSize($uploadedFile->getSize()), "isImage" => $this->isAnImage($uploadedFile->getClientMediaType()), "browserViewable" => $this->isBrowserViewable($uploadedFile->getClientMediaType()));
        }
    }
    public function delete(Models\ProjectFile $specificFile = NULL)
    {
        $num = (int) \App::getFromRequest("num");
        if ($num || $specificFile) {
            $fileToDelete = $specificFile ?: Models\ProjectFile::findOrFail($num);
            try {
                \Storage::projectManagementFiles($this->project->id)->deleteAllowNotPresent($fileToDelete->filename);
            } catch (\Exception $e) {
                throw new Exception("Unable to Delete File: " . $e->getMessage());
            }
            $fileToDelete->delete();
            $this->project->log()->add("File Deleted: " . $this->formatFilenameForDisplay($fileToDelete->filename));
        }
        return array("deletedFileNumber" => $num, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count());
    }
    protected function formatFileSize($val, $digits = 3)
    {
        $factor = 1024;
        $symbols = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
        for ($i = 0; $i < count($symbols) - 1 && $factor <= $val; $i++) {
            $val /= $factor;
        }
        $p = strpos($val, ".");
        if ($p !== false && $digits < $p) {
            $val = round($val);
        } else {
            if ($p !== false) {
                $val = round($val, $digits - $p);
            }
        }
        return round($val, $digits) . " " . $symbols[$i] . "B";
    }
    protected function isAnImage($mimeType)
    {
        if (empty($mimeType)) {
            return false;
        }
        return substr($mimeType, 0, 6) == "image/";
    }
    protected function isBrowserViewable($mimeType)
    {
        $browserViewable = array("application/javascript", "application/pdf", "text/css", "text/plain");
        return in_array($mimeType, $browserViewable);
    }
    public function formatFilenameForDisplay($filename)
    {
        return substr($filename, 7);
    }
    protected function checkAndCreateDirectoryIndexes()
    {
        $indexSrc = "<?php" . PHP_EOL . "header(\"Location: ../../index.php\");";
        $storage = \Storage::projectManagementFiles($this->project->id);
        if ($storage->isLocalAdapter() && !$storage->has("index.php")) {
            $storage->write("index.php", $indexSrc);
        }
        $storage = \Storage::projectManagementFiles();
        if ($storage->isLocalAdapter() && !$storage->has("index.php")) {
            $storage->write("index.php", $indexSrc);
        }
        return $this;
    }
}

?>