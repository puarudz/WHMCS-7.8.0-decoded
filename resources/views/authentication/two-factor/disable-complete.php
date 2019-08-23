<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>";
echo Lang::trans("twofadisableconfirmation");
echo "</p>\n\n<script>\n\$('.twofa-toggle-switch').bootstrapSwitch('state', false, true);\n\$('.twofa-config-link.disable').hide();\n\$('.twofa-config-link.enable').removeClass('hidden').show();\n</script>\n";

?>