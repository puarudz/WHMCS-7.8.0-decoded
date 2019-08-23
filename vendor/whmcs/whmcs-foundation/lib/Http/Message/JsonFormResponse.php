<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class JsonFormResponse extends JsonResponse
{
    public static function createWithSuccess($data = NULL)
    {
        return new static(array("data" => $data));
    }
    public static function createWithErrors(array $data)
    {
        return new static(array("fields" => $data), \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
    }
}

?>