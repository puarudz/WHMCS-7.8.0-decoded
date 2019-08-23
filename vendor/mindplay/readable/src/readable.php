<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace mindplay;

use Closure;
use Error;
use ErrorException;
use Exception;
use ReflectionFunction;
use Throwable;
/**
 * Pseudo-namespace for functions that generate human-readable string representations
 * of most types of PHP values.
 */
abstract class readable
{
    /**
     * @var int strings longer than this number of characters will be truncated when formatting string-values
     */
    public static $max_string_length = 120;
    /**
     * @var string[] map where PHP error severity code => constant name
     */
    public static $severity_names = [E_ERROR => "E_ERROR", E_USER_ERROR => "E_USER_ERROR", E_CORE_ERROR => "E_CORE_ERROR", E_COMPILE_ERROR => "E_COMPILE_ERROR", E_PARSE => "E_PARSE", E_WARNING => "E_WARNING", E_USER_WARNING => "E_USER_WARNING", E_CORE_WARNING => "E_CORE_WARNING", E_COMPILE_WARNING => "E_COMPILE_WARNING", E_NOTICE => "E_NOTICE", E_USER_NOTICE => "E_USER_NOTICE", E_STRICT => "E_STRICT", E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR", E_DEPRECATED => "E_DEPRECATED", E_USER_DEPRECATED => "E_USER_DEPRECATED"];
    /**
     * @param mixed $value any type of PHP value
     *
     * @return string readable representation of the given value
     */
    public static function value($value)
    {
        $type = is_array($value) && is_callable($value) ? "callable" : strtolower(gettype($value));
        switch ($type) {
            case "boolean":
                return $value ? "true" : "false";
            case "integer":
                return number_format($value, 0, "", "");
            case "double":
                // (for historical reasons "double" is returned in case of a float, and not simply "float")
                $formatted = sprintf("%.6g", $value);
                return $value == $formatted ? "{$formatted}" : "~{$formatted}";
            case "string":
                $string = strlen($value) > self::$max_string_length ? substr($value, 0, self::$max_string_length) . "...[" . strlen($value) . "]" : $value;
                return '"' . addslashes($string) . '"';
            case "array":
                return "[" . self::values($value) . "]";
            case "object":
                if ($value instanceof Closure) {
                    $reflection = new ReflectionFunction($value);
                    return "{Closure in " . $reflection->getFileName() . "({$reflection->getStartLine()})}";
                }
                return "{" . ($value instanceof \stdClass ? "object" : get_class($value)) . "}";
            case "resource":
                return "{" . get_resource_type($value) . "}";
            case "callable":
                return is_object($value[0]) ? '{' . get_class($value[0]) . "}->{$value[1]}()" : "{$value[0]}::{$value[1]}()";
            case "null":
                return "null";
        }
        return "{{$type}}";
        // "unknown type" and possibly unsupported (future) types
    }
    /**
     * @param array $array array containing any type of PHP values
     *
     * @return string comma-separated human-readable representation of the given values
     */
    public static function values(array $array)
    {
        $formatted = array_map(['mindplay\\readable', 'value'], $array);
        if (array_keys($array) !== range(0, count($array) - 1)) {
            foreach ($formatted as $name => $value) {
                $formatted[$name] = self::value($name) . " => {$value}";
            }
        }
        return implode(", ", $formatted);
    }
    /**
     * @param mixed $value any type of PHP value
     *
     * @return string human-readable type-name
     */
    public static function typeof($value)
    {
        $type = !is_string($value) && !is_object($value) && is_callable($value) ? "callable" : strtolower(gettype($value));
        switch ($type) {
            case "boolean":
                return "bool";
            case "integer":
                return "int";
            case "double":
                return "float";
            case "object":
                return $value instanceof \stdClass ? "object" : get_class($value);
            case "string":
            case "array":
            case "resource":
            case "callable":
            case "null":
                return $type;
        }
        return "unknown";
    }
    /**
     * @param mixed $callable
     *
     * @return string human-readable description of callback
     */
    public static function callback($callable)
    {
        if (is_string($callable) && is_callable($callable)) {
            return "{$callable}()";
        } elseif (is_object($callable) && method_exists($callable, "__invoke")) {
            return $callable instanceof Closure ? self::value($callable) : "{" . get_class($callable) . "}->__invoke()";
        }
        return self::value($callable);
    }
    /**
     * @param Exception|Error|Throwable|int $error an Exception, Error (or one of the E_* error severity constants)
     *
     * @return string
     */
    public static function error($error)
    {
        if (is_int($error)) {
            return static::severity($error);
        }
        $type = get_class($error);
        if ($error instanceof ErrorException) {
            $severity = static::severity($error->getSeverity());
            $type = "{$type}: {$severity}";
        }
        if ($error instanceof Exception || $error instanceof Error) {
            $message = $error->getMessage() ?: '{none}';
            $file = $error->getFile() ? $error->getFile() . "(" . $error->getLine() . ")" : "{no file}";
            return "{$type} with message: {$message} in {$file}";
        }
        return $type;
    }
    /**
     * @param int $severity one of the E_* error severity constants
     *
     * @return string
     */
    public static function severity($severity)
    {
        return isset(self::$severity_names[$severity]) ? self::$severity_names[$severity] : "{unknown error-code}";
    }
    /**
     * @param array|Exception|Error|Throwable $source      Exception, Error or stack-trace data as provided
     *                                                     by Exception::getTrace() or by debug_backtrace()
     * @param bool                            $with_params if TRUE, calls will be formatted with parameters
     *
     * @return string
     */
    public static function trace($source, $with_params = true)
    {
        if ($source instanceof Exception || $source instanceof Error) {
            $trace = $source->getTrace();
        } elseif (is_array($source)) {
            $trace = $source;
        } else {
            return "{stack-trace unavailable}";
        }
        $formatted = [];
        foreach ($trace as $index => $entry) {
            $line = array_key_exists("line", $entry) ? ":" . $entry["line"] : "";
            $file = isset($entry["file"]) ? $entry["file"] : "{no file}";
            $function = isset($entry["class"]) ? $entry["class"] . @$entry["type"] . @$entry["function"] : @$entry["function"];
            if ($function === "require" || $function === "include") {
                // bypass argument formatting for include and require statements
                $args = isset($entry["args"]) && is_array($entry["args"]) ? reset($entry["args"]) : "";
            } else {
                $args = $with_params && isset($entry["args"]) && is_array($entry["args"]) ? static::values($entry["args"]) : "";
            }
            $call = $function ? "{$function}({$args})" : "";
            $depth = $index + 1;
            $formatted[] = sprintf("%6s", "{$depth}.") . " {$file}{$line} {$call}";
        }
        return implode("\n", $formatted);
    }
}

?>