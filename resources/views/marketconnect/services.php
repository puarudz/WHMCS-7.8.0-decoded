<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$this->layout("layouts/admin-content");
$this->start("body");
echo "\n<div class=\"market-connect-apps-container\">\n    <div class=\"row\">\n        ";
$count = 0;
foreach ($services as $service => $data) {
    $count++;
    $this->insert("shared/service", array("service" => $service, "state" => $state, "data" => $data, "count" => $count));
}
echo "    </div>\n</div>\n\n<a href=\"https://marketplace.whmcs.com/contact/connect\" target=\"_blank\" class=\"btn btn-default pull-right\" style=\"margin-left:6px;\">\n    <i class=\"fas fa-envelope fa-fw\"></i>\n    Contact Support\n</a>\n<a href=\"https://marketplace.whmcs.com/help/connect/kb\" target=\"_blank\" class=\"btn btn-default pull-right\" style=\"margin-left:6px;\">\n    <i class=\"fas fa-question-circle fa-fw\"></i>\n    Visit Knowledgebase\n</a>\n<a href=\"https://marketplace.whmcs.com/promotions\" target=\"_blank\" class=\"btn btn-default pull-right\">\n    <i class=\"fas fa-ticket-alt fa-fw\"></i>\n    Current Promotions\n</a>\n\n";
$this->insert("shared/tour");
$this->end();

?>