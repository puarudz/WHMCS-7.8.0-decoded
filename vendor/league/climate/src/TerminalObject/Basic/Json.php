<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

class Json extends BasicTerminalObject
{
    /**
     * The data to convert to JSON
     *
     * @var mixed $data
     */
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    /**
     * Return the data as JSON
     *
     * @return string
     */
    public function result()
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }
}

?>