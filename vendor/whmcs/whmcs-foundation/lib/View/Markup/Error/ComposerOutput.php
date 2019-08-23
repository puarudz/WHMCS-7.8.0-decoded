<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error;

class ComposerOutput extends AbstractConsoleOutput
{
    public static function filterComposerNoise($line)
    {
        $line = trim($line);
        $line = str_replace(": mysql_connect(): The mysql extension is deprecated and will be removed in the future: use mysqli or PDO instead", "", $line);
        if (strpos($line, "update [--") === false) {
            return $line;
        }
        return "";
    }
    public static function getComposerOutputStack($data)
    {
        $stack = new \SplStack();
        $messages = explode("\n", $data);
        foreach ($messages as $message) {
            if ($message = static::filterComposerNoise($message)) {
                $stack->push($message);
            }
        }
        return $stack;
    }
    public function getIterator()
    {
        return static::getComposerOutputStack($this->getText());
    }
    protected function getMatchDecorators()
    {
        return array(new Message\MatchDecorator\SystemRequirements\DiskQuotaExceeded(), new Message\MatchDecorator\SystemRequirements\FunctionDisabled(), new Message\MatchDecorator\Validation\InvalidCertificate(), new Message\MatchDecorator\NetworkIssue\FailedKeyserverFetch(), new Message\MatchDecorator\FilePermission\NotWritablePath(), new Message\MatchDecorator\FilePermission\CacheNotWritable(), new Message\MatchDecorator\FilePermission\PostCommandCopy());
    }
}

?>