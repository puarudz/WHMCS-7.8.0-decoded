<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Bar;

class FooClass
{
    public $foo;
    public $moo;
    public $bar = null;
    public $initialized = false;
    public $configured = false;
    public $called = false;
    public $arguments = array();
    public function __construct($arguments = array())
    {
        $this->arguments = $arguments;
    }
    public static function getInstance($arguments = array())
    {
        $obj = new self($arguments);
        $obj->called = true;
        return $obj;
    }
    public function initialize()
    {
        $this->initialized = true;
    }
    public function configure()
    {
        $this->configured = true;
    }
    public function setBar($value = null)
    {
        $this->bar = $value;
    }
}

?>