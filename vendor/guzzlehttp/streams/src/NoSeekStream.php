<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Stream;

/**
 * Stream decorator that prevents a stream from being seeked
 */
class NoSeekStream implements StreamInterface
{
    use StreamDecoratorTrait;
    public function seek($offset, $whence = SEEK_SET)
    {
        return false;
    }
    public function isSeekable()
    {
        return false;
    }
    public function attach($stream)
    {
        $this->stream->attach($stream);
    }
}

?>