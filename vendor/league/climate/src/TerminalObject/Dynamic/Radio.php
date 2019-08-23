<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Dynamic;

class Radio extends Checkboxes
{
    /**
     * Build out the checkboxes
     *
     * @param array $options
     *
     * @return Checkbox\RadioGroup
     */
    protected function buildCheckboxes(array $options)
    {
        return new Checkbox\RadioGroup($options);
    }
}

?>