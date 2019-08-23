<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Adapter\Polyfill;

use LogicException;
trait NotSupportingVisibilityTrait
{
    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @throws LogicException
     */
    public function getVisibility($path)
    {
        throw new LogicException(get_class($this) . ' does not support visibility. Path: ' . $path);
    }
    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @throws LogicException
     */
    public function setVisibility($path, $visibility)
    {
        throw new LogicException(get_class($this) . ' does not support visibility. Path: ' . $path . ', visibility: ' . $visibility);
    }
}

?>