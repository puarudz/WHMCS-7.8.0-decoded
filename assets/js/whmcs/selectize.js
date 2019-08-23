/**
 * Selectize module
 *
 * Most basic usage:
 *  `WHMCS.selectize.register('#mySelect');`
 *    - This will selectize the <select id="mySelect"></select>.  See
 *      .register() for more specifics.
 *
 * Pre-made usage:
 *  `WHMCS.selectize.clientSearch();`
 *    - selectize all '.selectize-client-search'
 *
 *  `WHMCS.selectize.users(selector, options)`
 *    - selectize a given selector with a static array of options (user objects)
 *
 */
(function(module) {
    if (!WHMCS.hasModule('selectize')) {
        WHMCS.loadModule('selectize', module);
    }
})(
function () {
    /**
     * Search-on-type client select & click "#goButton" on 'change' event
     * - will bind to <select> with '.selectize-client-search'
     * - <select> needs data-search-url attribute for 'load' event
     *
     * @returns {Selectize}
     */
    this.clientSearch = function () {
        var itemDecorator = function(item, escape) {
            if (typeof dropdownSelectClient === "function") {
                // updates DOM for admin/supporttickets.php
                dropdownSelectClient(
                    escape(item.id),
                    escape(item.name)
                        + (item.companyname ? ' (' + escape(item.companyname) + ')' : '')
                        + (item.id > 0 ? ' - #' + escape(item.id) : ''),
                    escape(item.email)
                );
            }
            return '<div><span class="name">' + escape(item.name) +
                (item.companyname ? ' (' + escape(item.companyname) + ')' : '')  +
                (item.id > 0 ? ' - #' + escape(item.id) : '') + '</span></div>';
        };

        var selector ='.selectize-client-search';
        var selectElement = jQuery(selector);

        var module = this;
        var selectized = [];
        selectElement.each(function (){
            var element = $(this);
            var configuration = {
                'valueField': element.data('value-field'),
                'labelField': 'name', //legacy? shouldn't be required with render
                'render': {
                    item: itemDecorator
                },
                'load': module.builder.onLoadEvent(
                    element.data('search-url'),
                    function (query) {
                        return {
                            dropdownsearchq: query,
                            clientId: instance.currentValue
                        };
                    }
                )
            };

            var instance = module.users(selector, undefined, configuration);

            instance.on('change', module.builder.onChangeEvent(instance, '#goButton'));

            return selectized.push(instance);
        });

        if (selectized.length > 1) {
            return selectized;
        }

        return selectized[0];

    };

    /**
     * Generic selectize of users
     *  - no 'change' or 'load' events
     *
     * @param selector
     * @param options
     * @param configuration
     * @returns {Selectize}
     */
    this.users = function (selector, options, configuration) {
        var instance = this.register(
            selector,
            options,
            WHMCS.selectize.optionDecorator.user,
            configuration
        );

        instance.settings.searchField = ['name', 'email', 'companyname'];

        return instance;
    };

    this.billingContacts = function (selector, options, configuration) {
        var instance = this.register(
            selector,
            options,
            WHMCS.selectize.optionDecorator.billingContact,
            configuration
        );

        instance.settings.searchField = ['name', 'email', 'companyname', 'address'];

        return instance;
    };

    this.payMethods = function (selector, options, configuration) {
        var instance = this.register(
            selector,
            options,
            WHMCS.selectize.optionDecorator.payMethod,
            configuration
        );

        instance.settings.searchField = ['description', 'shortAccountNumber', 'type', 'payMethodType'];

        return instance;
    };

    this.html = function (selector, options, configuration) {
        var instance = this.register(
            selector,
            options,
            function(item, escape) {
                return '<div class="item">' + item.html + '</div>';
            },
            configuration
        );

        instance.settings.searchField = ['html'];

        return instance;
    };

    this.simple = function (selector, options, configuration) {
        var instance = this.register(
            selector,
            options,
            function(item, escape) {
                return '<div class="item">' + item.value + '</div>';
            },
            configuration
        );

        instance.settings.searchField = ['value'];

        return instance;
    };
    /**
     * Arguments:
     * selector
     *   CSS selector of the <select> element to selectize
     *
     * options
     *   The second argument is a JS array of objects that will be decorated
     *   into <option>s.
     *
     * decorator
     *   The third argument is the option decorator. By default, it will
     *   decorate using the userDecorator.  Value can be a global function,
     *   lambda, or fq function.  This argument will _not_ be applied when
     *   configuration supplies the .render.item or .render.option properties
     *
     * configuration
     *   configuration settings to use during Selectize initialization
     *
     *
     * Some Assumptions & Default settings:
     * settings.valueField and settings.labelField
     *   These are set to 'id' by default; change as needed
     *
     * settings.searchField
     *   Is empty by default; change as needed
     *
     * option and item decoration
     *   this.optionDecorator.user will be applied by default if nothing is
     *   supplied (by means of the decorator arg or within the configuration arg)
     *
     * @copyright Copyright (c) WHMCS Limited 2005-2018
     * @license http://www.whmcs.com/license/ WHMCS Eula
     */
    this.register = function (selector, options, decorator, configuration) {
        var self = this;
        var element = jQuery(selector);

        var instance = self.builder.init(element, configuration);

        // add item & option decorator if not provided in configuration
        var itemDecorator = self.builder.itemDecorator(decorator);
        if (typeof configuration === "undefined") {
            instance.settings.render.item = itemDecorator;
            instance.settings.render.option = itemDecorator;
        } else if (typeof configuration.render === "undefined") {
            instance.settings.render.item = itemDecorator;
            instance.settings.render.option = itemDecorator;
        } else {
            if (typeof configuration.render.item === "undefined") {
                instance.settings.render.item = itemDecorator;
            }
            if (typeof configuration.render.option === "undefined") {
                instance.settings.render.option = itemDecorator;
            }
        }

        this.builder.addOptions(instance, options);


        return instance;
    };

    this.optionDecorator = {
        user: function(item, escape) {
            var name = escape(item.name),
                companyname = '',
                descriptor = '',
                email = '';

            if (item.companyname) {
                companyname = ' (' + escape(item.companyname) + ')';
            }

            if (typeof item.descriptor === "undefined") {
                descriptor = (item.id > 0 ? ' - #' + escape(item.id) : '');
            } else {
                descriptor = escape(item.descriptor);
            }

            if (item.email) {
                email = '<span class="email">' + escape(item.email) + '</span>';
            }

            return '<div>'
                + '<span class="name">' + name + companyname + descriptor + '</span>'
                + email
                + '</div>';
        },
        billingContact: function(item, escape) {
            var name = escape(item.name),
                companyname = '',
                descriptor = '',
                email = '',
                address = '';

            if (item.companyname) {
                companyname = ' (' + escape(item.companyname) + ')';
            }

            if (typeof item.descriptor === "undefined") {
                descriptor = (item.id > 0 ? ' - #' + escape(item.id) : '');
            } else {
                descriptor = escape(item.descriptor);
            }

            if (item.email) {
                email = '<span class="email">' + escape(item.email) + '</span>';
            }

            if (item.address) {
                address = '<span class="email">' + escape(item.address) + '</span>';
            }

            return '<div>'
                + '<span class="name">' + name + companyname + descriptor + '</span>'
                + email
                + address
                + '</div>';
        },
        payMethod: function(item, escape) {
            var brandIcon = '',
                description = '',
                isDefault = '',
                shortAccountNumber = '',
                detail1 = '';

            if (item.brandIcon) {
                brandIcon = '<i class="' + item.brandIcon + '"></i>';
            }
            if (item.isDefault) {
                isDefault = '&nbsp;&nbsp;<i class="fal fa-user-check"></i>';
            }

            if (item.description) {
                description = item.description;
            }
            if (item.shortAccountNumber) {
                if (description.indexOf(item.shortAccountNumber) === -1) {
                    shortAccountNumber = '(' + escape(item.shortAccountNumber) + ')';
                }
            }

            if (item.detail1) {
                detail1 = '<span class="mouse">' + escape(item.detail1) + '</span>';
            }

            return '<div>'
                + '<span class="name"> '
                + brandIcon + '&nbsp;'
                + description + '&nbsp;'
                + shortAccountNumber + '&nbsp;'
                + '&nbsp;&nbsp;' + detail1 + '&nbsp;&nbsp;'
                + isDefault
                + '</span>'
                + '</div>';
        }
    };
    this.builder = {
        init: function (element, configuration)
        {
            var merged,
                defaults = {
                    plugins: ['whmcs_no_results'],
                    valueField: 'id',
                    labelField: 'id',
                    create: false,
                    maxItems: 1,
                    preload: 'focus'
                };

            if (typeof configuration === "undefined") {
                configuration = {};
            }
            merged = jQuery.extend({}, defaults, configuration);

            var thisSelectize = element.selectize(merged);
            /**
             * selectize assigns any items to an array. In order to be able to
             * run additional functions on this (like auto-submit and clear).
             *
             * @link https://github.com/brianreavis/selectize.js/blob/master/examples/api.html
             */
            thisSelectize = thisSelectize[0].selectize;

            thisSelectize.currentValue = '';

            thisSelectize.on('focus', function () {
                thisSelectize.currentValue = thisSelectize.getValue();
                thisSelectize.clear();
            });
            thisSelectize.on('blur', function () {
                if (thisSelectize.getValue() === '') {
                    thisSelectize.setValue(thisSelectize.currentValue);
                }
            });

            return thisSelectize;
        },
        addOptions: function (selectize, options) {
            if (typeof options !== "undefined" && options.length) {
                selectize.addOption(options);
            }
        },
        itemDecorator: function (decorator) {
            if (typeof decorator === "function") {
                return decorator;
            } else if (typeof decorator === "undefined") {
                return WHMCS.selectize.optionDecorator.user;
            }
        },
        onLoadEvent: function (searchUrl, dataCallback) {
            return function (query, callback) {
                jQuery.ajax({
                    url: searchUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: dataCallback(query),
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res);
                    }
                });
            };
        },
        onChangeEvent: function (instance, onChangeSelector) {
            var onChange;
            if (typeof onChangeSelector !== "undefined") {
                onChange = function (value) {
                    var changeSelector = jQuery(onChangeSelector);
                    if (changeSelector.length) {
                        if (value.length && value !== instance.currentValue) {
                            changeSelector.click();
                        }
                    }
                }
            }

            return onChange;
        }
    };

    return this;
});
