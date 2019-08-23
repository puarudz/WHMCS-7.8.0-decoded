<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

foreach ($apps->all() as $app) {
    echo "    ";
    $this->insert("apps/shared/app", array("app" => $app, "searchDisplay" => true));
}

?>