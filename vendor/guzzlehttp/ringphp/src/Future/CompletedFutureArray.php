<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Ring\Future;

/**
 * Represents a future array that has been completed successfully.
 */
class CompletedFutureArray extends CompletedFutureValue implements FutureArrayInterface
{
    public function __construct(array $result)
    {
        parent::__construct($result);
    }
    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->result[$offset];
    }
    public function offsetSet($offset, $value)
    {
        $this->result[$offset] = $value;
    }
    public function offsetUnset($offset)
    {
        unset($this->result[$offset]);
    }
    public function count()
    {
        return count($this->result);
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }
}

?>