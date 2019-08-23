<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Plugin;

class ListPaths extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'listPaths';
    }
    /**
     * List all paths.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array paths
     */
    public function handle($directory = '', $recursive = false)
    {
        $result = [];
        $contents = $this->filesystem->listContents($directory, $recursive);
        foreach ($contents as $object) {
            $result[] = $object['path'];
        }
        return $result;
    }
}

?>