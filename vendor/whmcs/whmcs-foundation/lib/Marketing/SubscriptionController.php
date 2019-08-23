<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Marketing;

class SubscriptionController
{
    public function manage(\WHMCS\Http\Message\ServerRequest $request)
    {
        $action = $request->get("action");
        $email = $request->get("email");
        $key = $request->get("key");
        $infoMessage = $errorMessage = null;
        try {
            if (empty($email)) {
                throw new \WHMCS\Exception\Validation\Required();
            }
            $client = \WHMCS\User\Client::where("email", $email)->first();
            if (is_null($client)) {
                throw new \WHMCS\Exception\Validation\Required();
            }
            $emailSubscription = new EmailSubscription();
            $emailSubscription->validateKey($client, $action, $key);
            if ($action == EmailSubscription::ACTION_OPTIN) {
                $client->marketingEmailOptIn();
            } else {
                if ($action == EmailSubscription::ACTION_OPTOUT) {
                    $client->marketingEmailOptOut();
                    sendMessage("Unsubscribe Confirmation", $client->id);
                } else {
                    $errorMessage = "Invalid action requested";
                }
            }
        } catch (\WHMCS\Exception\Marketing\AlreadyOptedIn $e) {
            $infoMessage = \Lang::trans("emailMarketingAlreadyOptedIn");
        } catch (\WHMCS\Exception\Marketing\AlreadyOptedOut $e) {
            $infoMessage = \Lang::trans("emailMarketingAlreadyOptedOut");
        } catch (\WHMCS\Exception $e) {
            $errorMessage = \Lang::trans("unsubscribehashinvalid");
        }
        $ca = new \WHMCS\ClientArea();
        $ca->setPageTitle(\Lang::trans("manageSubscription"));
        $ca->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $ca->addToBreadCrumb("clientarea.php", \Lang::trans("clientareatitle"));
        $ca->addToBreadCrumb("subscribe.php", \Lang::trans("manageSubscription"));
        $ca->initPage();
        $ca->assign("action", $action);
        $ca->assign("infoMessage", $infoMessage);
        $ca->assign("errorMessage", $errorMessage);
        $ca->setTemplate("subscription-manage");
        $ca->addOutputHookFunction("ClientAreaPageUnsubscribe");
        return $ca;
    }
}

?>