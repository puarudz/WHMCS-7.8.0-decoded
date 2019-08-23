<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase;

class ArticleCategoryLink extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblknowledgebaselinks";
    public $timestamps = false;
    public function article()
    {
        return $this->belongsTo("\\WHMCS\\Knowledgebase\\Article", "articleid");
    }
    public function category()
    {
        return $this->belongsTo("\\WHMCS\\Knowledgebase\\Category", "categoryid");
    }
}

?>