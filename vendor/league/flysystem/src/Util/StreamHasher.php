<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\Flysystem\Util;

class StreamHasher
{
    /**
     * @var string
     */
    private $algo;
    /**
     * StreamHasher constructor.
     *
     * @param string $algo
     */
    public function __construct($algo)
    {
        $this->algo = $algo;
    }
    /**
     * @param resource $resource
     *
     * @return string
     */
    public function hash($resource)
    {
        rewind($resource);
        $context = hash_init($this->algo);
        hash_update_stream($context, $resource);
        fclose($resource);
        return hash_final($context);
    }
}

?>