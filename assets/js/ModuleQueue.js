/*!
 * WHMCS Module Queue Javascript Functions
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2016
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function() {
    var moduleQueueRetryAll = jQuery('button.retry-all');
    if (moduleQueueRetryAll.length) {
        var processed = false,
            queueTimeout = null,
            count = 0;

        jQuery('button.retry').click(function() {
            processed = false;
            var self = jQuery(this),
                entryId = jQuery(this).data('entry-id'),
                processingEntry = jQuery('div#processing-entry-' + entryId);

            self.attr('disabled', 'disabled').addClass('disabled').find('i').addClass('fa-spin').end();
            if (queueTimeout) {
                processingEntry.find('div.queued').hide().end()
                    .find('div.processing').show().end();
            } else {
                processingEntry.find('div.messages').children('div').hide().end()
                    .find('div.processing').show().end().end()
                    .hide().removeClass('hidden').slideDown('fast');
            }
            var connection = WHMCS.http.jqClient.post(
                window.location.pathname,
                {
                    token: csrfToken,
                    action: 'retry',
                    id: entryId
                },
                null,
                'json'
            );

            connection.done(function(data) {
                if (data.error) {
                    processingEntry.find('div.processing').hide().end()
                        .find('div.error').find('span').html(data.message).parent().show().end();
                    jQuery('#last-error-' + entryId).html(data.errorMessage);
                    jQuery('div#entry-' + entryId).find('small.last-attempt').find('span').html(data.lastAttempt);
                    self.removeAttr('disabled').removeClass('disabled').find('i').removeClass('fa-spin').end();
                    count++;
                }
                if (data.completed) {
                    jQuery('div#entry-' + entryId).find('div.action-buttons').find('button').removeClass('retry')
                        .attr('disabled', 'disabled').addClass('disabled')
                        .find('i.fa-spin').removeClass('fa-spin').end();
                    processingEntry.find('div.processing').slideUp('fast').end()
                        .find('div.success').slideDown('fast').end();
                }
            });

            connection.always(function() {
                processed = true;
            });
        });

        jQuery('button.resolve').click(function() {
            var self = jQuery(this),
                entryId = jQuery(this).data('entry-id'),
                processingEntry = jQuery('div#processing-entry-' + entryId);

            self.attr('disabled', 'disabled').addClass('disabled');

            processingEntry.find('div.messages').children('div').hide().end()
                .find('div.processing').show().end().end()
                .hide().removeClass('hidden').slideDown('fast');

            var connection = WHMCS.http.jqClient.post(
                window.location.pathname,
                {
                    token: csrfToken,
                    action: 'resolve',
                    id: entryId
                },
                null,
                'json'
            );

            connection.done(function(data) {
                if (data.completed) {
                    jQuery('div#entry-' + entryId).find('div.action-buttons').find('button').removeClass('retry')
                        .attr('disabled', 'disabled').addClass('disabled').end();
                    processingEntry.find('div.processing').slideUp('fast').end()
                        .find('div.success').find('span').html(data.message).parent().slideDown('fast').end();
                } else {
                    processingEntry.find('div.processing').slideUp('fast').end()
                        .find('div.error').find('span').html(data.message).parent().slideDown('fast').end();
                    self.removeAttr('disabled').removeClass('disabled');
                }

            });
        });

        moduleQueueRetryAll.click(function () {
            jQuery(this).attr('disabled', 'disabled').addClass('disabled')
                .find('i').addClass('fa-spin').end();
            var items = jQuery('button.retry');
            processed = true;
            count = 0;

            items.each(function(index) {
                var entryId = jQuery(this).data('entry-id');
                jQuery('div#processing-entry-' + entryId).find('div.messages').children('div').hide().end()
                    .find('div.queued').show().end().end()
                    .hide().removeClass('hidden').slideDown('fast');
            });

            queueTimeout = setTimeout(nextClick, 1000);
        });

        function nextClick()
        {
            if (processed) {
                var button = jQuery('button.retry:eq(' + count + ')');
                if (button.length) {
                    button.click();
                } else {
                    clearTimeout(queueTimeout);
                    queueTimeout = null;
                    moduleQueueRetryAll.removeAttr('disabled').removeClass('disabled')
                        .find('i').removeClass('fa-spin').end();
                    return;
                }
            }
            queueTimeout = setTimeout(nextClick, 1000);
        }
    }
});
