<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version770alpha1 extends IncrementalVersion
{
    protected $updateActions = array("addPrimaryKeysToTables", "migrateEUVatAddon", "createInvoiceDataTable", "correctSpamExpertsEmailTemplate", "migrateStorageConfigurations", "updateRecaptchaOptions", "createSslStatusTable", "createTransactionHistoryTable");
    private function normalizePrimaryKey($tableName)
    {
        $tableName = preg_replace("/[^a-z\\d_]/i", "", $tableName);
        $columns = \WHMCS\Database\Capsule::connection()->select("SHOW COLUMNS FROM `" . $tableName . "`");
        $pkColumn = current(array_filter($columns, function ($item) {
            return $item->Key === "PRI";
        }));
        if ($pkColumn) {
            logActivity(sprintf("Table \"%s\" had a primary key (\"%s\"), skipping PK creation.", $tableName, $pkColumn->Field));
        } else {
            $idColumn = current(array_filter($columns, function ($item) {
                return strcasecmp($item->Field, "id") === 0;
            }));
            \WHMCS\Database\Capsule::schema()->table($tableName, function (\Illuminate\Database\Schema\Blueprint $table) use($idColumn) {
                if ($idColumn) {
                    $table->primary("id");
                } else {
                    $table->integer("id", true)->first();
                }
            });
            if ($idColumn) {
                $actionPerformedString = "Made existing \"id\" column a primary key in";
            } else {
                $actionPerformedString = "Added an \"id\" primary key to";
            }
            logActivity(sprintf("%s table \"%s\".", $actionPerformedString, $tableName));
        }
    }
    protected function addPrimaryKeysToTables()
    {
        $tablesToCheck = array("mod_invoicedata", "tbladminperms", "tblaffiliates", "tblconfiguration", "tblknowledgebaselinks", "tbloauthserver_access_token_scopes", "tbloauthserver_authcode_scopes", "tbloauthserver_client_scopes", "tbloauthserver_user_authz_scopes", "tblpaymentgateways", "tblproductconfiglinks", "tblservergroupsrel");
        foreach ($tablesToCheck as $tableName) {
            try {
                $this->normalizePrimaryKey($tableName);
            } catch (\Exception $e) {
                logActivity(sprintf("Error adding primary key to %s table: %s", $tableName, $e->getMessage()));
            }
        }
        try {
            $columns = \WHMCS\Database\Capsule::connection()->select("SHOW COLUMNS FROM `tblaffiliates`");
            $pkColumn = current(array_filter($columns, function ($item) {
                return $item->Key === "PRI" && $item->Field === "id";
            }));
            if ($pkColumn) {
                $indices = \WHMCS\Database\Capsule::connection()->select("SHOW INDEX FROM `tblaffiliates`");
                $extraIndex = current(array_filter($indices, function ($item) {
                    return $item->Key_name === "affiliateid" && $item->Column_name === "id";
                }));
                if ($extraIndex) {
                    \WHMCS\Database\Capsule::connection()->getPdo()->query("ALTER TABLE `tblaffiliates` DROP INDEX `" . $extraIndex->Key_name . "`");
                }
            }
        } catch (\Exception $e) {
            logActivity("Failed to analyze/drop redundant index: tblaffiliates.affiliateid(id): " . $e->getMessage());
        }
        return $this;
    }
    protected function migrateEUVatAddon()
    {
        $oldFieldToNewField = array("enablevalidation" => "TaxEUTaxValidation", "vatcustomfield" => "TaxVatCustomFieldId", "homecountry" => "TaxEUHomeCountry", "taxexempt" => "TaxEUTaxExempt", "notaxexempthome" => "TaxEUHomeCountryNoExempt", "enablecustominvoicenum" => "TaxCustomInvoiceNumbering", "custominvoicenumber" => "TaxNextCustomInvoiceNumber", "enableinvoicedatepayment" => "TaxSetInvoiceDateOnPayment", "custominvoicenumautoreset" => "TaxAutoResetNumbering", "sequentialpaidautoreset" => "TaxAutoResetPaidNumbering", "custominvoicenumformat" => "TaxCustomInvoiceNumberFormat");
        $existingConfig = \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "eu_vat")->get();
        if ($existingConfig) {
            foreach ($existingConfig as $config) {
                switch ($config->setting) {
                    case "enablevalidation":
                        \WHMCS\Config\Setting::setValue($oldFieldToNewField[$config->setting], (bool) $config->value == "on");
                        if ((bool) $config->value == "on") {
                            \WHMCS\Config\Setting::setValue("TaxVATEnabled", true);
                        }
                        break;
                    case "taxexempt":
                    case "notaxexempthome":
                    case "enablecustominvoicenum":
                    case "enableinvoicedatepayment":
                        \WHMCS\Config\Setting::setValue($oldFieldToNewField[$config->setting], (bool) $config->value == "on");
                        break;
                    case "vatcustomfield":
                        \WHMCS\Config\Setting::setValue($oldFieldToNewField[$config->setting], (int) \WHMCS\Database\Capsule::table("tblcustomfields")->where("fieldname", $config->value)->value("id"));
                        break;
                    case "custominvoicenumber":
                        \WHMCS\Config\Setting::setValue($oldFieldToNewField[$config->setting], (int) $config->value);
                        break;
                    case "homecountry":
                    case "custominvoicenumautoreset":
                    case "sequentialpaidautoreset":
                    case "custominvoicenumformat":
                        \WHMCS\Config\Setting::setValue($oldFieldToNewField[$config->setting], $config->value);
                        break;
                }
            }
            \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "eu_vat")->delete();
            $activeAddons = \WHMCS\Config\Setting::getValue("ActiveAddonModules");
            $activeAddons = explode(",", $activeAddons);
            $activeAddons = array_flip($activeAddons);
            unset($activeAddons["eu_vat"]);
            $activeAddons = array_flip($activeAddons);
            \WHMCS\Config\Setting::setValue("ActiveAddonModules", implode(",", array_values($activeAddons)));
            $addonPermissions = safe_unserialize(\WHMCS\Config\Setting::getValue("AddonModulesPerms"));
            if (is_array($addonPermissions)) {
                foreach ($addonPermissions as &$perm) {
                    unset($perm["eu_vat"]);
                }
                unset($perm);
                \WHMCS\Config\Setting::setValue("AddonModulesPerms", safe_serialize($addonPermissions));
            }
        }
        (new \WHMCS\Module\LegacyModuleCleanup())->removeModulesIfInstalledAndUnused(array("addons" => array("eu_vat")));
        return $this;
    }
    protected function createInvoiceDataTable()
    {
        (new \WHMCS\Billing\Invoice\Data())->createTable();
        return $this;
    }
    protected function correctSpamExpertsEmailTemplate()
    {
        $emailTemplates = \WHMCS\Mail\Template::where("type", "product")->where("name", "SpamExperts Welcome Email")->get();
        foreach ($emailTemplates as $emailTemplate) {
            $search = array("{foreach \$required_mx_records as \$mx_host => \$mx_priority}", "{foreach \$required_mx_records as \$mx_host =&gt; \$mx_priority}");
            $replace = "{foreach key=mx_host item=mx_priority from=\$required_mx_records}";
            $updatedMessage = str_replace($search, $replace, $emailTemplate->message);
            if ($updatedMessage != $emailTemplate->message) {
                $emailTemplate->message = $updatedMessage;
                $emailTemplate->save();
            }
        }
        return $this;
    }
    private function getLocalStorageConfigurationForDir($localPath)
    {
        $storageProvider = new \WHMCS\File\Provider\LocalStorageProvider();
        $storageProvider->setLocalPath($localPath);
        $storageConfiguration = $storageProvider->exportConfiguration();
        $existingConfiguration = \WHMCS\File\Configuration\StorageConfiguration::where("name", $storageConfiguration->name)->first();
        if ($existingConfiguration) {
            $existingStorageProvider = $existingConfiguration->createStorageProvider();
            if ($existingStorageProvider instanceof \WHMCS\File\Provider\LocalStorageProvider && $storageProvider->getLocalPath() === $existingStorageProvider->getLocalPath()) {
                $storageConfiguration = $existingConfiguration;
            }
        }
        if (!$storageConfiguration->exists) {
            $storageConfiguration->save();
        }
        return $storageConfiguration;
    }
    public function getApplicationConfig()
    {
        return \DI::make("config");
    }
    public function migrateStorageConfigurations()
    {
        $config = $this->getApplicationConfig();
        $downloadsDir = $config->downloads_dir ?: ROOTDIR . DIRECTORY_SEPARATOR . \WHMCS\Config\Application::DEFAULT_DOWNLOADS_FOLDER;
        $attachmentsDir = $config->getAbsoluteAttachmentsPath();
        $downloadsConfiguration = $this->getLocalStorageConfigurationForDir($downloadsDir);
        $attachmentsConfiguration = $this->getLocalStorageConfigurationForDir($attachmentsDir);
        $pmFilesConfiguration = $this->getLocalStorageConfigurationForDir(rtrim($attachmentsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "projects");
        $assetTypeLocalPaths = array(\WHMCS\File\FileAsset::TYPE_CLIENT_FILES => $attachmentsConfiguration, \WHMCS\File\FileAsset::TYPE_DOWNLOADS => $downloadsConfiguration, \WHMCS\File\FileAsset::TYPE_EMAIL_ATTACHMENTS => $attachmentsConfiguration, \WHMCS\File\FileAsset::TYPE_EMAIL_TEMPLATE_ATTACHMENTS => $downloadsConfiguration, \WHMCS\File\FileAsset::TYPE_PM_FILES => $pmFilesConfiguration, \WHMCS\File\FileAsset::TYPE_TICKET_ATTACHMENTS => $attachmentsConfiguration);
        foreach ($assetTypeLocalPaths as $assetType => $storageConfiguration) {
            $assetSetting = new \WHMCS\File\Configuration\FileAssetSetting();
            $assetSetting->asset_type = $assetType;
            $assetSetting->storageconfiguration_id = $storageConfiguration->id;
            $assetSetting->save();
        }
        \WHMCS\Database\Capsule::table("tbladminperms")->insert(array("roleid" => 1, "permid" => 147));
        return $this;
    }
    protected function updateRecaptchaOptions()
    {
        $invisibleKey = \WHMCS\Config\Setting::getValue("ReCAPTCHAInvisible");
        if ($invisibleKey) {
            \WHMCS\Config\Setting::setValue("CaptchaType", "invisible");
        }
        \WHMCS\Config\Setting::where("setting", "ReCAPTCHAInvisible")->delete();
        $recaptchaForms = \WHMCS\Config\Setting::getValue("ReCAPTCHAForms");
        if ($recaptchaForms) {
            \WHMCS\Config\Setting::setValue("CaptchaForms", $recaptchaForms);
        }
        \WHMCS\Config\Setting::whereIn("setting", array("ReCAPTCHAInvisible", "ReCAPTCHAForms"))->delete();
        return $this;
    }
    protected function createSslStatusTable()
    {
        (new \WHMCS\Domain\Ssl\Status())->createTable();
        return $this;
    }
    protected function createTransactionHistoryTable()
    {
        (new \WHMCS\Billing\Payment\Transaction\History())->createTable();
        return $this;
    }
}

?>