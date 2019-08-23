<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<script>\njQuery(document).ready(function(){\n    jQuery('#modalAjax .modal-submit').hide();\n\n    var storageSelectized = WHMCS.selectize.simple(\n        '#selectStorage',\n        ";
echo json_encode($storageOptions);
echo "    );\n\n    storageSelectized.on('change', function (value) {\n        if (value.length && value !== storageSelectized.currentValue) {\n            jQuery('#modalAjax .modal-submit').show();\n            jQuery('#modalAjax .modal-submit').prop('disabled', true);\n            jQuery('#modalAjax .loader').show();\n            updateAjaxModal({\n                url: '";
echo $actionUrl . "/";
echo "' + value\n            });\n        }\n    });\n});\n</script>\n<h2>Select Storage Location</h2>\n<form>\n    <select id=\"selectStorage\"\n            name=\"desiredStorage\"\n            class=\"form-control selectize\"\n            data-value-field=\"id\"\n            placeholder=\"Select Storage Option\">\n    </select>\n</form>";

?>