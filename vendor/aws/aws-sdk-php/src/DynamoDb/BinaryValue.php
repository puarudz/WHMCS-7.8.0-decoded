<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\DynamoDb;

use GuzzleHttp\Psr7;
/**
 * Special object to represent a DynamoDB binary (B) value.
 */
class BinaryValue implements \JsonSerializable
{
    /** @var string Binary value. */
    private $value;
    /**
     * @param mixed $value A binary value compatible with Guzzle streams.
     *
     * @see GuzzleHttp\Stream\Stream::factory
     */
    public function __construct($value)
    {
        if (!is_string($value)) {
            $value = Psr7\stream_for($value);
        }
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