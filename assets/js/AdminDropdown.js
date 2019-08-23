/*!
 * WHMCS Dynamic Dropdown Library
 *
 * Based upon Selectize.js
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2016
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

jQuery(document).ready(
    function()
    {
        var multiSelectize = jQuery('.selectize-multi-select'),
            standardSelectize = jQuery('.selectize-select'),
            tags = jQuery('.selectize-tags'),
            currentValue = '';

        jQuery(multiSelectize).selectize(
            {
                plugins: ['remove_button'],
                valueField: jQuery(multiSelectize).attr('data-value-field'),
                labelField: 'name',
                searchField: 'name',
                allowEmptyOption: true,
                create: false,
                maxItems: null,
                render: {
                    item: function(item, escape) {
                        return '<div><span class="name">' + escape(item.name) + '</span></div>';
                    },
                    option: function(item, escape) {
                        return '<div><span class="name">' + escape(item.name) + '</span></div>';
                    }
                },
                onItemRemove: function(value) {
                    if (jQuery(this)[0].$input[0].id == 'multi-view' && value != 'any' && value != 'flagged') {
                        jQuery(this)[0].removeItem('any', true);
                    }
                }
            }
        );

        jQuery(standardSelectize).selectize(
            {
                valueField: jQuery(standardSelectize).attr('data-value-field'),
                labelField: 'name',
                searchField: 'name',
                allowEmptyOption: jQuery(standardSelectize).attr('data-allow-empty-option'),
                create: false,
                maxItems: 1,
                render: {
                    item: function(item, escape) {
                        var colour = '';
                        if (typeof item.colour !== 'undefined' && item.colour !== '#FFF') {
                            colour = ' style="background-color: ' + escape(item.colour) + ';"';
                        }
                        return '<div' + colour + '><span class="name">' + escape(item.name) + '</span></div>';
                    },
                    option: function(item, escape) {
                        var colour = '';
                        if (typeof item.colour !== 'undefined' && item.colour !== '#FFF') {
                            colour = ' style="background-color: ' + escape(item.colour) + ';"';
                        }
                        return '<div' + colour + '><span class="name">' + escape(item.name) + '</span></div>';
                    }
                },
                onFocus: function() {
                    currentValue = this.getValue();
                    this.clear();
                },
                onBlur: function()
                {
                    if (this.getValue() == '') {
                        this.setValue(currentValue);
                    }
                    if (
                        jQuery(standardSelectize).hasClass('selectize-auto-submit')
                        && currentValue !== this.getValue()
                    ) {
                        this.setValue(this.getValue());
                        jQuery(standardSelectize).parent('form').submit();
                    }
                }
            }
        );

        jQuery(tags).selectize(
            {
                plugins: ['remove_button'],
                valueField: 'text',
                searchField: ['text'],
                delimiter: ',',
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    }
                },
                render: {
                    item: function(item, escape) {
                        return '<div><span class="item">' + escape(item.text) + '</span></div>';
                    },
                    option: function(item, escape) {
                        return '<div><span class="item">' + escape(item.text) + '</span></div>';
                    }
                },
                load: function(query, callback) {
                    if (!query.length) return callback();
                    jQuery.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'gettags',
                            q: query,
                            token: csrfToken
                        },
                        error: function() {
                            callback();
                        },
                        success: function(res) {
                            callback(res);
                        }
                    });
                },
                onItemAdd: function (value)
                {
                    jQuery.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: {
                            action: 'addTag',
                            newTag: value,
                            token: csrfToken
                        }
                    });

                },
                onItemRemove: function(value)
                {
                    jQuery.ajax(
                        {
                            url: window.location.href,
                            type: 'POST',
                            data: {
                                action: 'removeTag',
                                removeTag: value,
                                token: csrfToken
                            }
                        }
                    );
                }
            }
        );
    }
);
