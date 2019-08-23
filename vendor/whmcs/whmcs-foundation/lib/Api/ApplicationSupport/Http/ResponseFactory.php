<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Http;

class ResponseFactory
{
    const RESPONSE_FORMAT_NVP = "nvp";
    const RESPONSE_FORMAT_XML = "xml";
    const RESPONSE_FORMAT_JSON = "json";
    const RESPONSE_FORMAT_DEFAULT_HIGHLY_STRUCTURED = self::RESPONSE_FORMAT_XML;
    const RESPONSE_FORMAT_DEFAULT_BASIC_STRUCTURED = self::RESPONSE_FORMAT_NVP;
    public static function factory(ServerRequest $request, array $responseData, $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_OK)
    {
        $responseType = $request->getResponseFormat();
        if ($responseType == static::RESPONSE_FORMAT_JSON) {
            try {
                $response = new \WHMCS\Http\Message\JsonResponse($responseData, $statusCode);
            } catch (\Exception $e) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $jsonError = json_last_error_msg();
                    $responseData = array("result" => "error", "message" => "Error generating JSON encoded response: " . $jsonError);
                } else {
                    $responseData = array("result" => "error", "message" => $e->getMessage());
                }
                $response = new \WHMCS\Http\Message\JsonResponse($responseData, $statusCode);
            }
        } else {
            if ($responseType == static::RESPONSE_FORMAT_XML) {
                $responseData = array_merge(array("action" => $request->getAction()), $responseData);
                $response = new \WHMCS\Http\Message\XmlResponse($responseData, $statusCode);
            } else {
                $responseStr = array();
                foreach ($responseData as $k => $v) {
                    $responseStr[] = (string) $k . "=" . $v;
                }
                $response = new \Zend\Diactoros\Response\TextResponse(implode(";", $responseStr), $statusCode);
            }
        }
        return $response;
    }
    public static function getSupportedResponseTypes()
    {
        return array(static::RESPONSE_FORMAT_JSON, static::RESPONSE_FORMAT_XML, static::RESPONSE_FORMAT_NVP);
    }
    public static function isValidResponseType($type)
    {
        return in_array($type, static::getSupportedResponseTypes());
    }
    public static function isTypeHighlyStructured($type)
    {
        $highlyStructuredTypes = array(static::RESPONSE_FORMAT_JSON, static::RESPONSE_FORMAT_XML);
        if (in_array($type, $highlyStructuredTypes)) {
            return true;
        }
        return false;
    }
}

?>