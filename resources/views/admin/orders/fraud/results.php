<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($errorMsg) {
    echo "    <div class=\"alert alert-danger\">\n        <strong>";
    echo AdminLang::trans("maxmind.followingErrorOccurring");
    echo ":</strong><br>\n        ";
    echo $errorMsg;
    echo "    </div>\n";
}
echo "\n<div class=\"container order-fraud-check-results\">\n\n    ";
echo $prePanelsOutput;
echo "\n    <div class=\"row\">\n        ";
foreach ($panels as $panelTitle => $panelData) {
    echo "            <div class=\"col-md-6\">\n                <div class=\"panel panel-default";
    echo in_array($panelTitle, $disabledPanels) ? " panel-disabled" : "";
    echo "\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">";
    echo AdminLang::trans("maxmind.panels." . $panelTitle);
    echo "</h3>\n                    </div>\n                    <div class=\"panel-body\">\n                        <table class=\"table table-striped\">\n                            ";
    foreach ($panelData as $key => $value) {
        echo "                                <tr>\n                                    <td width=\"40%\">\n                                        ";
        echo AdminLang::trans("maxmind.results." . $key);
        echo ":\n                                    </td>\n                                    <td id=\"fraud.";
        echo $key;
        echo "\">\n                                        ";
        if (!isset($value)) {
            echo "                                            -\n                                        ";
        } else {
            if (in_array($key, $meteredFields)) {
                echo "                                            <div class=\"progress\">\n                                              <div class=\"progress-bar progress-bar-info progress-bar-striped\" role=\"progressbar\" aria-valuenow=\"";
                echo $value;
                echo "\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"min-width: 4em;width:";
                echo $value;
                echo "%;\">\n                                                ";
                echo $value;
                echo "%\n                                              </div>\n                                            </div>\n                                        ";
            } else {
                if (in_array($key, $booleanFields)) {
                    echo "                                            ";
                    echo $value ? "<i class=\"fas fa-check\"></i>" : "<i class=\"fas fa-times\"></i>";
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
echo "\n</div>\n\n<script type=\"text/javascript\" src=\"../assets/js/jquery.knob.js\"></script>\n<script>\n\$('.fraud-check-meter').knob({\n    'format': function (value) {\n        return value + '%';\n    }\n});\n\$('.order-fraud-check-results .panel-disabled').each( function( index, element ){\n    var d = document.createElement('div');\n    \$(d).css({\n        'background-color': '#000',\n        'opacity': '0.5',\n        'position': 'absolute',\n        'top': \$(this).offset().top,\n        'left': \$(this).offset().left,\n        'width': \$(this).width() + 2,\n        'height': \$(this).height() + 2,\n        'line-height': \$(this).height() + 'px',\n        'text-align': 'center',\n        'color': '#fff',\n        'border-radius': \$(this).css('border-radius'),\n        'zIndex': 100\n    }).html('";
echo AdminLang::trans("maxmind.results.requires_factors_or_insights");
echo "');\n    \$('#fraudresults').append(d);\n});\n</script>\n";

?>