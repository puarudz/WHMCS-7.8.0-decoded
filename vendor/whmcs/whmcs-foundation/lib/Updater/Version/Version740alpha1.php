<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version740alpha1 extends IncrementalVersion
{
    protected $updateActions = array("removeVarilogixModuleIfNotInUse", "addAdditionalccTLDs", "addApiRoleIfCredentialsExist");
    protected function removeVarilogixModuleIfNotInUse()
    {
        $isActive = \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", "varilogix_fraudcall")->count();
        if (!$isActive) {
            $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "fraud" . DIRECTORY_SEPARATOR . "varilogix_fraudcall";
        }
    }
    protected function addAdditionalccTLDs()
    {
        $rawTopLevelDomainsAndCategories = "{\n            \"com.br\":[\"ccTLD\"],\n            \"net.br\":[\"ccTLD\"],\n            \"co.za\":[\"ccTLD\"],\n            \"org.za\":[\"ccTLD\"],\n            \"web.za\":[\"ccTLD\"],\n            \"alt.za\":[\"ccTLD\"],\n            \"net.za\":[\"ccTLD\"]\n        }";
        return $this->addDomainsToCategories($rawTopLevelDomainsAndCategories);
    }
    public function getFeatureHighlights()
    {
        $highlights = array();
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Instant <span>Notifications</span>", "Get notified about events that matter to you with the new notification center.", null, "notifications.png", "<img src=\"images/whatsnew/hipchat-and-slack.png\" style=\"margin:0 auto;\">", "https://docs.whmcs.com/Notifications", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Improved <span>Phone UX</span>", "A better user experience for phone numbers", null, "phone-ux.png", "Featuring automatic country code prefixing and standardised formatting of phone number input.", "https://docs.whmcs.com/Phone_Numbers", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Improved <span>Collision Detection</span>", "Save time and improve efficiency", null, "ticket-collision-protection.png", "New and improved alerts help protect against multiple members of staff working on a ticket at the same time.", "https://docs.whmcs.com/Support_Tickets#Ticket_Collision_Detection", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Improved <span>Credit UX</span>", "Giving customers more control...", null, "credit.png", "Now customers can choose if they wish to auto apply their available credit to new orders they place.", "https://docs.whmcs.com/Credit/Prefunding", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("New <span>Translations</span>", "Localise your support departments", null, "translations.png", "Add localised translations for support department names and descriptions to make your support more accessible.", "https://docs.whmcs.com/Support_Departments", "Learn More");
        return $highlights;
    }
    protected function addApiRoleIfCredentialsExist()
    {
        $devices = \WHMCS\Authentication\Device::where("role_ids", "=", "")->get();
        if ($devices->count()) {
            $role = new \WHMCS\Api\Authorization\ApiRole();
            $role->role = "Compatibility Role - Auto Generated";
            $role->description = "Created by the WHMCS v7.4.0 update process. " . "You may modify or remove this role. " . "Please see the " . "<a href=\"https://docs.whmcs.com/API_Roles#compat\" target=\"_blank\">" . "API Roles documentation</a> for more information.";
            $role->allow(array_keys(\WHMCS\Api\V1\Catalog::get()->getActions()));
            $role->save();
            foreach ($devices as $device) {
                if (empty($device->role_ids)) {
                    $device->addRole($role);
                    $device->save();
                }
            }
        }
        return $this;
    }
}

?>