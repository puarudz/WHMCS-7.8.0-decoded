<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<script>\n    \$(document).ready(function() {\n        function getAdvancedSettings () {\n            var advancedSettings = {\n                products: {\n                    disabled: [],\n                    pricing: []\n                },\n                promotions: [],\n                general: [],\n            };\n\n            /** Get products that should be disabled **/\n            jQuery('input.product-status').not(':checked').each(function(i, e) {\n                advancedSettings.products.disabled.push(jQuery(e).data('productkey'));\n            });\n\n            /** Get promotions that should be disabled **/\n            jQuery('input.promo-switch').each(function(i, e) {\n                var input = jQuery(e);\n                advancedSettings.promotions.push({\n                    name: input.data('promo'),\n                    state: (input.is(':checked')) ? 1 : 0\n                });\n            });\n\n            jQuery('input.setting-switch').each(function(i, e) {\n                var input = jQuery(e);\n                advancedSettings.general.push({\n                    name: input.data('name'),\n                    state: (input.is(':checked')) ? 1 : 0\n                });\n            });\n\n            /** Get pricing for products **/\n            jQuery('ul.products form').each(function (i, e){\n                var pricingData = encodeURIComponent(jQuery(e).serialize());\n                advancedSettings.products.pricing.push(pricingData);\n            });\n\n            return advancedSettings;\n        }\n        \$(document).on('click', '.btn-activate', function() {\n            \$(this).html('<i class=\"fas fa-spinner fa-spin\"></i> Activating...').prop('disabled', true);\n            var service = \$(this).data('service');\n            var advancedSettings = {};\n            if ( jQuery(this).hasClass('btn-activate-advanced')) {\n                advancedSettings = getAdvancedSettings();\n            }\n            WHMCS.http.jqClient.post('', 'action=activate&service=' + service + '&token=' + csrfToken  + '&advancedSettings=' + JSON.stringify(advancedSettings), function(data) {\n                if (data.success) {\n                    \$('#modalAjax').modal('hide');\n                    swal(\"Success!\", \"Service activated successfully!\", \"success\");\n                    \$('#btnManage-' + service).removeClass('hidden').show();\n                    \$('#btnStart-' + service).hide();\n                    \$('.btn-activate').html('Activate').prop('disabled', false);\n                } else {\n                    var error = data.error;\n                    if (!error) {\n                        error = 'An unknown error occurred. Please try again or contact support.';\n                    }\n                    swal(\"Uh oh!\", error, \"error\");\n                    \$('.btn-activate').html('Activate').prop('disabled', false);\n                }\n            }, 'json');\n\n        });\n        \$(document).on('click', '.btn-deactivate', function() {\n            var service = \$(this).data('service');\n            swal({\n                    title: \"Are you sure?\",\n                    text: \"Deactivating this MarketConnect service will hide all products and prevent new orders being placed. It will not affect existing provisioned services.\",\n                    type: \"warning\",\n                    showCancelButton: true,\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"Yes, deactivate it\",\n                    closeOnConfirm: false\n                },\n                function(){\n                    WHMCS.http.jqClient.post('', 'action=deactivate&service=' + service + '&token=' + csrfToken, function(data) {\n                        \$('#modalAjax').modal('hide');\n                        if (data.success) {\n                            \$('#btnManage-' + service).hide();\n                            \$('#btnStart-' + service).removeClass('hidden').show();\n                            swal(\"Service Deactivated\", \"This service has now been removed from sale.\", \"success\");\n                        } else {\n                            var error = data.error;\n                            if (!error) {\n                                error = 'An unknown error occurred. Please try again or contact support.';\n                            }\n                            swal(\"Uh oh!\", error, \"error\");\n                        }\n                    }, 'json');\n                });\n        });\n        ";
if ($learnMore) {
    echo "            \$('#btnLearnMore-";
    echo $learnMore;
    echo "').click();\n        ";
}
echo "        ";
if ($activateService) {
    echo "            \$('#btnStart-";
    echo $activateService;
    echo "').click();\n        ";
}
echo "        ";
if ($manageService) {
    echo "            \$('#btnManage-";
    echo $manageService;
    echo "').click();\n        ";
}
echo "    });\n\n    var myDefaultWhiteList = \$.fn.tooltip.Constructor.DEFAULTS.whiteList;\n    myDefaultWhiteList.button = ['data-role'];\n\n    var tour = new Tour({\n        name: \"marketconnect\",\n        container: \"body\",\n        smartPlacement: true,\n        keyboard: true,\n        storage: window.localStorage,\n        steps: [\n            ";
foreach ($tourSteps as $step) {
    echo "            {\n                element: \"";
    echo $step["element"];
    echo "\",\n                title: \"";
    echo addslashes($step["title"]);
    echo "\",\n                content: \"";
    echo addslashes($step["content"]);
    echo "\",\n                backdrop: ";
    echo $step["backdrop"] ? "true" : "false";
    echo ",\n                placement: \"";
    echo $step["placement"];
    echo "\",\n            },\n            ";
}
echo "        ]});\n    tour.init();\n    tour.start(";
if ($forceTour) {
    echo "true";
}
echo ");\n</script>\n\n<a href=\"#\" onclick=\"tour.restart().start(true);return false\" class=\"btn btn-default\">\n    <i class=\"fas fa-play-circle fa-fw\"></i>\n    Watch the Tour Again\n</a>\n";

?>