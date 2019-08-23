/**
 * WHMCS UI module
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
(function(module) {
    if (!WHMCS.hasModule('ui')) {
        WHMCS.loadModule('ui', module);
    }
})({
/**
 * Confirmation PopUp
 */
confirmation: function () {

    /**
     * @type {Array} Registered confirmation root selectors
     */
    var toggles = [];

    /**
     * Register/Re-Register all confirmation elements with jQuery
     * By default all elements of data toggle "confirmation" will be registered
     *
     * @param {(string|undefined)} rootSelector
     * @return {Array} array of registered toggles
     */
    this.register = function (rootSelector) {
        if (typeof rootSelector === 'undefined') {
            rootSelector = '[data-toggle=confirmation]';
        }
        if (toggles.indexOf(rootSelector) < 0) {
            toggles.push(rootSelector);
        }

        jQuery(rootSelector).confirmation({
            rootSelector: rootSelector
        });

        return toggles;
    };

    return this;
},

/**
 * Data Driven Table
 */
dataTable: function () {

    /**
     * @type {{}}
     */
    this.tables = {};

    /**
     * Register all tables on page with the class "data-driven"
     */
    this.register = function () {
        var self = this;
        jQuery('table.data-driven').each(function (i, table) {
            self.getTableById(table.id, undefined);
        });
    };

    /**
     * Get a table by id; create table object on fly as necessary
     *
     * @param {string} id
     * @param {({}|undefined)} options
     * @returns {DataTable}
     */
    this.getTableById = function (id, options) {
        var self = this;
        var el = jQuery('#' + id);
        if (typeof self.tables[id] === 'undefined') {
            if (typeof options === 'undefined') {
                options = {
                    dom: '<"listtable"ift>pl',
                    paging: false,
                    lengthChange: false,
                    searching: false,
                    ordering: true,
                    info: false,
                    autoWidth: true,
                    language: {
                        emptyTable: (el.data('lang-empty-table')) ? el.data('lang-empty-table') : "No records found"
                    }
                };
            }
            var ajaxUrl = el.data('ajax-url');
            if (typeof ajaxUrl !== 'undefined') {
                options.ajax = {
                    url: ajaxUrl
                };
            }
            var dom = el.data('dom');
            if (typeof dom !== 'undefined') {
                options.dom = dom;
            }
            var searching = el.data('searching');
            if (typeof searching !== 'undefined') {
                options.searching = searching;
            }
            var responsive = el.data('responsive');
            if (typeof responsive !== 'undefined') {
                options.responsive = responsive;
            }
            var ordering = el.data('ordering');
            if (typeof ordering !== 'undefined') {
                options["ordering"] = ordering;
            }
            var order = el.data('order');
            if (typeof order !== 'undefined' && order) {
                options["order"] = order;
            }
            var colCss = el.data('columns');
            if (typeof colCss !== 'undefined' && colCss) {
                options["columns"] = colCss;
            }
            var autoWidth = el.data('auto-width');
            if (typeof autoWidth !== 'undefined') {
                options["autoWidth"] = autoWidth;
            }
            var paging = el.data('paging');
            if (typeof paging !== 'undefined') {
                options["paging"] = paging;
            }
            var lengthChange = el.data('length-change');
            if (typeof lengthChange !== 'undefined') {
                options["lengthChange"] = lengthChange;
            }
            var pageLength = el.data('page-length');
            if (typeof pageLength !== 'undefined') {
                options["pageLength"] = pageLength;
            }

            self.tables[id] = self.initTable(el, options);
        } else if (typeof options !== 'undefined') {
            var oldTable = self.tables[id];
            var initOpts = oldTable.init();
            var newOpts = jQuery.extend( initOpts, options);
            oldTable.destroy();
            self.tables[id] = self.initTable(el, newOpts);
        }

        return self.tables[id];
    };

    this.initTable = function (el, options) {
        var table = el.DataTable(options);
        var self = this;
        if (el.data('on-draw')) {
            table.on('draw.dt', function (e, settings) {
                var namedCallback = el.data('on-draw');
                if (typeof window[namedCallback] === 'function') {
                    window[namedCallback](e, settings);
                }
            });
        } else if (el.data('on-draw-rebind-confirmation')) {
            table.on('draw.dt', function (e) {
                self.rebindConfirmation(e);
            });
        }

        return table;
    };

    this.rebindConfirmation = function (e) {
        var self = this;
        var tableId = e.target.id;
        var toggles = WHMCS.ui.confirmation.register();
        for(var i = 0, len = toggles.length; i < len; i++ ) {
            jQuery(toggles[i]).on(
                'confirmed.bs.confirmation',
                function (e)
                {
                    e.preventDefault();
                    WHMCS.http.jqClient.post(
                        jQuery(e.target).data('target-url'),
                        {
                            'token': csrfToken
                        }
                    ).done(function (data)
                    {
                        if (data.status === 'success' || data.status === 'okay') {
                            self.getTableById(tableId, undefined).ajax.reload();
                        }
                    });

                }
            );
        }
    };

    return this;
},

clipboard: function() {
    this.copy = function(e) {
        e.preventDefault();

        var trigger = $(e.currentTarget);
        var contentElement = $(trigger).data('clipboard-target');
        var container = $(contentElement).parent();

        try {
            var tempElement = $('<textarea>')
                .css('position', 'fixed')
                .css('opacity', '0')
                .css('width', '1px')
                .css('height', '1px')
                .val($(contentElement).val());

            container.append(tempElement);
            tempElement.focus().select();
            document.execCommand('copy');
        } finally {
            tempElement.remove();
        }

        trigger.tooltip({
            trigger: 'click',
            placement: 'bottom'
        });
        WHMCS.ui.toolTip.setTip(trigger, 'Copied!');
        WHMCS.ui.toolTip.hideTip(trigger);
    };

    return this;
},

