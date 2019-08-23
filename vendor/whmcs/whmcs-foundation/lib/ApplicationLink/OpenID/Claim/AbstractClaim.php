<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\OpenID\Claim;

abstract class AbstractClaim
{
    protected $user = NULL;
    protected $claimName = NULL;
    public function __construct(\WHMCS\User\UserInterface $user, $claimName = NULL)
    {
        $this->setUser($user);
        if ($claimName) {
            $this->setClaimName($claimName);
        }
        $this->hydrate();
    }
    public function getUser()
    {
        return $this->user;
    }
    public function setUser(\WHMCS\User\UserInterface $user)
    {
        $this->user = $user;
        return $this;
    }
    public function getClaimName()
    {
        return $this->claimName;
    }
    public function setClaimName($claimName)
    {
        $this->claimName = $claimName;
        return $this;
    }
    public function toArray()
    {
        $data = array();
        $properties = get_object_vars($this);
        foreach ($properties as $propName => $propValue) {
            if ($propName == "user" || $propName == "claimName") {
                continue;
            }
            $data[$propName] = $propValue;
        }
        return $data;
    }
    protected abstract function hydrate();
}

?>