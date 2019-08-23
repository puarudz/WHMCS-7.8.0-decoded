<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$this->layout("layouts/minimal");
$errorHtml = "<br/>";
if (!isset($licenseKey)) {
    $licenseKey = "";
}
if (!empty($errorMessage)) {
    $errorHtml = "<span class=\"alert alert-danger text-center\">" . $errorMessage . "</span>";
}
$this->start("body");
echo "<script>\n    jQuery(document).ready(function(){\n        var licenseKey = jQuery('#inputLicenseKey');\n        \$('.form-group').find('input[type=\"text\"]').on('input', function() {\n            flagAnyErrors();\n        });\n        jQuery('#btnKeySubmit').on('click', function(event){\n           event.preventDefault();\n           var formData = jQuery('#frmActivateLicense').serialize();\n           jQuery.post(\n               '";
echo routePath("admin-license-update-key");
echo "',\n               formData,\n               function(data) {\n                   if (data.success && data.redirect) {\n                       window.location = data.redirect;\n                   } else {\n                       //@TODO handle error\n                       jQuery('#errorContainer').html(data.errorMessage).fadeIn();\n                       jQuery('.license-key').addClass('error');\n                   }\n               }\n           );\n        });\n    });\n</script>\n\n<p>You must enter a license key to activate and begin using WHMCS.</p>\n\n<div class=\"buy-promo\">\n    <strong>Don't have a license key yet?</strong><br>\n    Get started with 50% off your first month using promotion code BUY50OFF<br>\n    <a href=\"https://go.whmcs.com/1365/buy-whmcs-license\" class=\"btn btn-default\" target=\"_blank\">\n        Buy a License Now\n    </a>\n</div>\n\n<form class=\"form-horizontal\"\n      name=\"frmActivateLicense\"\n      id=\"frmActivateLicense\"\n      method=\"post\"\n      action=\"\">\n\n    <input type=\"hidden\" name=\"token\" value=\"";
echo $csrfToken;
echo "\">\n\n    <div class=\"license-key-error\" id=\"errorContainer\"></div>\n\n    <div class=\"license-key\">\n      <span class=\"fas fa-key\"></span>\n      <input type=\"text\"\n            class=\"form-control license-key\"\n            id=\"inputLicenseKey\"\n            name=\"license_key\"\n            value=\"";
echo $licenseKey;
echo "\"\n            placeholder=\"";
echo AdminLang::trans("License Key");
echo "\"\n            aria-placeholder=\"";
echo AdminLang::trans("License Key");
echo "\" />\n    </div>\n\n    <div class=\"btn-container\">\n        <input type=\"submit\"\n               class=\"btn btn-success btn-lg\"\n               id=\"btnKeySubmit\"\n               value=\"Activate\" />\n    </div>\n</form>\n\n";
$this->end();

?>