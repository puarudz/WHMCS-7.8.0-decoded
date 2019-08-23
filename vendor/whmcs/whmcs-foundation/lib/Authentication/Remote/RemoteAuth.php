<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote;

final class RemoteAuth
{
    private $providers = array();
    const AUTHN_CONTEXTS_VAR = "authn_provider_contexts";
    public function __construct()
    {
        $classes = static::getProviderClassNames();
        $providers = array();
        foreach ($classes as $className) {
            $provider = new $className();
            if (!$provider instanceof Providers\AbstractRemoteAuthProvider) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Invalid remote authentication provider class: " . $className);
            }
            $providers[$provider::NAME] = $provider;
        }
        ksort($providers);
        $this->providers = $providers;
    }
    private static function getProviderClassNames()
    {
        return array("\\WHMCS\\Authentication\\Remote\\Providers\\Google\\GoogleSignin", "\\WHMCS\\Authentication\\Remote\\Providers\\Facebook\\FacebookSignin", "\\WHMCS\\Authentication\\Remote\\Providers\\Twitter\\TwitterOauth");
    }
    public function getProviders()
    {
        return $this->providers;
    }
    public function getEnabledProviders()
    {
        return array_filter($this->providers, function (Providers\AbstractRemoteAuthProvider $provider) {
            return $provider->getEnabled();
        });
    }
    public function getEnabledProvidersHtmlCode($htmlTarget)
    {
        $codeSnippets = array();
        foreach ($this->providers as $provider) {
            if ($provider->getEnabled()) {
                $codeSnippets[] = $provider->getHtml($htmlTarget);
            }
        }
        return $codeSnippets;
    }
    public function getProviderByName($providerName)
    {
        if (!array_key_exists($providerName, $this->providers)) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Provider does not exist: " . $providerName);
        }
        return $this->providers[$providerName];
    }
    public function saveProviderContext(Providers\AbstractRemoteAuthProvider $provider, $context)
    {
        $payloads = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        if (!is_array($payloads)) {
            $payloads = array();
        }
        $payloads[$provider::NAME] = $context;
        \WHMCS\Session::set(static::AUTHN_CONTEXTS_VAR, $payloads);
    }
    public function retrieveProviderContext(Providers\AbstractRemoteAuthProvider $provider)
    {
        $contexts = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        if (is_array($contexts) && array_key_exists($provider::NAME, $contexts)) {
            return $contexts[$provider::NAME];
        }
        return null;
    }
    public function isPrelinkPerformed()
    {
        $contexts = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        return !empty($contexts);
    }
    public function generateRandomPassword()
    {
        return \Illuminate\Support\Str::random(24);
    }
    public function eraseProviderContext(Providers\AbstractRemoteAuthProvider $provider)
    {
        $payloads = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        if (isset($payloads[$provider::NAME])) {
            unset($payloads[$provider::NAME]);
        }
        \WHMCS\Session::set(static::AUTHN_CONTEXTS_VAR, $payloads);
    }
    public function linkRemoteAccounts()
    {
        $contexts = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        if (is_array($contexts)) {
            $providerNames = array_keys($contexts);
            foreach ($providerNames as $providerName) {
                try {
                    $provider = $this->getProviderByName($providerName);
                    if ($provider->getEnabled()) {
                        $provider->linkAccount($contexts[$providerName]);
                    }
                    unset($contexts[$providerName]);
                } catch (\Exception $e) {
                    logActivity("Failed to link remote account. " . $e->getMessage() . ". User ID: " . \WHMCS\Session::get("uid") . (\WHMCS\Session::get("cid") ? ", Contact ID: " . \WHMCS\Session::get("cid") : "") . ". Provider: " . $providerName . ", context: " . var_export($contexts[$providerName], true));
                }
            }
            if (!empty($contexts)) {
                \WHMCS\Session::set(static::AUTHN_CONTEXTS_VAR, $contexts);
            } else {
                \WHMCS\Session::delete(static::AUTHN_CONTEXTS_VAR);
            }
        }
    }
    public function getRegistrationFormData()
    {
        $formData = array();
        $contexts = \WHMCS\Session::get(static::AUTHN_CONTEXTS_VAR);
        if (is_array($contexts) && !empty($contexts)) {
            $providerNames = array_keys($contexts);
            $providerName = $providerNames[0];
            $provider = $this->getProviderByName($providerName);
            if ($provider->getEnabled()) {
                $formData = $provider->getRegistrationFormData($contexts[$providerName]);
            }
        }
        return $formData;
    }
    public function logAccountLinkCreation(AccountLink $accountLink)
    {
        $provider = $this->getProviderByName($accountLink->provider);
        logActivity("A " . $provider::FRIENDLY_NAME . " account belonging to " . $provider->getRemoteAccountName(json_decode($accountLink->metadata, true)) . " was linked with User ID: " . $accountLink->clientId . ($accountLink->contactId ? ", Contact ID: " . $accountLink->contactId : ""));
    }
    public function logAccountLinkDeletion(AccountLink $accountLink)
    {
        $provider = $this->getProviderByName($accountLink->provider);
        logActivity("A " . $provider::FRIENDLY_NAME . " account belonging to " . $provider->getRemoteAccountName(json_decode($accountLink->metadata, true)) . " was unlinked from User ID: " . $accountLink->clientId . ($accountLink->contactId ? ", Contact ID: " . $accountLink->contactId : ""));
    }
    public function logAccountLinkLogin(AccountLink $accountLink, $twofaPending = false)
    {
        $provider = $this->getProviderByName($accountLink->provider);
        logActivity("A " . $provider::FRIENDLY_NAME . " account belonging to " . $provider->getRemoteAccountName(json_decode($accountLink->metadata, true)) . ($twofaPending ? " was used as first login factor for" : " was used to log in as") . " User ID: " . $accountLink->clientId . ($accountLink->contactId ? ", Contact ID: " . $accountLink->contactId : ""));
    }
}

?>