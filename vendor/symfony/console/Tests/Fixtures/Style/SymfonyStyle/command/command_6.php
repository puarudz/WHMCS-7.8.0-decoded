<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Style\SymfonyStyleWithForcedLineLength;
//Ensure has proper blank line after text block when using a block like with SymfonyStyle::success
return function (InputInterface $input, OutputInterface $output) {
    $output = new SymfonyStyleWithForcedLineLength($input, $output);
    $output->listing(array('Lorem ipsum dolor sit amet', 'consectetur adipiscing elit'));
    $output->success('Lorem ipsum dolor sit amet');
};

?>