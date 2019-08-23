<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"app-category-title\">\n    <h2>";
echo AdminLang::trans("apps.searchResultsTitle");
echo "</h2>\n    <p class=\"lead\"><span id=\"searchResultsCount\">0</span> ";
echo AdminLang::trans("apps.searchMatchesFound");
echo "</p>\n</div>\n\n<div class=\"app-wrapper min-search-term\">\n    <span>\n        ";
echo AdminLang::trans("apps.searchMinSearchTerm");
echo "    </span>\n</div>\n<div class=\"app-wrapper no-results-found hidden\">\n    <span>\n        ";
echo AdminLang::trans("apps.searchNoResultsFound");
echo "    </span>\n</div>\n\n<div class=\"search-wrapper hidden\">\n    <div class=\"app-wrapper clearfix\">\n        <h3>";
echo AdminLang::trans("apps.recommendedTitle");
echo "</h3>\n        <div class=\"apps search-apps-featured\">\n        </div>\n    </div>\n\n    <div class=\"app-wrapper clearfix\">\n        <div class=\"apps search-apps-regular search-apps-load-target\">\n        </div>\n    </div>\n</div>\n";

?>