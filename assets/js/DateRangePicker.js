/*!
 * DateRangePicker Javascript.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function(){
    // Date range picker.
    jQuery('.date-picker-search').each(function (index) {
        var self = jQuery(this),
            opens = self.data('opens'),
            drops = self.data('drops'),
            range = adminJsVars.dateRangePicker.defaultRanges,
            format = adminJsVars.dateRangeFormat;
        if (!opens || typeof opens === "undefined") {
            opens = 'center';
        }
        if (!drops || typeof drops === "undefined") {
            drops = 'down';
        }
        if (self.hasClass('future')) {
            range = adminJsVars.dateRangePicker.futureRanges;
        }
        self.daterangepicker({
            autoUpdateInput: false,
            ranges: range,
            alwaysShowCalendars: true,
            opens: opens,
            drops: drops,
            showDropdowns: true,
            minYear: adminJsVars.minYear,
            maxYear: adminJsVars.maxYear,
            locale: {
                format: format,
                applyLabel: adminJsVars.dateRangePicker.applyLabel,
                cancelLabel: adminJsVars.dateRangePicker.cancelLabel,
                customRangeLabel: adminJsVars.dateRangePicker.customRangeLabel,
                monthNames: adminJsVars.dateRangePicker.months,
                daysOfWeek: adminJsVars.dateRangePicker.daysOfWeek
            }
        }).on('apply.daterangepicker', function(ev, picker) {
            jQuery(this).val(picker.startDate.format(adminJsVars.dateRangeFormat)
                + ' - ' + picker.endDate.format(adminJsVars.dateRangeFormat));
        }).on('cancel.daterangepicker', function(ev, picker) {
            jQuery(this).val('');
        });
    });

    jQuery('.datepick,.date-picker,.date-picker-single').each(function (index) {
        var self = jQuery(this),
            opens = self.data('opens'),
            drops = self.data('drops'),
            range = adminJsVars.dateRangePicker.defaultSingleRanges,
            format = adminJsVars.dateRangeFormat,
            time = false;
        if (!opens || typeof opens === "undefined") {
            opens = 'center';
        }
        if (!drops || typeof drops === "undefined") {
            drops = 'down';
        }
        if (self.hasClass('future')) {
            range = adminJsVars.dateRangePicker.futureSingleRanges;
        }
        if (self.hasClass('time')) {
            time = true;
            format = adminJsVars.dateTimeRangeFormat;
            if (self.hasClass('future')) {
                range = adminJsVars.dateRangePicker.futureTimeSingleRanges;
            }
        }
        self.daterangepicker({
            singleDatePicker: true,
            autoUpdateInput: false,
            ranges: range,
            alwaysShowCalendars: true,
            opens: opens,
            drops: drops,
            showDropdowns: true,
            minYear: adminJsVars.minYear,
            maxYear: adminJsVars.maxYear,
            timePicker: time,
            timePickerSeconds: false,
            locale: {
                format: format,
                customRangeLabel: adminJsVars.dateRangePicker.customRangeLabel,
                monthNames: adminJsVars.dateRangePicker.months,
                daysOfWeek: adminJsVars.dateRangePicker.daysOfWeek
            }
        }).on('apply.daterangepicker', function(ev, picker) {
            jQuery(this).data('original-value', picker.startDate.format(format))
                .val(picker.startDate.format(format));
        }).on('cancel.daterangepicker', function(ev, picker) {
            jQuery(this).val(jQuery(this).data('original-value'));
        });
    });
});
