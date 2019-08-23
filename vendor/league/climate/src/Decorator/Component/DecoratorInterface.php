<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Decorator\Component;

interface DecoratorInterface
{
    public function add($key, $value);
    /**
     * @return void
     */
    public function defaults();
    public function get($val);
    public function set($val);
    public function all();
    public function current();
    /**
     * @return void
     */
    public function reset();
}

?>