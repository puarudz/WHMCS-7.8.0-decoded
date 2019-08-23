<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws;

/**
 * Interface that allows implementing various incremental hashes.
 */
interface HashInterface
{
    /**
     * Adds data to the hash.
     *
     * @param string $data Data to add to the hash
     */
    public function update($data);
    /**
     * Finalizes the incremental hash and returns the resulting digest.
     *
     * @return string
     */
    public function complete();
    /**
     * Removes all data from the hash, effectively starting a new hash.
     */
    public function reset();
}

?>