/**
 * ToolTip and Clipboard behaviors
 */
toolTip: function () {
    this.setTip = function (btn, message) {
        var tip = btn.data('bs.tooltip');
        if (tip.hoverState !== 'in') {
            tip.hoverState = 'in';
        }
        btn.attr('data-original-title', message);
        tip.show();

        return tip;
    };

    this.hideTip = function (btn) {
        return setTimeout(function() {
            btn.data('bs.tooltip').hide()
        }, 2000);
    }
},

jsonForm: function() {
    this.managedElements = 'input,textarea,select';

    this.initFields = function (form) {
        var self = this;
        $(form).find(self.managedElements).each(function () {
            var field = this;

            $(field).on('keypress change', function () {
                if (self.fieldHasError(field)) {
                    self.clearFieldError(field);
                }
            });
        });
    };

    this.init = function (form) {
        var self = this;

        self.initFields(form);

        $(form).on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.clearErrors(form);

            var formModal = $(form).parents('.modal[role="dialog"]').first();

            if ($(formModal).length) {
                $(formModal).on('show.bs.modal hidden.bs.modal', function() {
                    self.clearErrors(form);
                });

                /*
                 * Make this optional if the form is used for editing
                 */
                $(formModal).on('show.bs.modal', function() {
                    $(form)[0].reset();
                });
            }

            WHMCS.http.client.post({
                url: $(form).attr('action'),
                data: $(form).serializeArray(),
            })
                .done(function (response) {
                    self.onSuccess(form, response);
                })
                .fail(function (jqXHR) {
                    self.onError(form, jqXHR);
                })
                .always(function (data) {
                    self.onRequestComplete(form, data);
                });
        });
    };

    this.initAll = function () {
        var self = this;

        $('form[data-role="json-form"]').each(function() {
            var formElement = this;
            self.init(formElement);
        });
    };

    this.markFieldErrors = function (form, fields)
    {
        var self = this;
        var errorMessage = null;
        var field, fieldLookup;

        for (var fieldName in fields) {
            if (fields.hasOwnProperty(fieldName)) {
                errorMessage = fields[fieldName];
            }

            fieldLookup = self.managedElements.split(',').map(function(element) {
                return element + '[name="' + fieldName + '"]';
            }).join(',');

            field = $(form).find(fieldLookup);

            if (errorMessage) {
                $(field).parents('.form-group').addClass('has-error');
                $(field).attr('title', errorMessage);
                $(field).tooltip();
            }
        }

        $(form).find('.form-group.has-error input[title]').first().tooltip('show');
    };

    this.fieldHasError = function (field) {
        return $(field).parents('.form-group').hasClass('has-error');
    };

    this.clearFieldError = function (field) {
        $(field).tooltip('destroy');
        $(field).parents('.form-group').removeClass('has-error');
    };

    this.onSuccess = function (form, response) {
        var formOnSuccess = $(form).data('on-success');

        if (typeof formOnSuccess === 'function') {
            formOnSuccess(response.data);
        }
    };

    this.onError = function (form, jqXHR) {
        if (jqXHR.responseJSON && jqXHR.responseJSON.fields && typeof jqXHR.responseJSON.fields === 'object') {
            this.markFieldErrors(form, jqXHR.responseJSON.fields);
        } else {
            // TODO: replace with client-accessible generic error messaging
            console.log('Unknown error - please try again later.');
        }

        var formOnError = $(form).data('on-error');

        if (typeof formOnError === 'function') {
            formOnError(jqXHR);
        }
    };

    this.clearErrors = function (form) {
        var self = this;

        $(form).find(self.managedElements).each(function () {
            self.clearFieldError(this);
        })
    };

    this.onRequestComplete = function (form, data) {
        // implement as needed
    };

    return this;
},

effects: function () {
    this.errorShake = function (element) {
        /**
         * Shake effect without jQuery UI inspired by Hiren Patel | ninty9notout:
         * @see https://github.com/ninty9notout/jquery-shake/blob/51f3dcf625970c78505bcac831fd9e28fc85d374/jquery.ui.shake.js
         */
        options = options || {};
        var options = $.extend({
            direction: "left",
            distance: 8,
            times: 3,
            speed: 90
        }, options);

        return element.each(function () {
            var el = $(this), props = {
                position: el.css("position"),
                top: el.css("top"),
                bottom: el.css("bottom"),
                left: el.css("left"),
                right: el.css("right")
            };

            el.css("position", "relative");

            var ref = (options.direction === "up" || options.direction === "down") ? "top" : "left";
            var motion = (options.direction === "up" || options.direction === "left") ? "pos" : "neg";

            var animation = {}, animation1 = {}, animation2 = {};
            animation[ref] = (motion === "pos" ? "-=" : "+=") + options.distance;
            animation1[ref] = (motion === "pos" ? "+=" : "-=") + options.distance * 2;
            animation2[ref] = (motion === "pos" ? "-=" : "+=") + options.distance * 2;

            el.animate(animation, options.speed);
            for (var i = 1; i < options.times; i++) {
                el.animate(animation1, options.speed).animate(animation2, options.speed);
            }

            el.animate(animation1, options.speed).animate(animation, options.speed / 2, function () {
                el.css(props);
            });
        });
    };

}
});
