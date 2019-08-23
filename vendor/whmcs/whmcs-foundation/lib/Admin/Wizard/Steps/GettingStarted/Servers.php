<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class Servers
{
    public function getTemplateVariables()
    {
        $server = new \WHMCS\Admin\Setup\Servers();
        return array("autoConfigServers" => $server->getAutoPopulateServers());
    }
    public function getStepContent()
    {
        return "<div class=\"alert alert-info info-alert\">{lang key=\"wizard.sellingWebHosting\"}</div>\n\n<div class=\"form-horizontal\">\n    <div class=\"form-group\">\n        <label for=\"inputControlPanel\" class=\"col-sm-3 control-label\">{lang key=\"fields.controlpanel\"}</label>\n        <div class=\"col-sm-9\">\n\n            <div style=\"margin-bottom:5px;\">{lang key=\"wizard.serverTypeNotListed\"}</div>\n\n            <input type=\"hidden\" name=\"module\" value=\"\" id=\"inputModule\" />\n            <div class=\"server-module-select\">\n                {foreach \$autoConfigServers as \$server}\n\n                    <span data-module=\"{\$server}\">\n                        <img src=\"../modules/servers/{\$server}/logo.png\" height=\"22\" />\n                    </span>\n\n                {/foreach}\n            </div>\n\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputHostname\" class=\"col-sm-3 control-label\">{lang key=\"fields.hostnameip\"}</label>\n        <div class=\"col-sm-9\">\n            <input id=\"inputHostname\" type=\"text\" name=\"hostname\" class=\"form-control\" placeholder=\"server1.example.com\">\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputServerUsername\" class=\"col-sm-3 control-label\">{lang key=\"fields.username\"}</label>\n        <div class=\"col-sm-3\">\n            <input id=\"inputServerUsername\" type=\"text\" name=\"username\" class=\"form-control\" placeholder=\"root\">\n        </div>\n        <label for=\"inputPassword\" class=\"col-sm-2 control-label\">{lang key=\"fields.password\"}</label>\n        <div class=\"col-sm-4\">\n            <input id=\"inputPassword\" type=\"password\" name=\"password\" class=\"form-control\">\n        </div>\n    </div>\n</div>\n\n<div style=\"background-color:#f8f8f8;margin:15px 0 12px 0;padding:10px;text-align:left;\">\n    {lang key=\"wizard.testServerConnectionDescription\"}\n    <div class=\"pull-right\" style=\"margin-top:-2px;\">\n    <button type=\"button\" class=\"btn btn-primary btn-xs\" id=\"btnVerifyConnection\">\n        <span>{lang key=\"wizard.verifyConnection\"}</span>\n        <span class=\"hidden\"><i class=\"fas fa-spinner fa-spin\"></i> {lang key=\"wizard.verifyConnectionConnecting\"}</span>\n    </button>\n    </div>\n</div>\n\n<div class=\"row\">\n    <div class=\"col-sm-6\">\n        <div class=\"form-group\">\n            <label for=\"inputServerName\">{lang key=\"wizard.serverName\"} <span class=\"field-desc\">{lang key=\"wizard.serverNameDesc\"}</span></label>\n            <input type=\"text\" name=\"name\" class=\"form-control\" id=\"inputServerName\" placeholder=\"{lang key=\"wizard.serverNameExample\"}\" value=\"\">\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputNameserver1\">{lang key=\"configservers.primarynameserver\"}</label>\n            <input type=\"text\" name=\"nameserver1\" class=\"form-control\" id=\"inputNameserver1\" placeholder=\"{lang key=\"configservers.primarynameserverexample\"}\" value=\"\">\n        </div>\n    </div>\n    <div class=\"col-sm-6\">\n        <div class=\"form-group\">\n            <label for=\"inputPrimaryIp\">{lang key=\"wizard.serverPrimaryIp\"} <span class=\"field-desc\">{lang key=\"wizard.displayedInWelcomeEmails\"}</span></label>\n            <input type=\"text\" name=\"primaryip\" class=\"form-control\" id=\"inputPrimaryIp\" placeholder=\"xx.xxx.xx.xx\" value=\"\">\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputNameserver2\">{lang key=\"configservers.secondarynameserver\"}</label>\n            <input type=\"text\" name=\"nameserver2\" class=\"form-control\" id=\"inputNameserver2\" placeholder=\"{lang key=\"configservers.secondarynameserverexample\"}\" value=\"\">\n        </div>\n    </div>\n</div>\n\n<script>\n\nvar verifyButtonLabel;\n\n\$(document).ready(function() {\n    \$(\".server-module-select span\").click(function() {\n        \$(\"#inputModule\").val(\$(this).data(\"module\"));\n        \$(\".server-module-select span\").removeClass('active');\n        \$(this).addClass('active');\n    });\n    \$(\".server-module-select span:first-child\").click();\n\n    \$('#btnVerifyConnection').click(function() {\n        var requestVars = 'module=' + \$('#inputModule').val()\n            + '&hostname=' + \$('#inputHostname').val()\n            + '&username=' + \$('#inputServerUsername').val()\n            + '&password=' + \$('#inputPassword').val();\n\n        \$('#btnVerifyConnection span:first-child').hide();\n        \$('#btnVerifyConnection span:last-child').removeClass('hidden').show();\n        wizardCall('verifyConnection', requestVars, function(data){\n            \$('#btnVerifyConnection span:first-child').show();\n            \$('#btnVerifyConnection span:last-child').hide();\n            if (data.error) {\n                wizardError(data.error);\n            } else {\n                \$('.info-alert:visible').html('{lang key=\"wizard.testConnectionSuccessful\"|escape:'javascript'}').removeClass('alert-info').removeClass('alert-danger').addClass('alert-success');\n                \$('#inputServerName').val(data.serverName);\n                \$('#inputPrimaryIp').val(data.primaryIp);\n                \$('#inputNameserver1').val(data.nameservers[0]);\n                \$('#inputNameserver2').val(data.nameservers[1]);\n            }\n        });\n    });\n});\n</script>\n";
    }
    public function verifyConnection($data)
    {
        try {
            $hostAddress = new \WHMCS\Filter\HostAddress($data["hostname"]);
        } catch (\WHMCS\Exception\Validation\InvalidHostAddress $e) {
            throw new \WHMCS\Exception(\AdminLang::trans("validation.regex", array(":attribute" => \AdminLang::trans("fields.hostnameip"))));
        }
        $server = new \WHMCS\Admin\Setup\Servers();
        $module = $data["module"];
        $hostname = $hostAddress->getHostname();
        $username = $data["username"];
        $password = $data["password"];
        $ipaddress = "";
        $accesshash = "";
        $secure = true;
        $port = $hostAddress->getPort();
        return $server->fetchAutoPopulateServerConfig($module, $hostname, $ipaddress, $username, $password, $accesshash, $secure, $port);
    }
    public function save($data)
    {
        $server = new \WHMCS\Admin\Setup\Servers();
        $accesshash = "";
        $serverId = $server->add($data["name"], $data["module"], $data["primaryip"], "", $data["hostname"], "", "", "", $data["nameserver1"], "", $data["nameserver2"], "", "", "", "", "", "", "", 200, $data["username"], $data["password"], $accesshash, true, "", array(), 0);
        $server->createApiToken($data["module"], $serverId);
    }
}

?>