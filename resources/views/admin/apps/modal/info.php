<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<button type=\"button\" class=\"close\" data-dismiss=\"modal\">\n    <span aria-hidden=\"true\">&times;</span>\n    <span class=\"sr-only\">";
echo AdminLang::trans("global.close");
echo "</span>\n</button>\n\n<div class=\"row\">\n    <div class=\"col-md-8\">\n\n        ";
if ($app->hasLogo()) {
    echo "            <div class=\"logo\">\n                <img src=\"data:image/png;base64,";
    echo base64_encode($app->getLogoContent());
    echo "\" alt=\"";
    echo $app->getDisplayName();
    echo "\">\n            </div>\n        ";
} else {
    echo "            <div class=\"title\">";
    echo escape($app->getDisplayName());
    echo "</div>\n        ";
}
echo "\n        <div class=\"tagline\">";
echo escape($app->getTagline());
echo "</div>\n\n        <div class=\"description\">\n            ";
echo $app->getLongDescription();
echo "        </div>\n\n        ";
if ($app->hasFeatures()) {
    echo "            <div class=\"features\">\n                <h4>";
    echo AdminLang::trans("apps.info.features");
    echo "</h4>\n                <ul>\n                    ";
    foreach ($app->getFeatures() as $feature) {
        echo "<li>" . escape($feature) . "</li>";
    }
    echo "                </ul>\n            </div>\n        ";
}
echo "\n    </div>\n    <div class=\"col-md-4\">\n\n        <div class=\"sidebar\">\n            <div class=\"title\">";
echo escape($app->getDisplayName());
echo "</div>\n\n            <strong>";
echo AdminLang::trans("apps.info.category");
echo "</strong>\n            <span>";
echo escape($app->getCategoryDisplayName());
echo "</span>\n            <strong>";
echo AdminLang::trans("apps.info.support");
echo "</strong>\n            <ul>\n                ";
if ($app->getHomepageUrl()) {
    echo "                    <li>\n                        <a href=\"";
    echo escape($app->getHomepageUrl());
    echo "\" target=\"_blank\" class=\"app-external-url\">\n                            <i class=\"far fa-globe fa-fw\"></i>\n                            ";
    echo AdminLang::trans("apps.info.homepage");
    echo "                        </a>\n                    </li>\n                ";
}
echo "                ";
if ($app->getSupportUrl()) {
    echo "                    <li>\n                        <a href=\"";
    echo escape($app->getSupportUrl());
    echo "\" target=\"_blank\" class=\"app-external-url\">\n                            <i class=\"far fa-life-ring fa-fw\"></i>\n                            ";
    echo AdminLang::trans("apps.info.getSupport");
    echo "                        </a>\n                    </li>\n                ";
}
echo "                ";
if ($app->getSupportEmail()) {
    echo "                    <li>\n                        <a href=\"mailto:";
    echo escape($app->getSupportEmail());
    echo "\">\n                            <i class=\"far fa-envelope fa-fw\"></i>\n                            ";
    echo AdminLang::trans("apps.info.contactSupport");
    echo "                        </a>\n                    </li>\n                ";
}
echo "                ";
if ($app->getDocumentationUrl()) {
    echo "                    <li>\n                        <a href=\"";
    echo escape($app->getDocumentationUrl());
    echo "\" target=\"_blank\" class=\"app-external-url\">\n                            <i class=\"far fa-book fa-fw\"></i>\n                            ";
    echo AdminLang::trans("apps.info.documentation");
    echo "                        </a>\n                    </li>\n                ";
}
echo "                ";
if ($app->getLearnMoreUrl()) {
    echo "                    <li>\n                        <a href=\"";
    echo escape($app->getLearnMoreUrl());
    echo "\" target=\"_blank\" class=\"app-external-url\">\n                            <i class=\"far fa-file-alt fa-fw\"></i>\n                            ";
    echo AdminLang::trans("apps.info.learnMore");
    echo "                        </a>\n                    </li>\n                ";
}
echo "                ";
if (!$app->getHomepageUrl() && !$app->getSupportUrl() && !$app->getSupportEmail() && !$app->getDocumentationUrl() && !$app->getLearnMoreUrl()) {
    echo "                    -\n                ";
}
echo "            </ul>\n            ";
if ($app->hasVersion()) {
    echo "                <strong>";
    echo AdminLang::trans("apps.info.version");
    echo "</strong>\n                <span>";
    echo escape($app->getVersion());
    echo "</span>\n            ";
}
echo "            <strong>";
echo AdminLang::trans("apps.info.developer");
echo "</strong>\n            <span>";
echo implode("<br>", $app->getAuthors());
echo "</span>\n            ";
if (0 < count($app->getKeywords())) {
    echo "                <strong>";
    echo AdminLang::trans("apps.info.tags");
    echo "</strong>\n                <span>";
    echo escape(implode(", ", $app->getKeywords()));
    echo "</span>\n            ";
}
echo "\n            <div class=\"management-buttons\">\n                ";
if ($app->isActive()) {
    echo "                    ";
    if ($managementForms = $app->getManagementForms()) {
        echo "                        ";
        foreach ($managementForms as $form) {
            echo "                            <form method=\"";
            echo $form->getMethod();
            echo "\" action=\"";
            echo $form->getUri();
            echo "\">\n                                ";
            foreach ($form->getParameters() as $key => $value) {
                echo "                                    <input type=\"hidden\" name=\"";
                echo $key;
                echo "\" value=\"";
                echo $value;
                echo "\">\n                                ";
            }
            echo "                                <button type=\"submit\" data-app=\"";
            echo $app->getKey();
            echo "\" class=\"btn btn-success btn-block btn-action\">\n                                    ";
            echo $form->getSubmitLabel();
            echo "                                </button>\n                            </form>\n                        ";
        }
        echo "                    ";
    } else {
        echo "                        <a href=\"#\" class=\"btn btn-success btn-block\" disabled=\"disabled\">";
        echo AdminLang::trans("apps.info.alreadyActive");
        echo "</a>\n                    ";
    }
    echo "                ";
} else {
    echo "                    ";
    if ($app->requiresPurchase() && ($app->requiresLicense() && !$app->isLicensed() || !$app->requiresLicense() && !$app->isInstalledLocally())) {
        echo "                        <div class=\"price\">\n                            ";
        echo escape($app->getPurchaseCurrencySymbol() . $app->getPurchasePrice());
        echo "                            ";
        echo escape($app->getPurchaseCurrency());
        echo "                            ";
        echo escape($app->getPurchaseTerm());
        echo "                        </div>\n                        ";
        if ($app->hasPurchaseFreeTrial()) {
            echo "                            <div class=\"free-trial\">\n                                with ";
            echo escape($app->getPurchaseFreeTrialDays());
            echo " Day Free Trial\n                            </div>\n                        ";
        }
        echo "                        <a href=\"";
        echo escape($app->getPurchaseUrl());
        echo "\" class=\"btn btn-success btn-block\" target=\"_blank\">\n                            ";
        if ($app->hasPurchaseFreeTrial()) {
            echo "                                ";
            echo AdminLang::trans("apps.info.startFreeTrial");
            echo "                            ";
        } else {
            echo "                                ";
            echo AdminLang::trans("apps.info.buyItNow");
            echo "                            ";
        }
        echo "                        </a>\n                    ";
    } else {
        if ($app->getMarketplaceUrl() && !$app->isInstalledLocally()) {
            echo "                        <a href=\"";
            echo escape($app->getMarketplaceUrl());
            echo "\" class=\"btn btn-success btn-block\" target=\"_blank\">";
            echo AdminLang::trans("apps.info.getItFrom");
            echo "<br>WHMCS Marketplace</a>\n                    ";
        } else {
            echo "                        ";
            foreach ($app->getActivationForms() as $form) {
                echo "                            <form method=\"";
                echo $form->getMethod();
                echo "\" action=\"";
                echo $form->getUri();
                echo "\">\n                                ";
                foreach ($form->getParameters() as $key => $value) {
                    echo "                                    <input type=\"hidden\" name=\"";
                    echo $key;
                    echo "\" value=\"";
                    echo $value;
                    echo "\">\n                                ";
                }
                echo "                                <button type=\"submit\" data-app=\"";
                echo $app->getKey();
                echo "\" class=\"btn btn-success btn-block btn-action\">\n                                    ";
                echo $form->getSubmitLabel();
                echo "                                </button>\n                            </form>\n                        ";
            }
            echo "                    ";
        }
    }
    echo "                ";
}
echo "            </div>\n        </div>\n\n    </div>\n</div>\n";

?>