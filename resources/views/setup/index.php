<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"row setup-index\">\n    <div class=\"col-md-8 col-sm-7\">\n\n        <div class=\"filter-container\">\n            <i class=\"fas fa-search\"></i>\n            <input type=\"text\" id=\"inputFilter\" class=\"form-control\" placeholder=\"";
echo AdminLang::trans("global.search");
echo "\" autofocus />\n            <i id=\"btnClearFilter\" class=\"fas fa-times\"></i>\n        </div>\n\n        <div class=\"row setup-links\" id=\"setupLinks\">\n            ";
foreach ($links as $link) {
    echo "                <div class=\"col-lg-6 link-container\">\n                    <a href=\"";
    echo $link["link"];
    echo "\" class=\"link\">\n                        <div class=\"icon\"><i class=\"";
    echo $link["icon"];
    echo " fa-fw\"></i></div>\n                        <div class=\"content\">\n                            <span class=\"title\">\n                                ";
    if (isset($link["image"])) {
        echo "                                    <img src=\"";
        echo $link["image"];
        echo "\" class=\"pull-right\">\n                                ";
    }
    echo "                                ";
    echo $link["title"];
    echo "                                ";
    if (isset($link["badge"])) {
        echo "                                    ";
        echo $link["badge"];
        echo "                                ";
    }
    echo "                            </span>\n                            <span class=\"desc\">";
    echo $link["description"] ? $link["description"] : "...";
    echo "</span>\n                        </div>\n                    </a>\n                </div>\n            ";
}
echo "        </div>\n\n    </div>\n    <div class=\"col-md-4 col-sm-5\">\n\n        <div class=\"tasks\">\n            <p class=\"pull-right\">\n                ";
echo $completedTaskCount;
echo " of ";
echo $totalTaskCount;
echo " Completed\n            </p>\n            <h3>";
echo AdminLang::trans("setup.tasks");
echo "</h3>\n\n            <ul>\n                ";
foreach ($setupTasks as $values) {
    echo "                    <li>\n                        <a href=\"";
    echo $values["link"];
    echo "\">\n                            <i class=\"fas fa-";
    echo $values["completed"] ? "check" : "times";
    echo " fa-fw\"></i>\n                            ";
    echo $values["label"];
    echo "                        </a>\n                    </li>\n                ";
}
echo "            </ul>\n\n            <p class=\"text-right\">\n                <a href=\"https://docs.whmcs.com/Setup_Tasks\" target=\"_blank\">\n                    ";
echo AdminLang::trans("global.learnMore");
echo "                </a>\n            </p>\n        </div>\n\n    </div>\n</div>\n\n";
echo $highlightAssetInclude;
echo "<script>\n\$(document).ready(function(){\n    \$.extend(\$.expr[\":\"], {\n        \"caseInsensitiveContains\": function(elem, i, match) {\n            return (elem.textContent || elem.innerText || \"\").toLowerCase().indexOf((match[3] || \"\").toLowerCase()) >= 0;\n        }\n    });\n    \$(\"#inputFilter\").keyup(function() {\n        var searchTerm = \$(this).val();\n        \$('.setup-links .link-container')\n            .hide()\n            .removeHighlight()\n            .filter('.link-container')\n            .filter(':caseInsensitiveContains(\"' + searchTerm + '\")')\n            .highlight(searchTerm)\n            .show();\n        if (searchTerm.length > 0) {\n            \$(\"#btnClearFilter\").fadeIn();\n        } else {\n            \$(\"#btnClearFilter\").fadeOut();\n        }\n    });\n    \$(\"#btnClearFilter\").click(function() {\n        \$(\"#inputFilter\").val('').focus();\n        \$(\".setup-links .link-container\").removeHighlight().show();\n        \$(\"#btnClearFilter\").fadeOut();\n    });\n});\n</script>\n";

?>