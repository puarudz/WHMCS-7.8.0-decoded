<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Support;

use Doctrine\Common\Inflector\Inflector;
class Pluralizer
{
    /**
     * Uncountable word forms.
     *
     * @var array
     */
    public static $uncountable = ['audio', 'bison', 'chassis', 'compensation', 'coreopsis', 'data', 'deer', 'education', 'equipment', 'fish', 'gold', 'information', 'knowledge', 'love', 'rain', 'money', 'moose', 'nutrition', 'offspring', 'plankton', 'police', 'rice', 'series', 'sheep', 'species', 'swine', 'traffic'];
    /**
     * Get the plural form of an English word.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if ($count === 1 || static::uncountable($value)) {
            return $value;
        }
        $plural = Inflector::pluralize($value);
        return static::matchCase($plural, $value);
    }
    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $singular = Inflector::singularize($value);
        return static::matchCase($singular, $value);
    }
    /**
     * Determine if the given value is uncountable.
     *
     * @param  string  $value
     * @return bool
     */
    protected static function uncountable($value)
    {
        return in_array(strtolower($value), static::$uncountable);
    }
    /**
     * Attempt to match the case on two strings.
     *
     * @param  string  $value
     * @param  string  $comparison
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];
        foreach ($functions as $function) {
            if (call_user_func($function, $comparison) === $comparison) {
                return call_user_func($function, $value);
            }
        }
        return $value;
    }
}

?>