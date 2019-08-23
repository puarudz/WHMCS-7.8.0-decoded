<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote\Management\Client;

class ViewHelper
{
    private function getLinkedAccounts($clientId, $contactId = NULL)
    {
        $responseData = array("linked" => 0, "accounts" => array());
        $remoteAuth = \DI::make("remoteAuth");
        if (count($remoteAuth->getEnabledProviders()) === 0) {
            return $responseData;
        }
        $userRemoteAccountLinks = null;
        if ($contactId) {
            $contact = \WHMCS\User\Client\Contact::find($contactId);
            $userRemoteAccountLinks = $contact->remoteAccountLinks;
        } else {
            if ($clientId) {
                $client = \WHMCS\User\Client::find($clientId);
                $userRemoteAccountLinks = $client->remoteAccountLinks;
            }
        }
        if (!$userRemoteAccountLinks) {
            return $responseData;
        }
        $linkedAccounts = array();
        foreach ($userRemoteAccountLinks as $account) {
            $provider = $remoteAuth->getProviderByName($account->provider);
            $linkedAccounts[$account->id] = $provider->parseMetadata($account->metadata);
        }
        $responseData["linked"] = count($linkedAccounts);
        $responseData["accounts"] = $linkedAccounts;
        return $responseData;
    }
    public function getTemplateData($targetHtml = NULL)
    {
        if (is_null($targetHtml)) {
            $targetHtml = \WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_REGISTER;
        }
        $data = array();
        $remoteAuth = \DI::make("remoteAuth");
        $providers = $remoteAuth->getEnabledProviders();
        if (count($providers)) {
            $providersData = array();
            foreach ($providers as $provider) {
                $providersData[] = array("provider" => $provider, "code" => $provider->getHtml($targetHtml), "login_button" => $provider->getHtmlButton(\WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_LOGIN));
            }
            $data["linkableProviders"] = $providersData;
        } else {
            $data["linkableProviders"] = null;
        }
        $userLinkedProviderData = array();
        if ($providers) {
            $userLinkedProviderData = $this->getLinkedAccounts(\WHMCS\Session::get("uid"), \WHMCS\Session::get("cid"));
        }
        $data["userLinkedProviderData"] = $userLinkedProviderData;
        $data["linkedAccountsUrl"] = routePath("auth-manage-client-links");
        if ($remoteAuth->isPrelinkPerformed()) {
            $data["remote_auth_prelinked"] = true;
            $data["password"] = $remoteAuth->generateRandomPassword();
        }
        return $data;
    }
    public function getTableData($userRemoteAccountLinks)
    {
        $remoteAuth = \DI::make("remoteAuth");
        $linkedAccounts = array();
        $btn = sprintf("<div class=\"btn btn-default btn-sm\" data-toggle=\"confirmation\"\n                    data-btn-ok-label=\"%s\"\n                    data-btn-ok-icon=\"fas fa-unlink\"\n                    data-btn-ok-class=\"btn-success\"\n                    data-btn-cancel-label=\"%s\"\n                    data-btn-cancel-icon=\"fas fa-ban\"\n                    data-btn-cancel-class=\"btn-default\"\n                    data-title=\"%s\"\n                    data-content=\"%s\"\n                    data-popout=\"true\"\n                    data-target-url=%s%%d\n                    >%s</div>", \Lang::trans("unlink"), \Lang::trans("cancel"), \Lang::trans("remoteAuthn.areYouSure"), \Lang::trans("remoteAuthn.unlinkDesc"), routePath("auth-manage-client-delete"), \Lang::trans("unlink"));
        foreach ($userRemoteAccountLinks as $account) {
            $provider = $remoteAuth->getProviderByName($account->provider);
            $meta = $provider->parseMetadata($account->metadata);
            $linkedAccounts[] = array($meta->getProviderName(), $meta->getFullName(), $meta->getEmailAddress(), sprintf($btn, $account->id));
        }
        return $linkedAccounts;
    }
}

?>