<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Adapter\Polyfill;

use League\Flysystem\Config;
trait StreamedCopyTrait
{
    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $response = $this->readStream($path);
        if ($response === false || !is_resource($response['stream'])) {
            return false;
        }
        $result = $this->writeStream($newpath, $response['stream'], new Config());
        if ($result !== false && is_resource($response['stream'])) {
            fclose($response['stream']);
        }
        return $result !== false;
    }
    // Required abstract method
    /**
     * @param  string   $path
     * @return resource
     */
    public abstract function readStream($path);
    /**
     * @param  string   $path
     * @param  resource $resource
     * @param  Config   $config
     * @return resource
     */
    public abstract function writeStream($path, $resource, Config $config);
}

?>