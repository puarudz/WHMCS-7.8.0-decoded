<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "\n<h2>";
echo AdminLang::trans("global.stepOfStep", array(":step" => 4, ":steps" => 4));
echo ": Summary</h2>\n\n";
if ($hasErrors) {
    echo "    <div class=\"alert alert-warning\" style=\"font-size:1.3em;\">\n        <i class=\"fas fa-exclamation-triangle\"></i>\n        Server Sync Completed but with Errors\n    </div>\n";
} else {
    echo "    <div class=\"alert alert-success\" style=\"font-size:1.3em;\">\n        <i class=\"fas fa-check\"></i>\n        Server Sync Completed!\n    </div>\n";
}
echo "\n<p>";
echo AdminLang::trans("utilities.serverSync.results");
echo "</p>\n\n<ul>\n";
if (count($import)) {
    echo "<li>" . AdminLang::trans("utilities.serverSync.imported", array(":selected" => count($import), ":completed" => count($imported))) . "</li>";
}
if (count($sync)) {
    echo "<li>" . AdminLang::trans("utilities.serverSync.synced", array(":selected" => count($sync), ":completed" => count($sync))) . "</li>";
}
if (count($terminate)) {
    echo "<li>" . AdminLang::trans("utilities.serverSync.terminated", array(":selected" => count($terminate), ":completed" => count($terminate))) . "</li>";
}
if ($hasErrors) {
    echo "<li>The following errors occurred:<ol>";
    foreach ($errors as $error) {
        echo "<li>" . $error . "</li>";
    }
    echo "</ol>";
}
echo "</ul>\n\n<p>";
echo AdminLang::trans("utilities.serverSync.seeActivityLog");
echo "</p>\n\n<br>\n\n<a href=\"configservers.php\" id=\"btnFinish\" class=\"btn btn-lg btn-primary\">\n    ";
echo AdminLang::trans("global.finish");
echo "    <i class=\"fas fa-chevron-right\"></i>\n</a>\n";

?>