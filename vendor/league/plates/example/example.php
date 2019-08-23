<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

include '../vendor/autoload.php';
// Create new Plates instance
$templates = new League\Plates\Engine('templates');
// Preassign data to the layout
$templates->addData(['company' => 'The Company Name'], 'layout');
// Render a template
echo $templates->render('profile', ['name' => 'Jonathan']);

?>