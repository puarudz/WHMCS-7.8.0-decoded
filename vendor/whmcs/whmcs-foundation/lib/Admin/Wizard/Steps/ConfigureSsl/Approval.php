<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\ConfigureSsl;

class Approval
{
    public function getStepContent()
    {
        $serviceId = \App::getFromRequest("serviceid");
        $addonId = \App::getFromRequest("addonid");
        return "\n            <h2>Choose Approval Method</h2>\n\n            <div class=\"alert alert-warning info-alert\">Choose between Email and HTTP File Based authentication below.</div>\n\n            <p>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"approval_method\" value=\"email\" checked>\n                    Email\n                </label>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"approval_method\" value=\"file\">\n                    HTTP File Based\n                </label>\n            </p>\n\n            <div class=\"well text-center hidden\" style=\"margin:30px;\" id=\"containerApprovalMethodFile\">\n                As you have selected HTTP File Based Authentication, you will be provided with a filename and contents to create inside the target web hosting account upon submission.\n                </div>\n\n            <div id=\"containerApprovalMethodEmail\">\n\n                <h3>Choose Email Address</h3>\n\n                <blockquote class=\"cert-approver-emails\">\n                </blockquote>\n\n            </div>\n\n            <input type=\"hidden\" name=\"serviceid\" value=\"" . $serviceId . "\">\n            <input type=\"hidden\" name=\"addonid\" value=\"" . $addonId . "\">\n\n<script>\n\$(document).ready(function() {\n    \$('input[name=\"approval_method\"]').on('ifChecked', function(event){\n        if (\$(this).attr('value') == 'file') {\n            \$('#containerApprovalMethodFile').removeClass('hidden').show();\n            \$('#containerApprovalMethodEmail').hide();\n        } else {\n            \$('#containerApprovalMethodFile').hide();\n            \$('#containerApprovalMethodEmail').show();\n        }\n    });\n});\n</script>";
    }
    public function save($data)
    {
        $approvalMethod = isset($data["approval_method"]) ? trim($data["approval_method"]) : "";
        $approverEmail = isset($data["approver_email"]) ? trim($data["approver_email"]) : "";
        $serviceId = isset($data["serviceid"]) ? trim($data["serviceid"]) : "";
        $addonId = isset($data["addonid"]) ? trim($data["addonid"]) : "";
        if ($approvalMethod == "email" && !$approverEmail) {
            throw new \WHMCS\Exception("Approver email is required");
        }
        $certConfig = \WHMCS\Session::get("AdminCertConfiguration");
        $serverInterface = new \WHMCS\Module\Server();
        if ($addonId) {
            $serverInterface->loadByAddonId($addonId);
        } else {
            $serverInterface->loadByServiceID($serviceId);
        }
        $configData = array("servertype" => $certConfig["serverType"], "csr" => $certConfig["csr"], "domain" => $certConfig["domain"], "firstname" => $certConfig["admin"]["firstname"], "lastname" => $certConfig["admin"]["lastname"], "orgname" => $certConfig["admin"]["orgname"], "jobtitle" => $certConfig["admin"]["jobtitle"], "email" => $certConfig["admin"]["email"], "address1" => $certConfig["admin"]["address1"], "address2" => $certConfig["admin"]["address2"], "city" => $certConfig["admin"]["city"], "state" => $certConfig["admin"]["state"], "postcode" => $certConfig["admin"]["postcode"], "country" => $certConfig["admin"]["country"], "phonenumber" => $certConfig["admin"]["phonenumber"], "approvalmethod" => $approvalMethod, "approveremail" => $approverEmail);
        $response = $serverInterface->call("SSLStepThree", array("configdata" => $configData));
        if (isset($response["error"]) && $response["error"]) {
            throw new \WHMCS\Exception($response["error"]);
        }
        \WHMCS\Session::start();
        \WHMCS\Session::delete("AdminCertConfiguration");
        \WHMCS\Database\Capsule::table("tblsslorders")->where(array("serviceid" => $serviceId, "addon_id" => $addonId, "module" => "marketconnect"))->update(array("configdata" => safe_serialize($configData), "status" => "Configuration Submitted"));
        $orderNumber = $configData["order_number"];
        $data = new \WHMCS\TransientData();
        $data->delete("marketconnect.order." . $orderNumber);
        $response["refreshMc"] = true;
        return $response;
    }
}

?>