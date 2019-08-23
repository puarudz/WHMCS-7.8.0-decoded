<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($errorMsg) {
    echo "    <div class=\"alert alert-danger\">\n        <strong>";
    echo AdminLang::trans("fraudlabs.followingErrorOccurring");
    echo ":</strong><br>\n        ";
    echo $errorMsg;
    echo "    </div>\n";
}
echo "\n<div class=\"container order-fraud-check-results\">\n\n    ";
echo $prePanelsOutput;
echo "\n    <div class=\"row\">\n        ";
foreach ($panels as $panelTitle => $panelData) {
    echo "            <div class=\"col-md-6\">\n                <div class=\"panel panel-default\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">\n                            ";
    echo AdminLang::trans("fraudlabs.panels." . $panelTitle);
    echo "                        </h3>\n                    </div>\n                    <div class=\"panel-body\">\n                        <table class=\"table table-striped\">\n                            ";
    foreach ($panelData as $key => $value) {
        echo "                                <tr>\n                                    <td width=\"40%\">\n                                        ";
        echo AdminLang::trans("fraudlabs.results." . $key);
        echo ":\n                                    </td>\n                                    <td id=\"fraud.";
        echo $key;
        echo "\">\n                                        ";
        if (!isset($value)) {
            echo "                                            -\n                                        ";
        } else {
            if (in_array($key, $meteredFields)) {
                echo "                                            <div class=\"progress\">\n                                                <div class=\"progress-bar progress-bar-info progress-bar-striped\"\n                                                     role=\"progressbar\"\n                                                     aria-valuenow=\"";
                echo $value;
                echo "\"\n                                                     aria-valuemin=\"0\"\n                                                     aria-valuemax=\"100\"\n                                                     style=\"min-width: 4em;width:";
                echo $value;
                echo "%;\"\n                                                >\n                                                    ";
                echo $value;
                echo "%\n                                                </div>\n                                            </div>\n                                        ";
            } else {
                if (in_array($key, $booleanFields)) {
                    echo "                                            ";
                    if ($value == "Y") {
                        echo "                                                <i class=\"fas fa-check\"></i>\n                                            ";
                    } else {
                        if ($value == "N") {
                            echo "                                                <i class=\"fas fa-times\"></i>\n                                            ";
                        } else {
                            echo "                                                <i class=\"fas fa-question\"></i>\n                                            ";
                        }
                    }
                    echo "                                        ";
                } else {
                    echo "                                            ";
                    echo $value;
                    echo "                                        ";
                }
            }
        }
        echo "                                    </td>\n                                </tr>\n                            ";
    }
    echo "                        </table>\n                    </div>\n                </div>\n            </div>\n        ";
}
echo "    </div>\n\n    ";
echo $postPanelsOutput;
echo "\n</div>\n<script type=\"text/javascript\" src=\"../assets/js/jquery.knob.js\"></script>\n<script>\n    \$('.fraud-check-meter').knob({\n        'format': function (value) {\n            return value + '%';\n        }\n    });\n</script>\n";

?>