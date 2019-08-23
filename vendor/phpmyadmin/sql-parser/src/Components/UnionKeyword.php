<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * `UNION` keyword builder.
 */
namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
/**
 * `UNION` keyword builder.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class UnionKeyword extends Component
{
    /**
     * @param UnionKeyword[] $component the component to be built
     * @param array          $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $tmp = array();
        foreach ($component as $component) {
            $tmp[] = $component[0] . ' ' . $component[1];
        }
        return implode(' ', $tmp);
    }
}

?>