jQuery(document).ready(function() {
    var spinner = jQuery('#withSelectedSpinner'),
        selectedTickets = [];
    spinner.hide();

    WHMCS.ui.dataTable.getTableById(
        'tblClientTickets',
        {
            'dom': '<"listtable"><"row"<"col-md-6"i><"col-md-6"f><"col-md-12"t><"col-md-6"l><"col-md-6"p>>',
            'aoColumnDefs': [
                {
                    'bSortable': false,
                    'aTargets': [0, 1, 3]
                }
            ],
            'order': [5, 'desc']
        }
    );

    jQuery('#checkAllTickets').click(function (event) {
        var checked = this.checked;
        jQuery(event.target).parents('.datatable').find('input.ticket-checkbox:visible').each(function () {
            jQuery(this).prop('checked', checked);
            jQuery(this).trigger('change');
        });
    });

    jQuery(document).on('change', '.ticket-checkbox', function () {
        if (jQuery(this).is(':checked')) {
            selectedTickets.push(parseInt(jQuery(this).val()));
        } else {
            selectedTickets.splice(selectedTickets.indexOf(parseInt(jQuery(this).val())), 1);
        }
    });

    jQuery(document).on('click', '#ticketsClose,#ticketsDelete,#ticketsMerge', function (event)
    {
        event.preventDefault();
        var type = jQuery(this).attr('id'),
            name = eval(type);

        if (selectedTickets.length === 0) {
            swal({
                title: missingSelections.title,
                html: true,
                text: missingSelections.text,
                type: missingSelections.type,
                confirmButtonText: missingSelections.confirmButtonText
            });
        } else if (type === 'ticketsMerge' && selectedTickets.length === 1) {
            swal({
                title: mergeError.title,
                html: true,
                text: mergeError.text,
                type: mergeError.type,
                confirmButtonText: mergeError.confirmButtonText
            });
        } else {
            var btnDropdown = jQuery('#btnTicketsWithSelected');
            swal(
                {
                    title: name.title,
                    html: true,
                    text: name.text,
                    type: name.type,
                    showCancelButton: true,
                    confirmButtonText: name.confirmButtonText,
                    cancelButtonText: name.cancelButtonText
                },
                function() {
                    btnDropdown.prop('disabled', true).addClass('disabled');
                    spinner.fadeIn('fast');
                    WHMCS.http.jqClient.post(
                        name.url,
                        {
                            token: csrfToken,
                            ticketIds: selectedTickets
                        }
                    ).always(function(data) {
                        window.location.reload();
                    });
                }
            );
        }

    });
});
