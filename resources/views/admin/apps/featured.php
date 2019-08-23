<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$firstSlim = true;
$firstCategory = true;
foreach ($categories->homeFeatured() as $key => $category) {
    echo "    ";
    if ($key == "other") {
        continue;
    }
    if (!$firstCategory) {
        if ($firstSlim) {
            echo "<div class=\"row\">";
            $firstSlim = false;
        }
        echo "<div class=\"col-lg-6\">";
    }
    echo "    <div class=\"app-wrapper featured-cat";
    if (!$firstCategory) {
        echo " slim";
    }
    echo " clearfix\" data-slug=\"";
    echo escape($category->getSlug());
    echo "\">\n        <a href=\"#\" class=\"btn btn-default pull-right btn-view-all\" data-category-slug=\"";
    echo escape($category->getSlug());
    echo "\" data-category-display-name=\"";
    echo escape($category->getDisplayName());
    echo "\">\n            ";
    echo AdminLang::trans("apps.viewAll");
    echo "            <i class=\"fa fa-chevron-right\"></i>\n        </a>\n        <h2>";
    echo escape($category->getDisplayName());
    echo " <span>";
    echo AdminLang::trans("apps.apps");
    echo "</span></h2>\n        <p class=\"lead\">";
    echo escape($category->getTagline());
    echo "</p>\n        <div class=\"apps\">\n            ";
    foreach ($category->getFeaturedAppsForHome($apps) as $app) {
        echo "                ";
        $this->insert("apps/shared/app", array("app" => $app, "featuredOutput" => true));
        echo "            ";
    }
    echo "        </div>\n    </div>\n        ";
    if (!$firstCategory) {
        echo "        </div>\n        ";
    } else {
        $firstCategory = false;
    }
}
echo "</div>\n";

?>