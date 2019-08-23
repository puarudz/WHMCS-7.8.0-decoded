<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Contracts\Validation;

use RuntimeException;
use Illuminate\Contracts\Support\MessageProvider;
class ValidationException extends RuntimeException
{
    /**
     * The message provider implementation.
     *
     * @var \Illuminate\Contracts\Support\MessageProvider
     */
    protected $provider;
    /**
     * Create a new validation exception instance.
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider  $provider
     * @return void
     */
    public function __construct(MessageProvider $provider)
    {
        $this->provider = $provider;
    }
    /**
     * Get the validation error message provider.
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function errors()
    {
        return $this->provider->getMessageBag();
    }
    /**
     * Get the validation error message provider.
     *
     * @return \Illuminate\Contracts\Support\MessageProvider
     */
    public function getMessageProvider()
    {
        return $this->provider;
    }
}

?>