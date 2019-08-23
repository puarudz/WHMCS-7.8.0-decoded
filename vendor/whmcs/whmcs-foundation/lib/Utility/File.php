<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class File
{
    public static function recursiveCopy($sourcePath, $destinationPath, array $excludeFromCopy = array(), $preservePermissions = true, $preserveTimes = true)
    {
        if (!is_dir($sourcePath)) {
            throw new \WHMCS\Exception("Invalid source copy path " . $sourcePath . ".");
        }
        if (!is_dir($destinationPath)) {
            throw new \WHMCS\Exception("Invalid destination copy path " . $destinationPath . ".");
        }
        $directory = new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            foreach ($excludeFromCopy as $excludePath) {
                if (strpos($item, $excludePath) === 0) {
                    continue 2;
                }
                if (strpos(str_replace($sourcePath . DIRECTORY_SEPARATOR, "", $item), $excludePath) === 0) {
                    continue 2;
                }
                if (strpos(str_replace(ROOTDIR . DIRECTORY_SEPARATOR, "", $item), $excludePath) === 0) {
                    continue 2;
                }
            }
            $destinationItem = $destinationPath . DIRECTORY_SEPARATOR . str_replace($sourcePath . DIRECTORY_SEPARATOR, "", $item);
            if ($item->isDir()) {
                if (!file_exists($destinationItem) && !@mkdir($destinationItem)) {
                    throw new \WHMCS\Exception("Unable to create the directory " . $destinationItem . ".");
                }
            } else {
                if (!@copy($item, $destinationItem)) {
                    throw new \WHMCS\Exception("Unable to copy " . $item . " to " . $destinationItem . ".");
                }
            }
            if ($preservePermissions && !chmod($destinationItem, $item->getPerms())) {
                throw new \WHMCS\Exception("Unable to preserve permissions for " . $destinationItem . ".");
            }
            if ($preserveTimes && !touch($destinationItem, $item->getMTime(), $item->getATime())) {
                throw new \WHMCS\Exception("Unable to preserve access and modification times for " . $destinationItem . ".");
            }
        }
    }
    public static function recursiveDelete($path, array $excludeFiles = array(), $removeRootDirectory = false)
    {
        if (!is_dir($path) || realpath($path) != $path) {
            throw new \WHMCS\Exception("Invalid path " . $path . ".");
        }
        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            foreach ($excludeFiles as $excludePath) {
                if (strpos($item, $excludePath) === 0) {
                    continue 2;
                }
                if (strpos(str_replace($path . DIRECTORY_SEPARATOR, "", $item), $excludePath) === 0) {
                    continue 2;
                }
                if (strpos(str_replace(ROOTDIR . DIRECTORY_SEPARATOR, "", $item), $excludePath) === 0) {
                    continue 2;
                }
            }
            if (!$item->isWritable()) {
                throw new \WHMCS\Exception\File\NotDeleted("Permissions prevent deletion of " . $item . ".");
            }
            if ($item->isDir()) {
                if (!@rmdir($item)) {
                    throw new \WHMCS\Exception\File\NotDeleted("Unable to delete " . $item . ".");
                }
            } else {
                if (!@unlink($item)) {
                    throw new \WHMCS\Exception\File\NotDeleted("Unable to delete " . $item . ".");
                }
            }
        }
        if (count($excludeFiles) == 0 && $removeRootDirectory) {
            if (!is_writable($path)) {
                throw new \WHMCS\Exception\File\NotDeleted("Permissions prevent deletion of " . $path . ".");
            }
            if (!@rmdir($path)) {
                throw new \WHMCS\Exception\File\NotDeleted("Unable to delete " . $path . ".");
            }
        }
    }
    public static function recursiveMkDir($location, $dirPath)
    {
        if (!is_dir($location)) {
            throw new \WHMCS\Exception("Invalid directory location");
        }
        if (!$dirPath) {
            throw new \WHMCS\Exception("Invalid directory path");
        }
        $dirs = explode(DIRECTORY_SEPARATOR, $dirPath);
        $pathToCreate = $location;
        $statInfo = stat($location);
        $dirMode = $statInfo !== false ? $statInfo["mode"] & 511 : false;
        foreach ($dirs as $dir) {
            if (!$dir) {
                continue;
            }
            $pathToCreate .= DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($pathToCreate)) {
                if (!mkdir($pathToCreate)) {
                    throw new \WHMCS\Exception("Failed to create directory: " . $pathToCreate);
                }
                if ($dirMode !== false) {
                    chmod($pathToCreate, $dirMode);
                }
            }
        }
    }
}

?>