<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\OpenID\Claim;

class Email extends AbstractClaim
{
    public $email = NULL;
    public $email_verified = NULL;
    public function hydrate()
    {
        $user = $this->getUser();
        $this->email = $user->email;
        $this->email_verified = false;
        return $this;
    }
}

?>