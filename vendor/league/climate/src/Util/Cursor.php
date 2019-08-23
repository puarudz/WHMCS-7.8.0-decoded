<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util;

class Cursor
{
    /**
     * Move the cursor up in the terminal x number of lines.
     *
     * @param int $lines
     *
     * @return string
     */
    public function up($lines = 1)
    {
        return "\33[{$lines}A";
    }
    /**
     * Move the cursor left in the terminal x number of columns.
     *
     * @param int $cols
     *
     * @return string
     */
    public function left($cols = 1)
    {
        return "\33[{$cols}D";
    }
    /**
     * Move cursor to the beginning of the current line.
     *
     * @return string
     */
    public function startOfCurrentLine()
    {
        return "\r";
    }
    /**
     * Delete the current line to the end.
     *
     * @return string
     */
    public function deleteCurrentLine()
    {
        return "\33[K";
    }
    /**
     * Get the style for hiding the cursor
     *
     * @return string
     */
    public function hide()
    {
        return "\33[?25l";
    }
    /**
     * Get the style for returning the cursor to its default
     *
     * @return string
     */
    public function defaultStyle()
    {
        return "\33[?25h";
    }
}

?>