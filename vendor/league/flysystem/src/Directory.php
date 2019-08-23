<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem;

/**
 * @deprecated
 */
class Directory extends Handler
{
    /**
     * Delete the directory.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->filesystem->deleteDir($this->path);
    }
    /**
     * List the directory contents.
     *
     * @param bool $recursive
     *
     * @return array|bool directory contents or false
     */
    public function getContents($recursive = false)
    {
        return $this->filesystem->listContents($this->path, $recursive);
    }
}

?>