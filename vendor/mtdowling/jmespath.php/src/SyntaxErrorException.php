<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace JmesPath;

/**
 * Syntax errors raise this exception that gives context
 */
class SyntaxErrorException extends \InvalidArgumentException
{
    /**
     * @param string $expectedTypesOrMessage Expected array of tokens or message
     * @param array  $token                  Current token
     * @param string $expression             Expression input
     */
    public function __construct($expectedTypesOrMessage, array $token, $expression)
    {
        $message = "Syntax error at character {$token['pos']}\n" . $expression . "\n" . str_repeat(' ', $token['pos']) . "^\n";
        $message .= !is_array($expectedTypesOrMessage) ? $expectedTypesOrMessage : $this->createTokenMessage($token, $expectedTypesOrMessage);
        parent::__construct($message);
    }
    private function createTokenMessage(array $token, array $valid)
    {
        return sprintf('Expected one of the following: %s; found %s "%s"', implode(', ', array_keys($valid)), $token['type'], $token['value']);
    }
}

?>