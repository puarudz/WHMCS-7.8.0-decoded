<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Announcement\View;

class Item extends Index
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->setTemplate("viewannouncement");
    }
}

?>