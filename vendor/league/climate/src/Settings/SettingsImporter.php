<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Settings;

trait SettingsImporter
{
    /**
     * Dictates any settings that a class may need access to
     *
     * @return array
     */
    public function settings()
    {
        return [];
    }
    /**
     * Import the setting into the class
     *
     * @param \League\CLImate\Settings\SettingsInterface $setting
     */
    public function importSetting($setting)
    {
        $short_name = basename(str_replace('\\', '/', get_class($setting)));
        $method = 'importSetting' . $short_name;
        if (method_exists($this, $method)) {
            $this->{$method}($setting);
        }
    }
}

?>