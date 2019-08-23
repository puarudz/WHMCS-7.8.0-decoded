<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem;

use Exception as BaseException;
class FileExistsException extends Exception
{
    /**
     * @var string
     */
    protected $path;
    /**
     * Constructor.
     *
     * @param string        $path
     * @param int           $code
     * @param BaseException $previous
     */
    public function __construct($path, $code = 0, BaseException $previous = null)
    {
        $this->path = $path;
        parent::__construct('File already exists at path: ' . $this->getPath(), $code, $previous);
    }
    /**
     * Get the path which was found.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}

?>