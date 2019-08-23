<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace M101Domain;

class Domain
{
    public $name = NULL;
    public $status = array();
    public $registrant = NULL;
    public $contacts = array();
    public $ns = array();
    public $cr_date = NULL;
    public $up_date = NULL;
    public $ex_date = NULL;
    public $key = NULL;
    protected $lockedStatuses = array("clientTransferProhibited", "clientHold", "serverTransferProhibited", "serverHold");
    public function isLocked()
    {
        foreach ($this->status as $status) {
            if (in_array($status, $this->lockedStatuses)) {
                return true;
            }
        }
        return false;
    }
}

?>