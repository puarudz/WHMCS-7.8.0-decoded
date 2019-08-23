<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Contracts\Queue;

use InvalidArgumentException;
class EntityNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return void
     */
    public function __construct($type, $id)
    {
        $id = (string) $id;
        parent::__construct("Queueable entity [{$type}] not found for ID [{$id}].");
    }
}

?>