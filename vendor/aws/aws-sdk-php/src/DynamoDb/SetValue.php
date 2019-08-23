<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\DynamoDb;

/**
 * Special object to represent a DynamoDB set (SS/NS/BS) value.
 */
class SetValue implements \JsonSerializable, \Countable, \IteratorAggregate
{
    /** @var array Values in the set as provided. */
    private $values;
    /**
     * @param array  $values Values in the set.
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }
    /**
     * Get the values formatted for PHP and JSON.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }
    public function count()
    {
        return count($this->values);
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

?>