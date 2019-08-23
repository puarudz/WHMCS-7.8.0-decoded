/**
 * WHMCS core JS library reference
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

(function (window, factory) {
    if (typeof window.WHMCS !== 'object') {
        window.WHMCS = factory;
    }
}(
    window,
    {
        hasModule: function (name) {
            return (typeof WHMCS[name] !== 'undefined'
                && Object.getOwnPropertyNames(WHMCS[name]).length > 0);
        },
        loadModule: function (name, module) {
            if (this.hasModule(name)) {
                return;
            }

            WHMCS[name] = {};
            if (typeof module === 'function') {
                (module).apply(WHMCS[name]);
            } else {
                for (var key in module) {
                    if (module.hasOwnProperty(key)) {
                        WHMCS[name][key] = {};
                        (module[key]).apply(WHMCS[name][key]);
                    }
                }
            }
        }
    }
));

jQuery(document).ready(function() {
    jQuery(document).on('click', '.disable-on-click', function () {
        jQuery(this).addClass('disabled');

        if (jQuery(this).hasClass('spinner-on-click')) {
            var icon = $(this).find('i.fas,i.far,i.fal,i.fab');

            jQuery(icon)
                .removeAttr('class')
                .addClass('fas fa-spinner fa-spin');
        }
    });
});
