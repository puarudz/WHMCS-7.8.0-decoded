<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("WHMCSConnect");
$aInt->title = "Servers";
$aInt->sidebar = "utilities";
$aInt->icon = "servers";
$action = $whmcs->get_req_var("action");
if ($action == "start") {
    echo "<html>\n<head>\n    ";
    echo WHMCS\View\Asset::cssInclude("bootstrap.min.css");
    echo "    <link href=\"https://fonts.googleapis.com/css?family=Indie+Flower\" rel=\"stylesheet\" type=\"text/css\">\n    <style>\n        body {\n            margin: 0;\n            padding: 0 0 0 20px;\n            background-color: #efefef;\n            font-family: 'Indie Flower', Tahoma;\n            font-size: 36px;\n            color: #888;\n        }\n        div.filter-msg {\n            position: absolute;\n            top: 116px;\n        }\n        div.filter-msg img {\n            padding: 30px 0 0 0;\n        }\n        div.footer-msg {\n            position: absolute;\n            bottom: 20px;\n        }\n        div.minimize img {\n            padding: 30px 0 0 30px;\n        }\n    </style>\n</head>\n<body>\n\n    <div class=\"filter-msg\">\n        ";
    echo WHMCS\View\Asset::imgTag("arrow-left.png", "Arrow");
    echo "        ";
    echo AdminLang::trans("whmcsConnect.helperTypeToFilter");
    echo "    </div>\n\n    <div class=\"footer-msg\">\n        ";
    echo WHMCS\View\Asset::imgTag("arrow-left-down.png", "Arrow");
    echo "        ";
    echo AdminLang::trans("whmcsConnect.helperMinimiseSidebar");
    echo "        <div class=\"minimize\">\n            ";
    echo WHMCS\View\Asset::imgTag("arrow-left.png", "Arrow");
    echo "            ";
    echo AdminLang::trans("whmcsConnect.helperReturnToWhmcs");
    echo "        </div>\n    </div>\n\n</body>\n</html>";
    WHMCS\Terminus::getInstance()->doExit();
}
echo "<html>\n<head>\n    <title>";
echo AdminLang::trans("whmcsConnect.whmcsConnectName");
echo " - Powered by WHMCS</title>\n    ";
echo WHMCS\View\Asset::cssInclude("bootstrap.min.css");
echo "    ";
echo WHMCS\View\Asset::cssInclude("fontawesome-all.min.css");
echo "    ";
echo WHMCS\View\Asset::jsInclude("jquery.min.js");
echo "    ";
echo WHMCS\View\Asset::jsInclude("bootstrap.min.js");
echo "    ";
echo WHMCS\View\Asset::jsInclude("jquery.highlight-5.js");
echo "    <style>\n        html, body{\n          height:100%;\n        }\n        body {\n            margin: 0;\n            padding: 0;\n            background-color: #efefef;\n        }\n        a {\n            font-size: 14px;\n            color: #fff;\n            padding: 0 10px 0 0;\n        }\n\n        .sidebar {\n            margin:0;\n            padding:0;\n            width:250px;\n            height:100%;\n            background-color: #fff;\n            border-right: 1px solid #ccc;\n        }\n        .sidebar .return {\n          display:block;\n          position:absolute;\n          bottom: 5px;\n          text-align:center;\n        }\n        .sidebar .return .btn {\n            border-radius: 0;\n            height: 40px;\n        }\n        .sidebar .return #btnMinimize {\n            width: 40px;\n        }\n        .sidebar .return #btnReturn {\n            width: 210px;\n        }\n\n        .logo {\n            padding:20px 0;\n            height: 150px;\n            line-height: 110px;\n            text-align:center;\n            font-size: 1.3em;\n            color: #fff;\n            background-color: #1A4D80;\n        }\n        .logo-vertical {\n            padding: 20px 5px;\n            display: none;\n            background-color: #1A4D80;\n        }\n\n        .servers {\n            margin: 0;\n            overflow:auto;\n            border-top:1px solid #eee;\n            border-bottom:1px solid #eee;\n            font-size: 0.9em;\n        }\n        .server-group {\n            position: relative;\n            margin: 0;\n            padding: 10px 13px;\n            background-color: #f8f8f8;\n            border-bottom: 1px solid #eee;\n        }\n        .server-item {\n            position: relative;\n            margin:0;\n            padding:5px 8px;\n            background-color:#fff;\n            border-bottom:1px solid #eee;\n        }\n        .server-item.active {\n            background-color: #ecf6ff;\n        }\n        .server-item img {\n            position: absolute;\n            top: 12px;\n            left: 10px;\n        }\n        .server-item .item-text {\n            float: left;\n        }\n        .server-item:hover {\n            background-color: #f8f8f8;\n            cursor: pointer;\n        }\n\n        .server-title,\n        .server-group-name {\n            display: block;\n            margin-left: 38px;\n            white-space: nowrap;\n            overflow: hidden;\n            text-overflow: ellipsis;\n            font-size:1.1em;\n            font-weight:bold;\n        }\n        .server-group-name {\n            margin-left: 0;\n        }\n        .server-hostname {\n            display: block;\n            margin-left: 38px;\n            white-space: nowrap;\n            overflow: hidden;\n            text-overflow: ellipsis;\n            color: #ccc;\n        }\n\n        .filter-container {\n            position: relative;\n        }\n        .filter-container i:first-child {\n            position: absolute;\n            z-index: 1;\n            left: 13px;\n            top: 13px;\n            color: #888;\n            width: 0;\n        }\n        .filter-container i:last-child {\n            display: none;\n            position: absolute;\n            z-index: 1;\n            right: 20px;\n            top: 13px;\n            color: #888;\n            width: 0;\n        }\n        .filter-container i:last-child:hover {\n            cursor: pointer;\n        }\n        .filter-container input {\n            padding-left: 40px;\n            height: 40px;\n            border-radius:0;\n            border:0;\n            border-top:1px solid #ccc;\n            border-bottom:1px solid #ccc;\n            background-color:#efefef;\n        }\n\n        .filter-container input:focus {\n            border-color: #ccc;\n            -webkit-box-shadow: none;\n            box-shadow: none;\n        }\n\n        .btn:active,\n        .btn:focus,\n        .btn.active {\n            background-image: none;\n            outline: 0;\n            -webkit-box-shadow: none;\n            box-shadow: none;\n        }\n\n        .highlight {\n            background-color: yellow;\n        }\n\n        #filterBtn {\n            display: none;\n            border-radius: 0;\n            height: 40px;\n            border:0;\n            border-top:1px solid #ccc;\n            border-bottom:1px solid #ccc;\n            background-color: #efefef;\n            color: #888;\n        }\n\n        .frame-container {\n            left: 250px;\n            position: absolute;\n        }\n    </style>\n</head>\n<body>\n\n<div id=\"mainBody\" class=\"frame-container pull-right\">\n    <iframe src=\"whmcsconnect.php?action=start\" name=\"selectedServer\" class=\"iFrame\" id=\"selectedServer\" frameborder=\"0\"></iframe>\n</div>\n<div id=\"sidebar\" class=\"sidebar\">\n\n    <div class=\"logo\">\n        <a href=\"index.php\"><img src=\"images/connect-logo.png\" /></a>\n    </div>\n\n    <div class=\"logo-vertical\">\n        <a href=\"index.php\"><img src=\"images/connect-logo-vertical.png\" height=\"110\" /></a>\n    </div>\n\n    <div class=\"filter-container\">\n        <i class=\"fas fa-search\"></i>\n        <input type=\"text\" id=\"inputFilter\" class=\"form-control\" placeholder=\"";
echo AdminLang::trans("whmcsConnect.typeToFilterList");
echo "\" autofocus />\n        <i id=\"btnClearFilter\" class=\"fas fa-times\">&nbsp;</i>\n    </div>\n\n    <div>\n        <button id=\"filterBtn\" class=\"btn btn-block\">\n            <i class=\"fas fa-search\"></i>\n        </button>\n    </div>\n\n    <div class=\"servers\" id=\"serversList\">\n        ";
$servers = $serverIdsPrinted = array();
$serverTypes = Illuminate\Database\Capsule\Manager::table("tblservers")->distinct()->orderBy("type")->get(array("type"));
foreach ($serverTypes as $type) {
    $module = new WHMCS\Module\Server();
    $module->load($type->type);
    $displayName = $module->getDisplayName();
    if ($module->functionExists("AdminSingleSignOn")) {
        $serversList = Illuminate\Database\Capsule\Manager::table("tblservers")->where("type", "=", $type->type)->where("disabled", "=", 0)->orderBy("name")->get(array("id", "name", "hostname", "ipaddress"));
        foreach ($serversList as $server) {
            $server->logo = $module->getSmallLogoFilename();
            $servers[$server->id] = $server;
        }
    }
}
$serverGroups = Illuminate\Database\Capsule\Manager::table("tblservergroups")->orderBy("name")->get(array("id", "name"));
foreach ($serverGroups as $serverGroup) {
    $serverGroupServers = array();
    $serversInGroup = Illuminate\Database\Capsule\Manager::table("tblservergroupsrel")->where("groupid", "=", $serverGroup->id)->get(array("serverid"));
    foreach ($serversInGroup as $serverInGroup) {
        $serverId = $serverInGroup->serverid;
        if (array_key_exists($serverId, $servers)) {
            $serverGroupServers[] = $serverId;
            $serverIdsPrinted[] = $serverId;
        }
    }
    if ($serverGroupServers) {
        echo "<div class=\"server-group\"><span class=\"server-group-name\">" . $serverGroup->name . "</span></div>";
        foreach ($servers as $serverId => $server) {
            if (in_array($serverId, $serverGroupServers)) {
                $serverHostname = $server->hostname ? $server->hostname : $server->ipaddress;
                if (!$serverHostname) {
                    $serverHostname = "Hostname and IP Missing";
                }
                echo "<div class=\"server-item\" data-server-id=\"" . $serverId . "\">\n<img src=\"../" . $server->logo . "\">\n<span class=\"server-title\">" . $server->name . "</span>\n<span class=\"server-hostname\">" . $serverHostname . "</span>\n</div>";
            }
        }
    }
}
$unGroupedServers = array();
foreach ($servers as $serverId => $server) {
    if (!in_array($serverId, $serverIdsPrinted)) {
        $unGroupedServers[$serverId] = $server;
    }
}
if ($unGroupedServers) {
    echo "<div class=\"server-group\"><span class=\"server-group-name\">" . AdminLang::trans("whmcsConnect.noServerGroup") . "</span></div>";
    foreach ($unGroupedServers as $serverId => $server) {
        $serverHostname = $server->hostname ? $server->hostname : $server->ipaddress;
        if (!$serverHostname) {
            $serverHostname = "Hostname and IP Missing";
        }
        echo "<div class=\"server-item\" data-server-id=\"" . $serverId . "\">\n<img src=\"../" . $server->logo . "\">\n<span class=\"server-title\">" . $server->name . "</span>\n<span class=\"server-hostname\">" . $serverHostname . "</span>\n</div>";
    }
}
echo "    </div>\n\n    <form method=\"get\" action=\"index.php\" target=\"_top\">\n        <div class=\"return btn-group\" id=\"btnContainer\">\n            <button type=\"button\" id=\"btnMinimize\" class=\"btn btn-default\">\n                <i class=\"fas fa-arrow-left\"></i>\n            </button>\n            <button type=\"submit\" id=\"btnReturn\" class=\"btn btn-primary\">\n                ";
echo AdminLang::trans("whmcsConnect.returnToWhmcs");
echo "            </button>\n        </div>\n    </form>\n\n</div>\n\n<script>\n\$(document).ready(function(){\n    // set initial server list height\n    resizeServerList();\n    resizeIFrame();\n\n    \$.extend(\$.expr[\":\"], {\n        \"caseInsensitiveContains\": function(elem, i, match) {\n            return (elem.textContent || elem.innerText || \"\").toLowerCase().indexOf((match[3] || \"\").toLowerCase()) >= 0;\n        }\n    });\n\n    // handle window resize\n    \$(window).resize(function() {\n        resizeServerList();\n        resizeIFrame();\n    });\n\n    // handle server selection\n    \$(\".servers .server-item\").click(function() {\n        loadIFrame(\$(this).attr('data-server-id'));\n        \$(\".servers .server-item\").removeClass('active');\n        \$(this).addClass('active');\n        document.title = \$(this).find('.server-title').removeHighlight().html() + ' - WHMCS Connect';\n    });\n\n    // handle list filtering\n    \$(\"#inputFilter\").keyup(function() {\n        var searchTerm = \$(this).val();\n        \$('.servers .server-item').hide()\n            .removeHighlight()\n            .filter('.server-item')\n            .filter(':caseInsensitiveContains(\"' + searchTerm + '\")')\n            .highlight(searchTerm)\n            .show();\n        if (searchTerm.length > 0) {\n            \$(\"#btnClearFilter\").fadeIn();\n        } else {\n            \$(\"#btnClearFilter\").fadeOut();\n        }\n    });\n\n    // handle clear filter click\n    \$(\"#btnClearFilter\").click(function() {\n        clearFilter();\n    });\n\n    // handle escape to clear filter\n    \$(document).keyup(function(e) {\n        if (e.keyCode == 27) {\n            clearFilter();\n        }\n    });\n\n    // handle sidebar minimize\n    \$(\"#btnMinimize\").click(function() {\n        toggleSidebar();\n    });\n\n    // handle filter button click\n    \$(\"#filterBtn\").click(function() {\n        toggleSidebar();\n        \$(\"#inputFilter\").focus();\n    });\n});\n\nfunction resizeServerList() {\n    var inputFilter = \$(\"#inputFilter\");\n    var maxHeight = \$(\"#btnContainer\").offset().top\n        - inputFilter.offset().top\n        - inputFilter.outerHeight()\n        - 5;\n    \$(\"#serversList\").css('max-height', maxHeight);\n}\n\nfunction resizeIFrame() {\n    var sidebar = \$(\"#sidebar\");\n    var iFrame = \$(\"#selectedServer\");\n    \$(\".frame-container\").css('left', sidebar.width() + 1);\n    iFrame.css('height', window.innerHeight);\n    iFrame.css('width', window.innerWidth - sidebar.width() - 1);\n}\n\nfunction clearFilter() {\n    \$(\"#inputFilter\").val('').focus();\n    \$(\".servers .server-item\").removeHighlight().show();\n    \$(\"#btnClearFilter\").fadeOut();\n}\n\nfunction toggleSidebar() {\n    var sidebar = \$(\"#sidebar\");\n    if (sidebar.css('width') == '40px') {\n        // maximise\n        sidebar.css('width', '250');\n        \$(\".logo\").show();\n        \$(\".logo-vertical\").hide();\n        \$(\".filter-container\").show();\n        \$(\"#filterBtn\").hide();\n        \$(\"#serversList\").show();\n        \$(\"#btnReturn\").show();\n        \$(\"#btnMinimize i\").addClass('fa-arrow-left').removeClass('fa-arrow-right');\n    } else {\n        sidebar.css('width', '40');\n        \$(\".logo\").hide();\n        \$(\".logo-vertical\").show();\n        \$(\".filter-container\").hide();\n        \$(\"#filterBtn\").show();\n        \$(\"#serversList\").hide();\n        \$(\"#btnReturn\").hide();\n        \$(\"#btnMinimize i\").removeClass('fa-arrow-left').addClass('fa-arrow-right');\n    }\n    resizeIFrame();\n}\n\nfunction loadIFrame(serverId) {\n    var iFrame = \$(\"#selectedServer\");\n    iFrame.attr(\n        'src',\n        'configservers.php?action=singlesignon";
echo generate_token("link");
echo "&serverid=' + serverId\n    );\n}\n</script>\n\n</body>\n</html>\n";

?>