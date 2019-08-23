$(document).ready(function(){
    var minimisedWidgets = null;
    if(typeof(Storage) !== "undefined") {
        minimisedWidgets = JSON.parse(localStorage.getItem("minimisedWidgets"));
    }
    if (!minimisedWidgets) {
        minimisedWidgets = [];
    }
    $(".widget-minimise").click(function(e) {
        e.preventDefault();
        var obj = $(this);
        var icon = obj.find('i'),
            widget = obj.closest('.panel').data('widget');
        if (icon.hasClass('fa-chevron-up')) {
            obj.closest('.panel').find('.panel-body').slideUp('fast', function() {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                packery.shiftLayout();
            });
            if (minimisedWidgets.indexOf(widget) == -1) {
                minimisedWidgets.push(widget);
            }
        } else {
            obj.closest('.panel').find('.panel-body').slideDown('fast', function(e) {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                packery.fit(this);
                packery.shiftLayout();
            });
            minimisedWidgets.splice(minimisedWidgets.indexOf(widget), 1);
        }
        if(typeof(Storage) !== "undefined") {
            localStorage.setItem("minimisedWidgets", JSON.stringify(minimisedWidgets));
        }
    });
    $(".widget-refresh").click(function(e) {
        e.preventDefault();
        var obj = $(this);
        var icon = obj.find('i');
        var widget = obj.closest('.panel').data('widget');
        var panelBody = obj.closest('.panel').find('.panel-body');
        icon.addClass('fa-spin');
        refreshWidget(widget, 'refresh=1');
    });
    var completedToggle = false;
    $(".widget-hide").click(function(e) {
        e.preventDefault();
        var obj = $(this),
            widget = obj.closest('.panel').data('widget');
        completedToggle = true;

        $('#panel' + widget).slideUp('fast', function() {
            $(this).addClass('hidden');
            WHMCS.http.jqClient.post(WHMCS.adminUtils.getAdminRouteUrl('/widget/display/toggle/' + widget)).always(function() {
                $('input[data-widget="' + widget + '"]').iCheck('uncheck');
                completedToggle = false;
            });
            $('.home-widgets-container').masonry().masonry('reloadItems');
        });
    });

    $(document).on('ifToggled', '.display-widget', function(event) {
        var self = $(this),
            widget = $(this).data('widget'),
            widgetPanel = $('#panel' + widget);

        if (completedToggle) {
            return;
        }

        self.iCheck('disable');
        if (self.prop('checked')) {
            if (widgetPanel.hasClass('hidden')) {
                self.parent('div').parent('label').parent('li').addClass('active');
                widgetPanel.hide().removeClass('hidden').slideDown('fast', function() {
                    WHMCS.http.jqClient.post(WHMCS.adminUtils.getAdminRouteUrl('/widget/display/toggle/' + widget))
                    .always(function() {
                        $('.home-widgets-container').masonry().masonry('reloadItems');
                        widgetPanel.find('.widget-refresh').click();
                        if ($('#widgetSettingsDropdown').hasClass('open') === false) {
                            $('#widgetSettings').dropdown('toggle');
                        }
                        self.iCheck('enable');
                    });
                });
            }
        } else {
            if (widgetPanel.hasClass('hidden') === false) {
                self.parent('div').parent('label').parent('li').removeClass('active');
                widgetPanel.slideUp('fast', function() {
                    $(this).addClass('hidden');
                    $('.home-widgets-container').masonry().masonry('reloadItems');
                    WHMCS.http.jqClient.post(WHMCS.adminUtils.getAdminRouteUrl('/widget/display/toggle/' + widget), function() {
                        if ($('#widgetSettingsDropdown').hasClass('open') === false) {
                            $('#widgetSettings').dropdown('toggle');
                        }
                    }, 'json').always(function() {
                        self.iCheck('enable');
                    });
                });
            }
        }
    });

    $('input.display-widget').each(function(){
        var self = $(this),
            label = self.next(),
            label_text = label.text();

        label.remove();
        self.iCheck({
            inheritID: true,
            checkboxClass: 'icheckbox_flat-blue',
            increaseArea: '20%'
        });
    });

    if ($('.home-widgets-container').length) {
        minimisedWidgets.forEach(function(currentValue) {
            $('#panel' + currentValue).find('.panel-body').hide().end()
                .find('i.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

        Packery.prototype.getPositions = function() {
            return this.items.map(function(item) {
                return item.element.getAttribute("data-widget")
            });
        };

        // init Packery
        grid = document.querySelector('.home-widgets-container'),
        packery = new Packery(grid, {
            itemSelector: '.dashboard-panel-item',
            columnWidth: '.dashboard-panel-sizer',
            percentPosition: true
        });

        packery.stamp(document.querySelector('.dashboard-panel-static-item'));

        // init draggable
        var items = grid.querySelectorAll('.dashboard-panel-item');
        for (var i=0; i < items.length; i++) {
            var itemElem = items[i],
                draggie = new Draggabilly(itemElem, {handle: '.panel-title'} );
            packery.bindDraggabillyEvents(draggie);
        }

        // Listeners

        packery.on('removeComplete', function() {
            packery.shiftLayout();
        });

        var isSaving = false;
        packery.on('dragItemPositioned', function(items) {
            packery.shiftLayout();
            if (!$(".home-widgets-container").children("div.dashboard-panel-item").hasClass('is-dragging')){
                if (!isSaving) {
                    isSaving = true;
                    setTimeout(function () {
                        saveWidgetPosition();
                    }, 1000);
                }
            }
        });
    }

    function saveWidgetPosition() {
        WHMCS.http.jqClient.post(WHMCS.adminUtils.getAdminRouteUrl('/widget/order'),
            {
                token: csrfToken,
                order: packery.getPositions()
            },
            function(data) {
                //do nothing
            },
            'json'
        ).always(function() {
            isSaving = false;
            packery.shiftLayout();
        });
    }
    //end of $(document).ready
});

var grid, packery;

function refreshWidget(widgetName, requestString) {
    var obj = $('.panel[data-widget="' + widgetName + '"]');
    var panelBody = obj.find('.panel-body');
    var icon = obj.find('i.fa-sync');
    panelBody.addClass('panel-loading');
    var jqxhr = WHMCS.http.jqClient.post(WHMCS.adminUtils.getAdminRouteUrl('/widget/refresh&widget=' + widgetName + '&' + requestString),
        function(data) {
            panelBody.html(data.widgetOutput);
            panelBody.removeClass('panel-loading');
        }, 'json')
        .always(function() {
            icon.removeClass('fa-spin');
        });
}
