<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

/**
 * Trait that implements the methods of HasEmitterInterface
 */
trait HasEmitterTrait
{
    /** @var EmitterInterface */
    private $emitter;
    public function getEmitter()
    {
        if (!$this->emitter) {
            $this->emitter = new Emitter();
        }
        return $this->emitter;
    }
}

?>