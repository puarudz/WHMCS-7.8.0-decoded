/*!
 * WHMCS MarketConnect Admin JS Functions
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function() {
    jQuery(document).on('click', '#btnMcServiceRefresh', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        WHMCS.http.jqClient.post({
            url: 'clientsservices.php',
            data: btn.attr('href') + '&token=' + csrfToken,
            success: function (data) {
                $('#mcServiceManagementWrapper').replaceWith(data.statusOutput);
                btn.find('i').removeClass('fa-spin');
            }
        });
    });
    jQuery(document).on('click', '#btnMcCancelOrder', function(e) {
        swal({
            title: 'Are you sure?',
            html: true,
            text: 'Cancelling this order will result in the service immediately ceasing to function.<br><br>You will automatically receive a credit if within the credit period. <a href="https://go.whmcs.com/1281/marketconnect-credit-terms" target="_blank">See credit period terms</a>',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'No'
        },
        function(){
            runModuleCommand('terminate');
        });
    });
    jQuery(document).on('click', '#mcServiceManagementWrapper .btn:not(.open-modal,.btn-refresh,.btn-cancel)', function(e) {
        e.preventDefault();
        $('#growls').fadeOut('fast').remove();
        $('.successbox,.errorbox').slideUp('fast').remove();
        var button = $(this);
        var request = button.attr('href');

        // If button is disabled, don't execute action
        if (button.attr('disabled') == 'disabled') {
            return;
        }

        button.find('i').addClass('fa-spin').addClass('fa-spinner');

        WHMCS.http.jqClient.post('clientsservices.php', request + '&token=' + csrfToken,
            function(data) {

                if (data.redirectUrl) {

                    window.open(data.redirectUrl);

                } else if (data.growl) {

                    if (data.growl.type == 'error') {
                        $.growl.error({ title: '', message: data.growl.message });
                    } else {
                        $.growl.notice({ title: '', message: data.growl.message });
                        $('#btnMcServiceRefresh').click();
                    }

                }

                button.find('i').removeClass('fa-spin').removeClass('fa-spinner');
            }, 'json');
    });
});
