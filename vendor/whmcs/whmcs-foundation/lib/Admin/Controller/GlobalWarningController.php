<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Controller;

class GlobalWarningController
{
    public function dismiss(\WHMCS\Http\Message\ServerRequest $request)
    {
        $alertToDismiss = $request->get("alert");
        (new \WHMCS\Admin\ApplicationSupport\View\Html\Helper\GlobalWarning())->updateDismissalTracker($alertToDismiss);
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
    }
}

?>