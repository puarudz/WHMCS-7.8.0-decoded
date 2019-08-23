<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class ActionResponseFormat implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\AssumingMiddlewareTrait;
    protected $actionResponseHighlyStructured = array("domaingetwhoisinfo", "getactivitylog", "getaffiliates", "getannouncements", "getcancelledpackages", "getclientgroups", "getclients", "getclientsaddons", "getclientsdomains", "getclientsproducts", "getcontacts", "getcredits", "getcurrencies", "getemails", "getemailtemplates", "getinvoice", "getinvoices", "getmodulequeue", "getorders", "getorderstatuses", "getpaymentmethods", "getproducts", "getproject", "getprojects", "getpromotions", "getquotes", "getstaffonline", "getsupportdepartments", "getsupportstatuses", "getticket", "getticketnotes", "getticketpredefinedcats", "getticketpredefinedreplies", "gettickets", "gettodoitems", "gettodoitemstatuses", "gettransactions", "orderfraudcheck");
    protected $requiresJsonResponse = array("getadminusers");
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $request = $this->normalizeRequest($request);
        $userRequestedResponseType = $request->getResponseFormat();
        $action = $request->getAction();
        if ($this->requiresJsonResponse($action)) {
            $requiredType = \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_JSON;
            $request = $request->withAttribute("response_format", $requiredType);
        } else {
            if (!$userRequestedResponseType) {
                if ($this->isActionResponseHighlyStructured($action)) {
                    $assumedType = \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_DEFAULT_HIGHLY_STRUCTURED;
                } else {
                    $assumedType = \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_DEFAULT_BASIC_STRUCTURED;
                }
                $request = $request->withAttribute("response_format", $assumedType);
            } else {
                if (!\WHMCS\Api\ApplicationSupport\Http\ResponseFactory::isValidResponseType($userRequestedResponseType)) {
                    throw new \WHMCS\Exception\Api\InvalidResponseType(sprintf("Unsupported API response type format \"%s\". Supported formats include: %s", $userRequestedResponseType, implode(", ", \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::getSupportedResponseTypes())));
                }
                if (!$this->isResponseTypeAllowed($action, $userRequestedResponseType)) {
                    throw new \WHMCS\Exception\Api\InvalidResponseType("This API action only supports the XML or JSON response type formats");
                }
            }
        }
        return $delegate->process($request);
    }
    public function isResponseTypeAllowed($action, $type)
    {
        if (!$this->isActionResponseHighlyStructured($action)) {
            return true;
        }
        return \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::isTypeHighlyStructured($type);
    }
    public function isActionResponseHighlyStructured($action)
    {
        return in_array($action, $this->actionResponseHighlyStructured);
    }
    public function requiresJsonResponse($action)
    {
        return in_array($action, $this->requiresJsonResponse);
    }
    protected function normalizeRequest(\WHMCS\Api\ApplicationSupport\Http\ServerRequest $request)
    {
        $userRequestedResponseType = $request->getResponseFormat();
        $normalizedType = strtolower($userRequestedResponseType);
        if ($normalizedType != $userRequestedResponseType) {
            $request = $request->withAttribute("response_format", $normalizedType);
        }
        return $request;
    }
}

?>