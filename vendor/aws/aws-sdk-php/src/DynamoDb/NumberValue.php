<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\DynamoDb;

/**
 * Special object to represent a DynamoDB Number (N) value.
 */
class NumberValue implements \JsonSerializable
{
    /** @var string Number value. */
    private $value;
    /**
     * @param string|int|float $value A number value.
     */
    public function __construct($value)
    {
        $this->value = (string) $value;
    }
    public function jsonSerialize()
    {
        return $this->value;
    }
    public function __toString()
    {
        return $this->value;
    }
}

?>