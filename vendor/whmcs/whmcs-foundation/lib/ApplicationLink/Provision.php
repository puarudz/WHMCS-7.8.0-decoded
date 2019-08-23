<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink;

class Provision implements \WHMCS\Scheduling\Contract\JobInterface
{
    use \WHMCS\Scheduling\Jobs\JobTrait;
    public function sync($module)
    {
        $moduleInterface = new \WHMCS\Module\Server();
        $moduleInterface->load($module);
        if ($moduleInterface->isApplicationLinkSupported() && $moduleInterface->isApplicationLinkingEnabled()) {
            $moduleInterface->syncApplicationLinksConfigChange();
        }
    }
    public function cleanup($module)
    {
        $moduleInterface = new \WHMCS\Module\Server();
        $moduleInterface->load($module);
        if ($moduleInterface->isApplicationLinkSupported() && $moduleInterface->isApplicationLinkingEnabled()) {
            $moduleInterface->cleanupOldApplicationLinks();
        }
    }
    public function cloneScopeLink($applinkId, $oldScopeName, $newScopeName)
    {
        $appLink = ApplicationLink::find($applinkId);
        if (!is_null($appLink) && $appLink->isEnabled) {
            $stdScopes = (new Scope())->getStandardScopes();
            $newScopeDefinition = $stdScopes[$newScopeName];
            if (!is_array($newScopeDefinition)) {
                $newScopeDefinition = array("description" => "");
            }
            $newLink = Links::firstOrNew(array("applink_id" => $appLink->id, "scope" => $newScopeName));
            $oldLink = $appLink->links()->where("scope", "=", $oldScopeName)->first();
            if ($oldLink) {
                $newLink->displayLabel = $oldLink->displayLabel;
                $newLink->isEnabled = 1;
                $newLink->order = $oldLink->order;
            } else {
                $newLink->displayLabel = $newScopeDefinition["description"];
                $newLink->isEnabled = 1;
                $newLink->order = 0;
            }
            $newLink->save();
        }
    }
}

?>