<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

abstract class TableModel extends TableQuery
{
    protected $pageObj = NULL;
    protected $queryObj = NULL;
    public function __construct(Pagination $obj = NULL)
    {
        $whmcs = \DI::make("app");
        $this->pageObj = $obj;
        $numrecords = $whmcs->get_config("NumRecordstoDisplay");
        $this->setRecordLimit($numrecords);
        return $this;
    }
    public abstract function _execute($implementationData);
    public function setPageObj(Pagination $pageObj)
    {
        $this->pageObj = $pageObj;
    }
    public function getPageObj()
    {
        return $this->pageObj;
    }
    public function execute($implementationData = NULL)
    {
        $results = $this->_execute($implementationData);
        $this->getPageObj()->setData($results);
        return $this;
    }
}

?>