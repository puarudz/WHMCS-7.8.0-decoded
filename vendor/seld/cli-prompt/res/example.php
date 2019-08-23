<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require __DIR__ . '/../vendor/autoload.php';
echo 'Say hello (visible): ';
$answer = Seld\CliPrompt\CliPrompt::prompt();
echo 'You answered: ' . $answer . PHP_EOL;
echo 'Say hello (hidden): ';
$answer = Seld\CliPrompt\CliPrompt::hiddenPrompt();
echo 'You answered: ' . $answer . PHP_EOL;

?>