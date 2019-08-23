<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud\FraudLabs;

class Response extends \WHMCS\Module\Fraud\AbstractResponse implements \WHMCS\Module\Fraud\ResponseInterface
{
    protected $failureErrorCodes = array(101, 102, 103, 104, 203, 204, 210, 211);
    public function isSuccessful()
    {
        $errorCode = $this->get("fraudlabspro_error_code");
        return $this->httpCode == 200 && (!$errorCode || !in_array($errorCode, $this->failureErrorCodes));
    }
}

?>