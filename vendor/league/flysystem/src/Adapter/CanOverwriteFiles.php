<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Adapter;

/**
 * Adapters that implement this interface let the Filesystem know that it files can be overwritten using the write
 * functions and don't need the update function to be called. This can help improve performance when asserts are disabled.
 */
interface CanOverwriteFiles
{
}

?>