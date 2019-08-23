<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea\Account;

class PaymentMethodsController
{
    protected function initView()
    {
        $view = new \WHMCS\ClientArea();
        $view->addOutputHookFunction("ClientAreaPaymentMethods");
        $view->setPageTitle(\Lang::trans("paymentMethods.title"));
        $view->setDisplayTitle(\Lang::trans("paymentMethods.title"));
        $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $view->addToBreadCrumb("clientarea.php", \Lang::trans("clientareatitle"));
        $view->addToBreadCrumb(routePath("account-index"), \Lang::trans("clientareanavdetails"));
        $view->addToBreadCrumb(routePath("account-paymentmethods"), \Lang::trans("paymentMethods.title"));
        $sidebarName = "clientView";
        \Menu::primarySidebar($sidebarName);
        \Menu::secondarySidebar($sidebarName);
        return $view;
    }
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!\WHMCS\Session::get("uid")) {
            return $this->initView()->requireLogin();
        }
        $view = $this->initView();
        $view->setTemplate("account-paymentmethods");
        $client = \WHMCS\User\Client::with("payMethods", "payMethods.payment")->find(\WHMCS\Session::get("uid"));
        foreach ($client->payMethods as $payMethod) {
            if (!$payMethod->payment->getSensitiveData()) {
                logActivity("Automatically Removed Payment Method without Encrypted Data." . " PayMethod ID: " . $payMethod->id . " User ID: " . $payMethod->userid, \WHMCS\Session::get("uid"));
                $payMethod->delete();
            }
        }
        $data = array("setDefaultResult" => \WHMCS\Session::getAndDelete("payMethodDefaultResult"), "deleteResult" => \WHMCS\Session::getAndDelete("payMethodDeleteResult"), "createSuccess" => \WHMCS\Session::getAndDelete("payMethodCreateSuccess"), "createFailed" => \WHMCS\Session::getAndDelete("payMethodCreateFailed"), "saveSuccess" => \WHMCS\Session::getAndDelete("payMethodSaveSuccess"), "saveFailed" => \WHMCS\Session::getAndDelete("payMethodSaveFailed"), "allowDelete" => \WHMCS\Config\Setting::getValue("CCAllowCustomerDelete"), "allowBankDetails" => (new \WHMCS\Gateways())->isLocalBankAccountGatewayAvailable());
        $view->setTemplateVariables($data);
        return $view;
    }
    public function add(\WHMCS\Http\Message\ServerRequest $request, $payMethod = NULL)
    {
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return $this->initView()->requireLogin();
        }
        $view = $this->initView();
        $view->setTemplate("account-paymentmethods-manage");
        $view->addToBreadCrumb(routePath("account-paymentmethods-add"), \Lang::trans("paymentMethodsManage.addPaymentMethod"));
        $inputType = $request->get("type");
        if (is_null($payMethod)) {
            $payMethod = new \WHMCS\Payment\PayMethod\Model();
        }
        $gatewaysHelper = new \WHMCS\Gateways();
        $activeMerchantGateways = $gatewaysHelper->getActiveMerchantGatewaysByType();
        $allTokenGateways = array_merge($activeMerchantGateways["token"], $activeMerchantGateways["remote"], $activeMerchantGateways["assisted"]);
        $visibleTokenGateways = array();
        foreach ($allTokenGateways as $gateway => $isVisible) {
            if (!$isVisible && (!$payMethod->exists || $payMethod->gateway_name !== $gateway)) {
                continue;
            }
            $visibleTokenGateways[] = $gateway;
        }
        $countries = new \WHMCS\Utility\Country();
        $remoteUpdate = "";
        if ($payMethod->exists && $payMethod->isRemoteCreditCard()) {
            $gatewayInterface = $payMethod->getGateway();
            if ($gatewayInterface->functionExists("remoteupdate")) {
                if (!function_exists("getClientsDetails")) {
                    require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
                }
                $passedParams = getClientsDetails($client, $payMethod->getContactId());
                $passedParams["gatewayid"] = $payMethod->payment->getRemoteToken();
                $passedParams["payMethod"] = $payMethod;
                $remoteUpdate = $gatewayInterface->call("remoteupdate", $passedParams);
            }
        }
        $data = array("csrfToken" => generate_token("plain"), "enabledTypes" => array("tokenGateways" => 0 < count($activeMerchantGateways["token"]) + count($activeMerchantGateways["assisted"]) + count($activeMerchantGateways["remote"]), "localCreditCard" => $gatewaysHelper->isLocalCreditCardStorageEnabled(), "bankAccount" => (new \WHMCS\Gateways())->isLocalBankAccountGatewayAvailable()), "paymentMethodType" => $inputType ? $inputType : "creditcard", "tokenGateways" => $visibleTokenGateways, "gatewayDisplayNames" => $gatewaysHelper->getDisplayNames(), "editMode" => $payMethod->exists, "payMethod" => $payMethod, "creditCard" => $payMethod->exists && $payMethod->isCreditCard() ? $payMethod->payment : new \WHMCS\Payment\PayMethod\Adapter\CreditCard(), "bankAccount" => $payMethod->exists && !$payMethod->isCreditCard() ? $payMethod->payment : new \WHMCS\Payment\PayMethod\Adapter\BankAccount(), "dateMonths" => $gatewaysHelper->getCCDateMonths(), "startDateYears" => $gatewaysHelper->getCCStartDateYears(), "expiryDateYears" => $gatewaysHelper->getCCExpiryDateYears(), "startDateEnabled" => $gatewaysHelper->isIssueDateAndStartNumberEnabled(), "issueNumberEnabled" => $gatewaysHelper->isIssueDateAndStartNumberEnabled(), "creditCardNumberFieldEnabled" => !$payMethod->exists, "creditCardExpiryFieldEnabled" => true, "creditCardCvcFieldEnabled" => !$payMethod->exists, "countries" => $countries->getCountryNameArray(), "clientCountry" => $client->country, "remoteUpdate" => $remoteUpdate);
        $view->setTemplateVariables($data);
        return $view;
    }
    public function initToken(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!\WHMCS\Session::get("uid")) {
            return new \WHMCS\Http\Message\JsonResponse(array());
        }
        $gateway = $request->request()->get("gateway");
        $workflowType = null;
        $assistedOutput = null;
        $remoteInputForm = null;
        $gatewayInterface = new \WHMCS\Module\Gateway();
        if ($gatewayInterface->load($gateway)) {
            $workflowType = $gatewayInterface->getWorkflowType();
            switch ($workflowType) {
                case \WHMCS\Module\Gateway::WORKFLOW_ASSISTED:
                    $assistedOutput = $gatewayInterface->call("credit_card_input");
                    break;
                case \WHMCS\Module\Gateway::WORKFLOW_REMOTE:
                    if ($gatewayInterface->functionExists("remoteinput")) {
                        if (!function_exists("getClientsDetails")) {
                            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
                        }
                        $passedParams = array();
                        $passedParams["clientdetails"] = getClientsDetails(\WHMCS\Session::get("uid"));
                        $remoteInputForm = $gatewayInterface->call("remoteinput", $passedParams);
                    }
                    break;
                default:
                    return new \WHMCS\Http\Message\JsonResponse(array("workflowType" => $workflowType, "assistedOutput" => $assistedOutput, "remoteInputForm" => $remoteInputForm));
            }
        } else {
            throw new Exception("Invalid gateway name provided.");
        }
    }
    public function create(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return $this->initView()->requireLogin();
        }
        $post = $request->request();
        $inputType = $post->get("type");
        $inputDescription = $post->get("description");
        $inputCardNum = $post->get("ccnumber", "");
        $inputCardStartDate = $post->get("ccstart", "");
        $inputCardExpiryDate = $post->get("ccexpiry", "");
        $inputCardCvv = $post->get("cardcvv", "");
        $inputCardIssueNum = $post->get("ccissuenum", "");
        $inputBankAcctType = $post->get("bankaccttype");
        $inputBankName = $post->get("bankname");
        $inputBankAcctHolderName = $post->get("bankacctholdername");
        $inputBankRoutingNum = $post->get("bankroutingnum");
        $inputBankAcctNum = $post->get("bankacctnum");
        $inputBillingContact = $post->get("billingcontact");
        $inputMakeDefault = (bool) $post->get("makedefault", false);
        $inputRemoteStorageToken = $post->get("remoteStorageToken", "");
        $tokenGatewayInterface = null;
        if (substr($inputType, 0, 5) === "token") {
            $gatewayModuleName = substr($inputType, 6);
            $tokenGatewayInterface = new \WHMCS\Module\Gateway();
            if (!$tokenGatewayInterface->load($gatewayModuleName)) {
                \WHMCS\Session::set("payMethodSaveFailed", true);
                return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
            }
        }
        $billingContact = $inputBillingContact ? $client->contacts()->find($inputBillingContact) : $client;
        $payMethod = null;
        try {
            if ($inputType === "localcard") {
                $resolver = new \WHMCS\Gateways();
                if (!$resolver->isLocalCreditCardStorageEnabled()) {
                    \WHMCS\Session::set("payMethodSaveFailed", true);
                    return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
                }
                $payMethod = \WHMCS\Payment\PayMethod\Adapter\CreditCard::factoryPayMethod($client, $billingContact, $inputDescription);
                $payment = $payMethod->payment;
                if ($inputCardNum) {
                    $payment->setCardNumber($inputCardNum);
                }
                if ($inputCardStartDate) {
                    $payment->setStartDate(\WHMCS\Carbon::createFromCcInput($inputCardStartDate));
                }
                if ($inputCardExpiryDate) {
                    $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($inputCardExpiryDate));
                }
                if ($inputCardIssueNum) {
                    $payment->setIssueNumber($inputCardIssueNum);
                }
                $payment->validateRequiredValuesPreSave()->save();
            } else {
                if ($inputType === "bankacct") {
                    $resolver = new \WHMCS\Gateways();
                    if (!$resolver->isLocalBankAccountGatewayAvailable()) {
                        \WHMCS\Session::set("payMethodSaveFailed", true);
                        return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
                    }
                    $payMethod = \WHMCS\Payment\PayMethod\Adapter\BankAccount::factoryPayMethod($client, $billingContact, $inputDescription);
                    $payMethod->payment->setAccountType($inputBankAcctType)->setAccountHolderName($inputBankAcctHolderName)->setBankName($inputBankName)->setRoutingNumber($inputBankRoutingNum)->setAccountNumber($inputBankAcctNum)->validateRequiredValuesPreSave()->save();
                } else {
                    if ($tokenGatewayInterface) {
                        $payMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($client, $billingContact, $inputDescription);
                        $payMethod->setGateway($tokenGatewayInterface)->save();
                        $payment = $payMethod->payment->setRemoteToken($inputRemoteStorageToken);
                        if ($inputCardNum) {
                            $payment->setCardNumber($inputCardNum);
                        }
                        if ($inputCardStartDate) {
                            $payment->setStartDate(\WHMCS\Carbon::createFromCcInput($inputCardStartDate));
                        }
                        if ($inputCardExpiryDate) {
                            $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($inputCardExpiryDate));
                        }
                        if ($inputCardCvv) {
                            $payment->setCardCvv($inputCardCvv);
                        }
                        if ($inputCardIssueNum) {
                            $payment->setIssueNumber($inputCardIssueNum);
                        }
                        $payment->validateRequiredValuesPreSave()->createRemote()->save();
                    }
                }
            }
            if ($inputMakeDefault) {
                $payMethod->setAsDefaultPayMethod();
            }
            logActivity("Pay Method Created - " . $payMethod->payment->getDisplayName() . " - User ID: " . $payMethod->client->id, $payMethod->client->id);
            \WHMCS\Session::set("payMethodCreateSuccess", true);
        } catch (\Exception $e) {
            \WHMCS\Session::set("payMethodCreateFailed", true);
            if ($payMethod) {
                $payMethod->delete();
            }
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
    }
    public function manage(\WHMCS\Http\Message\ServerRequest $request)
    {
        $payMethodId = $request->get("id");
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        $payMethod = $client->payMethods()->where("id", $payMethodId)->first();
        if (is_null($payMethod)) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        return $this->add($request, $payMethod);
    }
    public function save(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $payMethodId = $request->get("id");
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        $payMethod = $client->payMethods()->where("id", $payMethodId)->first();
        if (is_null($payMethod)) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        if (($payMethod->isRemoteCreditCard() || $payMethod->isRemoteBankAccount()) && !$payMethod->getGateway()) {
            \WHMCS\Session::set("payMethodSaveFailed", true);
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        $post = $request->request();
        $inputDescription = $post->get("description");
        $inputCardStartDate = $post->get("ccstart", "");
        $inputCardExpiryDate = $post->get("ccexpiry", "");
        $inputCardIssueNum = $post->get("ccissuenum");
        $inputBankAcctType = $post->get("bankaccttype");
        $inputBankAcctHolderName = $post->get("bankacctholdername");
        $inputBankName = $post->get("bankname");
        $inputBankRoutingNum = $post->get("bankroutingnum");
        $inputBankAcctNum = $post->get("bankacctnum");
        $inputBillingContact = $post->get("billingcontact");
        $inputMakeDefault = (bool) $post->get("makedefault");
        $billingContact = $inputBillingContact ? $client->contacts()->find($inputBillingContact) : $client;
        try {
            $payMethod->setDescription($inputDescription)->contact()->associate($billingContact)->save();
            if ($payMethod->isRemoteCreditCard()) {
                $payment = $payMethod->payment->setIssueNumber($inputCardIssueNum);
                if ($inputCardStartDate) {
                    $payment->setStartDate(\WHMCS\Carbon::createFromCcInput($inputCardStartDate));
                }
                if ($inputCardExpiryDate) {
                    $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($inputCardExpiryDate));
                }
                $payment->validateRequiredValuesForEditPreSave()->updateRemote()->save();
            } else {
                if ($payMethod->isCreditCard()) {
                    $payment = $payMethod->payment->setIssueNumber($inputCardIssueNum);
                    if ($inputCardStartDate) {
                        $payment->setStartDate(\WHMCS\Carbon::createFromCcInput($inputCardStartDate));
                    }
                    if ($inputCardExpiryDate) {
                        $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($inputCardExpiryDate));
                    }
                    $payment->validateRequiredValuesForEditPreSave()->save();
                } else {
                    if ($payMethod->isBankAccount()) {
                        $payMethod->payment->setAccountType($inputBankAcctType)->setAccountHolderName($inputBankAcctHolderName)->setBankName($inputBankName)->setRoutingNumber($inputBankRoutingNum)->setAccountNumber($inputBankAcctNum)->validateRequiredValuesPreSave()->save();
                    }
                }
            }
            if ($inputMakeDefault) {
                $payMethod->setAsDefaultPayMethod();
            }
            logActivity("Pay Method Updated - " . $payMethod->payment->getDisplayName() . " - User ID: " . $payMethod->client->id, $payMethod->client->id);
            \WHMCS\Session::set("payMethodSaveSuccess", true);
        } catch (\Exception $e) {
            logActivity($e->getMessage());
            \WHMCS\Session::set("payMethodSaveFailed", true);
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
    }
    public function setDefault(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $payMethodId = $request->get("id");
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        $payMethod = $client->payMethods()->where("id", $payMethodId)->first();
        if (!is_null($payMethod)) {
            $payMethod->setAsDefaultPayMethod();
            logActivity("Pay Method Set Default - " . $payMethod->payment->getDisplayName() . " - User ID: " . $payMethod->client->id, $payMethod->client->id);
            \WHMCS\Session::set("payMethodDefaultResult", true);
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
        }
        \WHMCS\Session::set("payMethodDefaultResult", false);
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
    }
    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $payMethodId = $request->get("id");
        if (\WHMCS\Config\Setting::getValue("CCAllowCustomerDelete")) {
            $client = \WHMCS\User\Client::loggedIn()->first();
            if (!$client) {
                return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
            }
            $payMethod = $client->payMethods()->where("id", $payMethodId)->first();
            if (!is_null($payMethod)) {
                if ($payMethod->isRemoteCreditCard()) {
                    $payMethod->payment->deleteRemote();
                }
                logActivity("Pay Method Deleted - " . $payMethod->payment->getDisplayName() . " - User ID: " . $payMethod->client->id, $payMethod->client->id);
                $payMethod->delete();
                \WHMCS\Session::set("payMethodDeleteResult", true);
                return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
            }
        }
        \WHMCS\Session::set("payMethodDeleteResult", false);
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("account-paymentmethods"));
    }
    public function getBillingContacts(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!\WHMCS\Session::get("uid")) {
            return "";
        }
        $view = $this->initView();
        $view->setTemplate("account-paymentmethods-billing-contacts");
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return "";
        }
        $payMethod = null;
        if ($request->has("id")) {
            $payMethod = $client->payMethods()->where("id", $request->get("id"))->first();
        }
        if (is_null($payMethod)) {
            $payMethod = new \WHMCS\Payment\PayMethod\Model();
        }
        $data = array("editMode" => $payMethod->exists, "payMethod" => $payMethod, "client" => $client, "selectedContactId" => $request->get("contact_id") ?: $payMethod->getContactId());
        $view->setTemplateVariables($data);
        return $view->getSingleTPLOutput("account-paymentmethods-billing-contacts");
    }
    public function createBillingContact(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $client = \WHMCS\User\Client::loggedIn()->first();
        if (!$client) {
            return \WHMCS\Http\Message\JsonFormResponse::createWithErrors(array());
        }
        if (!function_exists("validateContactDetails")) {
            require_once ROOTDIR . "/includes/clientfunctions.php";
        }
        $validator = validateContactDetails();
        $errorFields = $validator->getErrorFields();
        if (!empty($errorFields)) {
            return \WHMCS\Http\Message\JsonFormResponse::createWithErrors(array_combine($errorFields, $validator->getErrors()));
        }
        $firstname = \App::getFromRequest("firstname");
        $lastname = \App::getFromRequest("lastname");
        $companyname = \App::getFromRequest("companyname");
        $address1 = \App::getFromRequest("address1");
        $address2 = \App::getFromRequest("address2");
        $city = \App::getFromRequest("city");
        $state = \App::getFromRequest("state");
        $postcode = \App::getFromRequest("postcode");
        $country = \App::getFromRequest("country");
        $phonenumber = \App::getFromRequest("phonenumber");
        $taxId = \App::getFromRequest("tax_id");
        $contactId = addContact($client->id, $firstname, $lastname, $companyname, $client->email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, "", array(), "", "", "", "", "", "", $taxId);
        return \WHMCS\Http\Message\JsonFormResponse::createWithSuccess(array("id" => $contactId));
    }
    public function remoteInput(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $gatewayModule = $request->get("gateway");
        $invoiceId = $request->get("invoice_id");
        if (!$gatewayModule || !$invoiceId) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => \Lang::trans("invoiceserror")));
        }
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gateway->load($gatewayModule)) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => \Lang::trans("invoiceserror")));
        }
        if (!$gateway->functionExists("remoteinput")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        if (!function_exists("getCCVariables")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ccfunctions.php";
        }
        $params = getCCVariables($invoiceId, $gatewayModule);
        $output = $gateway->call("remoteinput", $params);
        $output = str_replace("<form", "<form target=\"3dauth\"", $output);
        return new \WHMCS\Http\Message\JsonResponse(array("output" => $output));
    }
}

?>