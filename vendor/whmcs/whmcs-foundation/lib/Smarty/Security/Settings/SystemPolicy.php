<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Smarty\Security\Settings;

class SystemPolicy extends BasePolicy
{
    public function __construct(array $data)
    {
        $data = $this->mergeSettingsAndAdminValues($data);
        parent::__construct($data);
    }
    protected function mergeSettingsAndAdminValues(array $data)
    {
        $defaults = parent::getDefaultPolicySettings();
        $adminAllowsPhpTag = (bool) \WHMCS\Config\Setting::getValue("AllowSmartyPhpTags");
        if (!$adminAllowsPhpTag) {
            return $data;
        }
        if (!isset($data["disabled_tags"]) || !is_array($data["disabled_tags"])) {
            $data["disabled_tags"] = $defaults["disabled_tags"];
            if (in_array(\WHMCS\Smarty\Security\Policy::TAG_COMPILER_PHP, $data["disabled_tags"])) {
                foreach (array_keys($data["disabled_tags"], \WHMCS\Smarty\Security\Policy::TAG_COMPILER_PHP) as $key) {
                    unset($data["disabled_tags"][$key]);
                }
            }
            return $data;
        } else {
            return $data;
        }
    }
}

?>