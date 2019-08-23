<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Server;

class ServerController
{
    public function refreshRemoteData(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $server = \WHMCS\Product\Server::findOrFail($request->get("id"));
            $remoteData = \WHMCS\Product\Server\Remote::firstOrNew(array("server_id" => $server->id));
            $serverInterface = $server->getModuleInterface();
            $serverDetails = $serverInterface->getServerParams($server);
            $serverCounts = $serverInterface->call("GetUserCount", $serverDetails);
            $metaData = $remoteData->metaData;
            if ($serverCounts !== \WHMCS\Module\Server::FUNCTIONDOESNTEXIST) {
                if (array_key_exists("error", $serverCounts) && $serverCounts["error"]) {
                    throw new \WHMCS\Exception\Module\NotServicable($serverCounts["error"]);
                }
                $remoteData->numAccounts = $serverCounts["totalAccounts"];
                $metaData["ownedAccounts"] = $serverCounts["ownedAccounts"];
            }
            $remoteMetaData = $serverInterface->call("GetRemoteMetaData", $serverDetails);
            if ($remoteMetaData !== \WHMCS\Module\Server::FUNCTIONDOESNTEXIST) {
                if (array_key_exists("error", $remoteMetaData) && $remoteMetaData["error"]) {
                    throw new \WHMCS\Exception\Module\NotServicable($serverCounts["error"]);
                }
                $metaData = array_merge($metaData, $remoteMetaData);
            }
            $remoteData->metaData = $metaData;
            $remoteData->save();
            $remoteMetaDataOutput = $serverInterface->call("RenderRemoteMetaData", array("remoteData" => $remoteData));
            if ($remoteMetaDataOutput == \WHMCS\Module\Server::FUNCTIONDOESNTEXIST) {
                $remoteMetaDataOutput = "";
            } else {
                $remoteMetaDataOutput .= "<br>" . \AdminLang::trans("global.lastUpdated") . ": Just now";
            }
            return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "metaData" => $remoteMetaDataOutput, "numAccounts" => $remoteData->numAccounts));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("success" => false, "error" => array("title" => \AdminLang::trans("global.error"), "message" => $e->getMessage())));
        }
    }
}

?>