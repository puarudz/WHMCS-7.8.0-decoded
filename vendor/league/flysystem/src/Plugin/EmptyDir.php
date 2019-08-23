<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Plugin;

class EmptyDir extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'emptyDir';
    }
    /**
     * Empty a directory's contents.
     *
     * @param string $dirname
     */
    public function handle($dirname)
    {
        $listing = $this->filesystem->listContents($dirname, false);
        foreach ($listing as $item) {
            if ($item['type'] === 'dir') {
                $this->filesystem->deleteDir($item['path']);
            } else {
                $this->filesystem->delete($item['path']);
            }
        }
    }
}

?>