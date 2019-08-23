<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Storage;

class StorageController
{
    const MAX_FAILURE_REASON_LENGTH = 400;
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper())->setTitle(\AdminLang::trans("setup.storage"))->setSidebarName("config")->setFavicon("configother")->setHelpLink("Storage Settings");
        $assetSettings = array();
        foreach (\WHMCS\File\Configuration\FileAssetSetting::with(array("configuration", "migrateToConfiguration"))->get() as $assetSetting) {
            if ($assetSetting->migrateToConfiguration) {
                $migrationProgress = \WHMCS\File\Migration\FileAssetMigrationProgress::forAssetType($assetSetting->asset_type)->first();
                if ($migrationProgress) {
                    if (0 < $migrationProgress->num_objects_total) {
                        $progress = round($migrationProgress->num_objects_migrated * 100 / $migrationProgress->num_objects_total);
                    } else {
                        $progress = null;
                    }
                    $migration = array("active" => $migrationProgress->active, "progress" => $progress, "last_failure" => $this->truncateString($migrationProgress->last_failure_reason, self::MAX_FAILURE_REASON_LENGTH));
                } else {
                    $migration = array("active" => true, "progress" => 0, "last_failure" => "");
                }
            } else {
                $migration = null;
            }
            $assetSettings[] = array_merge($assetSetting->toArray(), array("can_migrate" => \WHMCS\File\FileAsset::canMigrate($assetSetting->asset_type), "migration" => $migration));
        }
        $storageConfigurations = array();
        $action = $request->get("action");
        foreach (\WHMCS\File\Configuration\StorageConfiguration::all() as $config) {
            $provider = $config->createStorageProvider();
            $errorMessage = "";
            if ($config->last_error) {
                try {
                    $errorTimestamp = (new \WHMCS\Carbon($config->last_error["timestamp"]))->toAdminDateTimeFormat();
                } catch (\Exception $e) {
                    $errorTimestamp = \WHMCS\Carbon::now()->toAdminDateTimeFormat();
                }
                $errorMessage = $errorTimestamp . "\n" . $config->last_error["message"];
                $action = "showConfigurations";
            }
            $storageConfigurations[] = array_merge($config->toArray(), array("provider_name" => $provider->getName(), "icon" => $provider->getIcon(), "config_summary" => $provider->getConfigSummaryHtml(), "error_message" => \WHMCS\Input\Sanitize::encode($errorMessage)));
        }
        $providers = array_map(function ($providerClass) {
            return $providerClass::getName();
        }, \WHMCS\File\Provider\StorageProviderFactory::getProviderClasses());
        $params = array("assetSettings" => $assetSettings, "storageConfigurations" => $storageConfigurations, "providers" => $providers, "csrfToken" => generate_token("plain"), "action" => $action);
        $content = view("admin.setup.storage.index", $params);
        $view->setBodyContent($content);
        return $view;
    }
    public function editConfiguration(\WHMCS\Http\Message\ServerRequest $request, $errorMsg = NULL, $duplicate = false)
    {
        require_once ROOTDIR . "/includes/modulefunctions.php";
        $configId = (int) $request->get("id");
        $provider = null;
        $inUse = false;
        $settings = $request->get("settings") ?: array();
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                $settings[$key] = \WHMCS\Input\Sanitize::decode($value);
            }
        }
        if ($configId) {
            $config = \WHMCS\File\Configuration\StorageConfiguration::find($configId);
            if ($config) {
                $provider = $config->createStorageProvider();
                $configFields = $config->settings;
                foreach ($provider->getAccessCredentialFieldNames() as $fieldName) {
                    if (isset($configFields[$fieldName])) {
                        $configFields[$fieldName] = str_repeat("*", strlen($configFields[$fieldName]));
                    }
                }
                $settings = array_merge($configFields, $settings);
                $inUse = \WHMCS\File\Configuration\FileAssetSetting::usingConfiguration($configId)->exists();
            }
        } else {
            $providerType = $request->get("provider");
            $provider = \WHMCS\File\Provider\StorageProviderFactory::createProvider($providerType);
        }
        if (!$provider) {
            throw new \WHMCS\Exception\Storage\StorageException("Invalid provider or configuration ID");
        }
        $output = view("admin.setup.storage.provider-config", array("id" => !$duplicate ? $configId : 0, "duplicate_configuration_id" => $duplicate ? $configId : 0, "provider" => $provider, "inUse" => $inUse && !$duplicate, "settings" => $settings, "errorMsg" => $errorMsg));
        return new \WHMCS\Http\Message\JsonResponse(array("body" => $output));
    }
    public function duplicateConfiguration(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->editConfiguration($request, null, true);
    }
    public function saveConfiguration(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $providerType = $request->get("provider");
            $provider = \WHMCS\File\Provider\StorageProviderFactory::createProvider($providerType);
            if (!$provider) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid provider");
            }
            $existingConfigurationId = $request->get("id");
            $existingConfiguration = $existingConfigurationId ? \WHMCS\File\Configuration\StorageConfiguration::findOrFail($existingConfigurationId) : null;
            $duplicatedConfigurationId = $request->get("duplicate_configuration_id");
            $duplicatedConfiguration = $duplicatedConfigurationId ? \WHMCS\File\Configuration\StorageConfiguration::findOrFail($duplicatedConfigurationId) : null;
            $requestSettings = $request->get("settings");
            $settings = array();
            if (is_array($requestSettings)) {
                foreach ($requestSettings as $key => $value) {
                    if (count_chars($value, 3) === "*") {
                        continue;
                    }
                    $settings[$key] = \WHMCS\Input\Sanitize::decode($value);
                }
            }
            if ($existingConfiguration) {
                $settings = array_merge($existingConfiguration->settings, $settings);
            }
            if ($duplicatedConfiguration) {
                $settings = array_merge($duplicatedConfiguration->settings, $settings);
            }
            if (!empty($settings)) {
                $provider->applyConfiguration($settings);
                $provider->testConfiguration();
                $provider->exportConfiguration($existingConfiguration)->testForDuplicate()->save();
            }
            return new \WHMCS\Http\Message\JsonResponse(array("dismiss" => true, "reloadPage" => routePath("admin-setup-storage-index", "confirmSave")));
        } catch (\WHMCS\Exception\Storage\SameStorageConfigurationExistsException $e) {
            return $this->editConfiguration($request, \AdminLang::trans("storage.sameConfigAlreadyExists"));
        } catch (\WHMCS\Exception\Storage\StorageConfigurationException $e) {
            return $this->editConfiguration($request, "<ul>" . implode("", array_map(function ($item) {
                return "<li>" . $item . "</li>";
            }, array_values($e->getFields()))) . "</ul>");
        } catch (\Exception $e) {
            return $this->editConfiguration($request, \AdminLang::trans("storage.configSaveError") . " " . " " . \AdminLang::trans("global.error") . ": " . $e->getMessage());
        }
    }
    public function testConfiguration(\WHMCS\Http\Message\ServerRequest $request)
    {
        $configId = (int) $request->get("id");
        try {
            $config = \WHMCS\File\Configuration\StorageConfiguration::find($configId);
            if (!$config) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid configuration");
            }
            $provider = $config->createStorageProvider();
            $provider->testConfiguration();
            $config->last_error = null;
            $config->save();
            return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => \AdminLang::trans("global.success"), "successMsg" => \AdminLang::trans("storage.configTestOk")));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => \AdminLang::trans("storage.configTestError") . " " . \WHMCS\Input\Sanitize::encode($e->getMessage())));
        }
    }
    public function deleteConfiguration(\WHMCS\Http\Message\ServerRequest $request)
    {
        $configId = (int) $request->get("id");
        try {
            if (\WHMCS\File\Configuration\FileAssetSetting::usingConfiguration($configId)->exists()) {
                throw new \WHMCS\Exception\Storage\StorageException(\AdminLang::trans("storage.configInUse"));
            }
            $config = \WHMCS\File\Configuration\StorageConfiguration::find($configId);
            if (!$config) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid configuration");
            }
            $config->delete();
            return new \WHMCS\Http\Message\JsonResponse(array("successMsg" => \AdminLang::trans("storage.config.confirmDelete")));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => \AdminLang::trans("storage.configDeleteError") . " " . $e->getMessage()));
        }
    }
    public function dismissError(\WHMCS\Http\Message\ServerRequest $request)
    {
        $configId = (int) $request->get("id");
        try {
            $config = \WHMCS\File\Configuration\StorageConfiguration::find($configId);
            if (!$config) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid configuration");
            }
            $config->last_error = null;
            $config->save();
            return new \WHMCS\Http\Message\JsonResponse(array("successMsg" => \AdminLang::trans("global.success")));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => \AdminLang::trans("global.error")));
        }
    }
    public function cancelMigration(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $assetType = $request->get("asset_type");
            $assetSetting = \WHMCS\File\Configuration\FileAssetSetting::forAssetType($assetType)->first();
            if (!$assetSetting) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid asset type: " . $assetType);
            }
            $migrationInProgress = \WHMCS\File\Migration\FileAssetMigrationProgress::forAssetType($assetType)->first();
            if ($migrationInProgress) {
                $migrationInProgress->delete();
            }
            $assetSetting->migratetoconfiguration_id = null;
            $assetSetting->save();
            return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => \AdminLang::trans("global.success"), "successMsg" => \AdminLang::trans("storage.migration.cancelOk")));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => $e->getMessage()));
        }
    }
    public function switchAssetStorage(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $assetType = $request->get("asset_type");
            $assetSetting = \WHMCS\File\Configuration\FileAssetSetting::forAssetType($assetType)->first();
            if (!$assetSetting) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid asset type: " . $assetType);
            }
            if ($assetSetting->migrateToConfiguration) {
                throw new \WHMCS\Exception\Storage\StorageException("This asset type is currently under migration and cannot be switched to a new location.");
            }
            $targetConfiguration = \WHMCS\File\Configuration\StorageConfiguration::find((int) $request->get("configuration_id"));
            if (!$targetConfiguration) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid target configuration");
            }
            if ($assetSetting->configuration->id === $targetConfiguration->id) {
                throw new \WHMCS\Exception\Storage\StorageException("This asset type is already configured to use specified storage.");
            }
            $targetConfiguration->assetSettings()->save($assetSetting);
            return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => \AdminLang::trans("global.success"), "successMsg" => \AdminLang::trans("storage.migration.switchOk")));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => $e->getMessage()));
        }
    }
    public function startMigration(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $assetType = $request->get("asset_type");
            $assetSetting = \WHMCS\File\Configuration\FileAssetSetting::forAssetType($assetType)->first();
            if (!$assetSetting) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid asset type: " . $assetType);
            }
            if ($assetSetting->migrateToConfiguration) {
                throw new \WHMCS\Exception\Storage\StorageException("This asset type is already being migrated.");
            }
            $newConfiguration = \WHMCS\File\Configuration\StorageConfiguration::find((int) $request->get("configuration_id"));
            if (!$newConfiguration) {
                throw new \WHMCS\Exception\Storage\StorageException("Invalid target configuration");
            }
            if ($assetSetting->configuration->id === $newConfiguration->id) {
                throw new \WHMCS\Exception\Storage\StorageException("This asset type is already configured to use specified storage.");
            }
            $newConfiguration->assetSettingsMigratedTo()->save($assetSetting);
            $assetSetting->load("migrateToConfiguration");
            $migrationProcessor = \WHMCS\File\Migration\Processor\MigrationProcessorFactory::createForFileAsset($assetSetting, 60);
            if ($migrationProcessor->migrate()) {
                $response = array("migrationCompleted" => true);
            } else {
                \WHMCS\File\Migration\MigrationJob::queue();
                $migrationProgress = $migrationProcessor->getMigrationProgress();
                $percentDone = 0 < $migrationProgress->num_objects_total ? round($migrationProgress->num_objects_migrated * 100 / $migrationProgress->num_objects_total) : 0;
                $response = array("migrationInProgress" => true, "progress" => $percentDone);
                if (0 < $migrationProgress->num_failures) {
                    $response["failureReason"] = $this->truncateString($migrationProgress->last_failure_reason, self::MAX_FAILURE_REASON_LENGTH);
                }
            }
            return new \WHMCS\Http\Message\JsonResponse($response);
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => \AdminLang::trans("global.error"), "errorMsg" => $e->getMessage()));
        }
    }
    private function truncateString($content, $maxLength)
    {
        if ($maxLength < strlen($content)) {
            $content = substr($content, 0, $maxLength) . "...";
        }
        return $content;
    }
}

?>