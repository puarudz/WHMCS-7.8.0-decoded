<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"alert alert-success text-center text-md\">\n    <i class=\"fa fa-check\"></i>\n    &nbsp;\n    ";
echo Lang::trans("twofanowenabled");
echo "</div>\n\n";
if ($displayMsg) {
    echo "    <div class=\"activation-msg\">\n        ";
    echo $displayMsg;
    echo "    </div>\n";
}
echo "\n<h3 style=\"margin-top:25px;\">";
echo Lang::trans("twofabackupcode");
echo "</h3>\n<p>";
echo Lang::trans("twofabackupcodeintro");
echo "</p>\n<div class=\"backup-code\">\n    ";
echo Lang::trans("twofabackupcodeis");
echo "    <span style=\"display:block;font-family:monospace;font-size:1.6em;\">\n        ";
echo $backupCode;
echo "    </span>\n</div>\n<p>";
echo Lang::trans("twofabackupcodeexpl");
echo "</p>\n\n<br>\n\n<script>\n\$('.twofa-toggle-switch').bootstrapSwitch('state', true, true);\n\$('.twofa-config-link.enable').hide();\n\$('.twofa-config-link.disable').removeClass('hidden').show();\n</script>\n";

?>