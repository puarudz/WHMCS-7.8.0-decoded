<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http;

class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse
{
    use DataTrait;
    use PriceDataTrait;
    public function setData($data = array())
    {
        $data = $this->mutatePriceToFull($data);
        $this->setRawData($data);
        parent::setData($data);
        return $this;
    }
}

?>