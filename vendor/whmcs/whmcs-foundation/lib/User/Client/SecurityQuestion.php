<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User\Client;

class SecurityQuestion extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladminsecurityquestions";
    public function getQuestionAttribute($question)
    {
        return decrypt($question);
    }
    public function setQuestionAttribute($question)
    {
        $this->attributes["question"] = encrypt($question);
    }
    public function clients()
    {
        return $this->hasMany("WHMCS\\User\\Client", "securityqid");
    }
}

?>