<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getCustomFields($type, $relid, $relid2, $admin = "", $order = "", $ordervalues = "", $hidepw = "")
{
    global $_LANG;
    $customfields = array();
    if (is_null($relid)) {
        $relid = 0;
    }
    if (is_null($relid2)) {
        $relid2 = 0;
    }
    static $customFieldCache = NULL;
    if (!$customFieldCache) {
        $customFieldCache = array();
    }
    if (isset($customFieldCache[$type][$relid])) {
        $customFieldsData = $customFieldCache[$type][$relid];
    } else {
        $customFieldsData = WHMCS\CustomField::where("type", $type)->where("relid", $relid)->get();
        $customFieldCache[$type][$relid] = $customFieldsData;
    }
    if (!$admin) {
        $customFieldsData = $customFieldsData->where("adminonly", "");
    }
    if ($order) {
        $customFieldsData = $customFieldsData->where("showorder", "on");
    }
    foreach ($customFieldsData->toArray() as $data) {
        $id = $data["id"];
        $fieldname = $admin ? $data["fieldname"] : WHMCS\CustomField::getFieldName($id, $data["fieldname"]);
        if (strpos($fieldname, "|")) {
            $fieldname = explode("|", $fieldname);
            $fieldname = trim($fieldname[1]);
        }
        $fieldtype = $data["fieldtype"];
        $description = $admin ? $data["description"] : WHMCS\CustomField::getDescription($id, $data["description"]);
        $fieldoptions = $data["fieldoptions"];
        $required = $data["required"];
        $adminonly = $data["adminonly"];
        $customfieldval = is_array($ordervalues) && array_key_exists($id, $ordervalues) ? $ordervalues[$id] : "";
        $input = "";
        if ($relid2) {
            $customFieldValue = WHMCS\CustomField\CustomFieldValue::firstOrNew(array("fieldid" => $id, "relid" => $relid2));
            if ($customFieldValue->exists) {
                $customfieldval = $customFieldValue->value;
            }
            $fieldloadhooks = run_hook("CustomFieldLoad", array("fieldid" => $id, "relid" => $relid2, "value" => $customfieldval));
            if (0 < count($fieldloadhooks)) {
                $fieldloadhookslast = array_pop($fieldloadhooks);
                if (array_key_exists("value", $fieldloadhookslast)) {
                    $customfieldval = $fieldloadhookslast["value"];
                }
            }
        }
        $rawvalue = $customfieldval;
        $customfieldval = WHMCS\Input\Sanitize::makeSafeForOutput($customfieldval);
        if ($required == "on") {
            $required = "*";
        }
        if ($fieldtype == "text" || $fieldtype == "password" && $admin) {
            $input = "<input type=\"text\" name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\" value=\"" . $customfieldval . "\" size=\"30\" class=\"form-control\" />";
        } else {
            if ($fieldtype == "link") {
                $webaddr = trim($customfieldval);
                if (substr($webaddr, 0, 4) == "www.") {
                    $webaddr = "http://" . $webaddr;
                }
                $input = "<input type=\"text\" name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\" value=\"" . $customfieldval . "\" size=\"40\" class=\"form-control\" /> " . ($customfieldval ? "<a href=\"" . $webaddr . "\" target=\"_blank\">www</a>" : "");
                $customfieldval = "<a href=\"" . $webaddr . "\" target=\"_blank\">" . $customfieldval . "</a>";
            } else {
                if ($fieldtype == "password") {
                    $input = "<input type=\"password\" name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\" value=\"" . $customfieldval . "\" size=\"30\" class=\"form-control\" />";
                    if ($hidepw) {
                        $pwlen = strlen($customfieldval);
                        $customfieldval = "";
                        for ($i = 1; $i <= $pwlen; $i++) {
                            $customfieldval .= "*";
                        }
                    }
                } else {
                    if ($fieldtype == "textarea") {
                        $input = "<textarea name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\" rows=\"3\" class=\"form-control\">" . $customfieldval . "</textarea>";
                    } else {
                        if ($fieldtype == "dropdown") {
                            $input = "<select name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\" class=\"form-control\">";
                            if (!$required) {
                                $input .= "<option value=\"\">" . $_LANG["none"] . "</option>";
                            }
                            $fieldoptions = explode(",", $fieldoptions);
                            foreach ($fieldoptions as $optionvalue) {
                                $input .= "<option value=\"" . $optionvalue . "\"";
                                if ($customfieldval == $optionvalue) {
                                    $input .= " selected";
                                }
                                if (strpos($optionvalue, "|")) {
                                    $optionvalue = explode("|", $optionvalue);
                                    $optionvalue = trim($optionvalue[1]);
                                }
                                $input .= ">" . $optionvalue . "</option>";
                            }
                            $input .= "</select>";
                        } else {
                            if ($fieldtype == "tickbox") {
                                $input = "<input type=\"checkbox\" name=\"customfield[" . $id . "]\" id=\"customfield" . $id . "\"";
                                if ($customfieldval == "on") {
                                    $input .= " checked";
                                }
                                $input .= " />";
                            }
                        }
                    }
                }
            }
        }
        if ($fieldtype != "link" && strpos($customfieldval, "|")) {
            $customfieldval = explode("|", $customfieldval);
            $customfieldval = trim($customfieldval[1]);
        }
        $customfields[] = array("id" => $id, "textid" => preg_replace("/[^0-9a-z]/i", "", strtolower($fieldname)), "name" => $fieldname, "description" => $description, "type" => $fieldtype, "input" => $input, "value" => $customfieldval, "rawvalue" => $rawvalue, "required" => $required, "adminonly" => $adminonly);
    }
    return $customfields;
}
function saveCustomFields($relid, $customfields, $type = "", $isAdmin = false)
{
    if (is_array($customfields)) {
        foreach ($customfields as $id => $value) {
            if (is_null($value)) {
                $value = "";
            }
            if (!is_int($id) && !empty($id)) {
                $stmt = WHMCS\Database\Capsule::table("tblcustomfields")->where("tblcustomfields.fieldname", "=", $id);
                if ($type) {
                    $stmt = $stmt->where("tblcustomfields.type", "=", $type);
                }
                if ($type == "product") {
                    $stmt = $stmt->join("tblproducts", "tblproducts.id", "=", "tblcustomfields.relid")->join("tblhosting", "tblhosting.packageid", "=", "tblproducts.id")->where("tblhosting.id", "=", $relid);
                }
                $fieldIds = $stmt->get(array("tblcustomfields.id"));
                if (count($fieldIds) != 1) {
                    continue;
                }
                $id = $fieldIds[0]->id;
            }
            $where = array();
            $where["id"] = $id;
            if ($type) {
                $where["type"] = $type;
            }
            if (!$isAdmin) {
                $where["adminonly"] = "";
            }
            if (!get_query_val("tblcustomfields", "id", $where)) {
                continue;
            }
            $fieldsavehooks = run_hook("CustomFieldSave", array("fieldid" => $id, "relid" => $relid, "value" => $value));
            if (0 < count($fieldsavehooks)) {
                $fieldsavehookslast = array_pop($fieldsavehooks);
                if (array_key_exists("value", $fieldsavehookslast)) {
                    $value = $fieldsavehookslast["value"];
                }
            }
            $customFieldValue = WHMCS\CustomField\CustomFieldValue::firstOrNew(array("fieldid" => $id, "relid" => $relid));
            $customFieldValue->value = $value;
            $customFieldValue->save();
        }
    }
}
function copyCustomFieldValues($itemType, $fromItemId, $toItemId)
{
    if ($fromItemId === $toItemId) {
        return false;
    }
    switch ($itemType) {
        case "product":
            $sourceFieldRelId = WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $fromItemId)->value("packageid");
            $destFieldRelId = WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $toItemId)->value("packageid");
            break;
        case "support":
            $sourceFieldRelId = WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $fromItemId)->value("did");
            $destFieldRelId = WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $toItemId)->value("did");
            break;
        default:
            return false;
    }
    if (!$sourceFieldRelId || !$destFieldRelId) {
        return false;
    }
    $sourceCustomFields = array();
    foreach (getcustomfields($itemType, $sourceFieldRelId, $fromItemId, true) as $field) {
        $sourceCustomFields[$field["name"]] = $field;
    }
    $destCustomFields = array();
    foreach (getcustomfields($itemType, $destFieldRelId, "", true) as $field) {
        $destCustomFields[$field["name"]] = $field;
    }
    foreach ($destCustomFields as $destFieldName => $destFieldData) {
        if (isset($sourceCustomFields[$destFieldName])) {
            WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->updateOrInsert(array("fieldid" => $destFieldData["id"], "relid" => $toItemId), array("value" => $sourceCustomFields[$destFieldName]["rawvalue"]));
        }
    }
    return true;
}
function migrateCustomFields($itemType, $itemID, $newRelID)
{
    switch ($itemType) {
        case "product":
            $existingRelID = get_query_val("tblhosting", "packageid", array("id" => $itemID));
            break;
        case "support":
            $existingRelID = get_query_val("tbltickets", "did", array("id" => $itemID));
            break;
        case "addon":
            $existingRelID = get_query_val("tblhostingaddons", "addonid", array("id" => $itemID));
            break;
        default:
            $existingRelID = 0;
    }
    if (!$existingRelID || $existingRelID == $newRelID) {
        return false;
    }
    $customfields = getcustomfields($itemType, $existingRelID, $itemID, true);
    $dataArr = array();
    $marketConnectOrderNumberValue = NULL;
    foreach ($customfields as $v) {
        $cfid = $v["id"];
        $cfname = $v["name"];
        $cfval = $v["rawvalue"];
        $dataArr[$cfname] = $cfval;
        delete_query("tblcustomfieldsvalues", array("fieldid" => $cfid, "relid" => $itemID));
        if ($cfname == "Order Number" && $cfval) {
            $marketConnectOrderNumberValue = $cfval;
        }
    }
    $hasMarketConnectOrderNumberField = false;
    $customfields = getcustomfields($itemType, $newRelID, "", true);
    $newProductCustomFieldNames = array();
    foreach ($customfields as $v) {
        $cfid = $v["id"];
        $cfname = $v["name"];
        $newProductCustomFieldNames[] = $cfname;
        if (isset($dataArr[$cfname])) {
            insert_query("tblcustomfieldsvalues", array("fieldid" => $cfid, "relid" => $itemID, "value" => $dataArr[$cfname]));
        }
    }
    if (!is_null($marketConnectOrderNumberValue) && !in_array("Order Number", $newProductCustomFieldNames)) {
        $orderNumberFieldId = insert_query("tblcustomfields", array("type" => $itemType, "relid" => $newRelID, "fieldname" => "Order Number", "fieldtype" => "text", "adminonly" => 1));
        insert_query("tblcustomfieldsvalues", array("fieldid" => $orderNumberFieldId, "relid" => $itemID, "value" => $marketConnectOrderNumberValue));
    }
}
function migrateCustomFieldsBetweenProducts($serviceid, $newpid, $save = false)
{
    $existingPid = get_query_val("tblhosting", "packageid", array("id" => $serviceid));
    migrateCustomFieldsBetweenProductsOrAddons($serviceid, $newpid, $existingPid, $save);
}
function migrateCustomFieldsBetweenProductsOrAddons($entityId, $relatedItemId, $existingRelatedItemId, $save = false, $addon = false)
{
    $type = $addon ? "addon" : "product";
    if ($save) {
        $customFieldsArray = array();
        $customFields = getcustomfields($type, $existingRelatedItemId, $entityId, true);
        foreach ($customFields as $v) {
            $k = $v["id"];
            $customFieldsArray[$k] = App::getFromRequest("customfield", $k);
        }
        savecustomfields($entityId, $customFieldsArray, $type, true);
    }
    if ($existingRelatedItemId != $relatedItemId) {
        migratecustomfields($type, $entityId, $relatedItemId);
    }
}

?>