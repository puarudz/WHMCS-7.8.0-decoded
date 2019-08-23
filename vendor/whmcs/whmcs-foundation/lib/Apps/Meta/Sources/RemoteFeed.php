<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Meta\Sources;

class RemoteFeed
{
    public function getAdditionalApps()
    {
        $apps = array();
        foreach ((new \WHMCS\Apps\Feed())->additionalApps() as $slug => $data) {
            $apps[$slug] = $this->parseJson($data);
        }
        return $apps;
    }
    public function getAppByModuleName($moduleType, $moduleName)
    {
        $slug = $moduleType . "." . $moduleName;
        $apps = (new \WHMCS\Apps\Feed())->apps();
        $additionalApps = (new \WHMCS\Apps\Feed())->additionalApps();
        $metaData = isset($apps[$slug]) ? $apps[$slug] : null;
        if (is_null($metaData)) {
            $metaData = isset($additionalApps[$slug]) ? $additionalApps[$slug] : null;
        }
        return $this->parseJson($metaData);
    }
    protected function getSchemaMajorVersion($metaData)
    {
        if (isset($metaData["schema"])) {
            $versionParts = explode(".", $metaData["schema"]);
            return $versionParts[0];
        }
        throw new \WHMCS\Exception("Schema not defined.");
    }
    public function parseJson($metaData)
    {
        $majorVersion = $this->getSchemaMajorVersion($metaData);
        $schemaClass = "\\WHMCS\\Apps\\Meta\\Schema\\Version" . (int) $majorVersion . "\\Remote";
        if (class_exists($schemaClass)) {
            return new $schemaClass($metaData);
        }
        throw new \WHMCS\Exception("Invalid schema version.");
    }
}

?>