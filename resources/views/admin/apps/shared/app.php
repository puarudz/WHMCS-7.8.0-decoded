<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($app->isVisible()) {
    echo "    <div class=\"app";
    echo ($searchDisplay ? " search" : "") . (isset($featuredOutput) && $featuredOutput || !isset($featuredOutput) && $app->isFeatured() ? " featured" : "");
    echo "\">\n        <a href=\"";
    echo routePath("admin-apps-info", $app->getKey());
    echo "\" class=\"app-inner open-modal\" data-modal-class=\"app-info-modal\" data-modal-size=\"modal-lg\">\n            <div class=\"logo-container\">\n                ";
    if ($app->hasLogo()) {
        echo "                    <img src=\"data:image/png;base64,";
        echo base64_encode($app->getLogoContent());
        echo "\" alt=\"";
        echo escape($app->getDisplayName());
        echo "\">\n                ";
    } else {
        echo "                    <span class=\"no-image-available\">no image available</span>\n                ";
    }
    echo "            </div>\n            <div class=\"content-container\">\n                <div class=\"title\">";
    echo escape($app->getDisplayName());
    echo "</div>\n                <div class=\"description";
    echo !$app->getTagline() ? " none" : "";
    echo "\">";
    echo escape($app->getTagline());
    echo "</div>\n                <span class=\"category\">";
    echo escape($app->getCategory());
    echo "</span>\n                ";
    if ($app->isUpdated()) {
        echo "                    <span class=\"popular-star\"><i class=\"fas fa-code\"></i></span>\n                ";
    } else {
        if ($app->isPopular()) {
            echo "                    <span class=\"popular-star\"><i class=\"fas fa-angle-double-up\"></i></span>\n                ";
        } else {
            if ($app->isFeatured()) {
                echo "                    <span class=\"popular-star\"><i class=\"fas fa-star\"></i></span>\n                ";
            }
        }
    }
    echo "                <span class=\"keywords hidden\">";
    echo escape(implode(" ", $app->getKeywords()));
    echo "</span>\n            </div>\n        </a>\n    </div>\n";
}

?>