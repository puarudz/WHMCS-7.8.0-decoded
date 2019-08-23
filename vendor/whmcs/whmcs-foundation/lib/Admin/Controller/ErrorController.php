<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Controller;

class ErrorController
{
    use \WHMCS\Application\Support\Controller\DelegationTrait;
    public function loginRequired(\WHMCS\Http\Message\ServerRequest $request)
    {
        $msg = "Admin Login Required";
        if ($request->expectsJsonResponse()) {
            $response = new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMessage" => $msg), 403);
        } else {
            $response = $this->redirectTo("admin-login", $request);
        }
        return $response;
    }
}

?>