var SMALL_SCREEN_SIZE = 825;

function searchclose() {
    $("#searchresults").slideUp();
}

function sidebarOpen() {
    $("#sidebaropen").fadeOut();
    $("#contentarea").animate({"margin-left":"209px"},1000,function() {
        $("#sidebar").fadeIn("slow");
    });
    WHMCS.http.jqClient.post(whmcsBaseUrl + adminBaseRoutePath + "/search.php","a=maxsidebar");
}
function sidebarClose() {
    $("#sidebar").fadeOut("slow",function(){
        $("#contentarea").animate({"margin-left":"10px"});
        $("#sidebaropen").fadeIn();
    });
    WHMCS.http.jqClient.post(whmcsBaseUrl + adminBaseRoutePath + "/search.php","a=minsidebar");
}
function notesclose(save) {
    $('#myNotes').modal('hide');
    if (save) {
        WHMCS.http.jqClient.post(
            WHMCS.adminUtils.getAdminRouteUrl('/profile/notes'),
            $("#frmmynotes").serialize()
        );
    }
}

$(document).ready(function(){
    var hideSidebarEvents = "load";

    if ((typeof window.orientation === "undefined") && (navigator.userAgent.indexOf('IEMobile') === -1)) {
        // It's not a mobile browser. We can hide sidebar on resize too.

        hideSidebarEvents = hideSidebarEvents + " resize";
    }

    $(window).on(hideSidebarEvents, function() {
        if ($("#sidebar").is(':visible') && $(window).width() <= SMALL_SCREEN_SIZE) {
            $("#sidebar").hide();
            $("#contentarea").css('margin-left', '10px');
            $("#sidebaropen").show();
        }
    });

    var typingTimer,
        intelligentSearching = false;
    $('#inputIntelliSearchValue').keyup(function(){
        clearTimeout(typingTimer);
        var limitField = $('#intelliSearchMoreOf'),
            showMoreOf = limitField.val();

        if (showMoreOf !== '') {
            limitField.val('');
        }
        if ($('#inputIntelliSearchValue').val().replace(/\s/g, '').length >= 3
            && $('#intelliSearchRealtime').is(':checked')) {
            typingTimer = setTimeout(doIntelligentSearch, 750);
        }
    });

    // Intelligent Search Function
    $('#frmIntelligentSearch').submit(function(e) {
        e.preventDefault();
        doIntelligentSearch();
    });

    $(document).on('click', '.search-more-results', function(e) {
        e.preventDefault();
        $(this).hide('fast');

        var limitField = $('#intelliSearchMoreOf'),
            showMoreOf = $(this).data('type');

        if (showMoreOf === 'placeholder') {
            return;
        }

        limitField.val(showMoreOf);
        $(this).remove();
        doIntelligentSearch();
    });

    function doIntelligentSearch() {
        if (intelligentSearching) {
            return;
        }
        intelligentSearching = true;
        var valueVar = $('#inputIntelliSearchValue'),
            value = valueVar.val();
        if (Number(value) === "NaN" && value.replace(/\s/g, '').length < 3) {
            valueVar.tooltip('show');
            return;
        } else {
            valueVar.tooltip('hide');
        }

        var $target = $('#intelligentSearchResults');
        var whmcsAdminBasePath = whmcsBaseUrl + adminBaseRoutePath;

        valueVar.css("background-image","url('" + whmcsAdminBasePath + "/images/loading.gif')");

        if (!$target.is(':visible')) {
            $target.find('.search-results').hide();
            $target.find('.search-in-progress').show();
            $target.slideDown();
        }

        WHMCS.http.jqClient.jsonPost({
            url: $('#frmIntelligentSearch').attr('action'),
            data: $('#frmIntelligentSearch').serialize(),
            success: function(results) {
                var outputClient = function(entity) {
                    var output = entity.client_name;
                    if (entity.client_company_name) {
                        output += ' (' + entity.client_company_name + ')';
                    }
                    output += ' #' + entity.user_id;
                    return output;
                },
                    showMoreOf = $('#intelliSearchMoreOf').val(),
                    outputCount = 0,
                    resultCount = 0,
                    moreClone = $('.search-more-results[data-type="placeholder"]'),
                    cloneRow = null,
                    stringValue = '';

                if (!showMoreOf) {
                    $target.find('ul.clients').empty().end()
                        .find('ul.contacts').empty().end()
                        .find('ul.services').empty().end()
                        .find('ul.domains').empty().end()
                        .find('ul.invoices').empty().end()
                        .find('ul.tickets').empty().end()
                        .find('ul.others').empty().end();
                }

                if (results.client.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.client, function(index, client) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/clientssummary.php?userid=' + client.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-user"></i></span>';
                        searchResult += '<strong>';
                        searchResult += client.name;
                        if (client.company_name) {
                            searchResult += ' (' + client.company_name + ')';
                        }
                        searchResult += '</strong>';
                        searchResult += ' #' + client.id;
                        searchResult += '<span class="label ' + WHMCS.adminUtils.normaliseStringValue(client.status) + '">' + client.status + '</span>';
                        searchResult += '<em>' + client.email + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.clients').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = client.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'clients') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'clients');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.clients').append(cloneRow);
                    }
                }
                if (results.contact.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.contact, function(index, contact) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/clientscontacts.php?userid=' + contact.user_id + '&contactid=' + contact.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-user-tag"></i></span>';
                        searchResult += '<strong>';
                        searchResult += contact.name;
                        if (contact.company_name) {
                            searchResult += ' (' + contact.company_name + ')';
                        }
                        searchResult += '</strong>';
                        searchResult += ' #' + contact.id;
                        searchResult += '<em>' + contact.email + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.contacts').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = contact.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'contacts') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'contacts');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.contacts').append(cloneRow);
                    }
                }
                if (results.service.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.service, function(index, service) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/clientshosting.php?userid=' + service.user_id + '&id=' + service.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-cube"></i></span>';
                        searchResult += '<strong>';
                        searchResult += service.product_name;
                        if (service.domain) {
                            searchResult += ' - ' + service.domain;
                        }
                        searchResult += '</strong>';
                        searchResult += '<span class="label ' + WHMCS.adminUtils.normaliseStringValue(service.status) + '">' + service.status + '</span>';
                        searchResult += '<em>' + outputClient(service) + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.services').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = service.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'services') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'services');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.services').append(cloneRow);
                    }
                }
                if (results.domain.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.domain, function(index, domain) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/clientsdomains.php?userid=' + domain.user_id + '&id=' + domain.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-globe-americas"></i></span>';
                        searchResult += '<strong>';
                        searchResult += domain.domain;
                        searchResult += '</strong>';
                        searchResult += '<span class="label ' + WHMCS.adminUtils.normaliseStringValue(domain.status) + '">' + domain.status + '</span>';
                        searchResult += '<em>' + outputClient(domain) + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.domains').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = domain.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'domains') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'domains');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.domains').append(cloneRow);
                    }
                }
                if (results.invoice.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.invoice, function(index, invoice) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/invoices.php?action=edit&id=' + invoice.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-file-invoice"></i></span>';
                        searchResult += '<strong>';
                        searchResult += 'Invoice #' + invoice.number;
                        searchResult += '</strong>';
                        searchResult += '<span class="label ' + WHMCS.adminUtils.normaliseStringValue(invoice.status) + '">' + invoice.status + '</span>';
                        searchResult += '<em>' + outputClient(invoice) + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.invoices').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = invoice.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'invoices') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'invoices');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.invoices').append(cloneRow);
                    }
                }
                if (results.ticket.length > 0) {
                    outputCount = 0;
                    resultCount = 0;
                    $.each(results.ticket, function(index, ticket) {
                        var searchResult = '<a href="' + whmcsAdminBasePath + '/supporttickets.php?action=view&id=' + ticket.id + '">';
                        searchResult += '<span class="icon"><i class="fal fa-comments"></i></span>';
                        searchResult += '<strong>';
                        searchResult += 'Ticket #' + ticket.mask;
                        searchResult += '</strong>';
                        searchResult += '<em>' + ticket.subject + '</em>';
                        searchResult += '</a>';
                        $target.find('ul.tickets').append('<li>' + searchResult + '</li>').prev('h5').show();
                        outputCount++;
                        if (resultCount === 0) {
                            resultCount = ticket.totalResults;
                        }
                    });
                    if ((!showMoreOf || showMoreOf !== 'tickets') && resultCount > outputCount) {
                        cloneRow = moreClone.clone();
                        cloneRow.attr('data-type', 'tickets');
                        cloneRow.removeClass('hidden').show();
                        stringValue = cloneRow.html();
                        stringValue = stringValue.replace(':count', (resultCount - outputCount).toString());
                        cloneRow.html(stringValue);
                        $target.find('ul.tickets').append(cloneRow);
                    }
                }
                if (results.other.length > 0) {
                    $.each(results.other, function(index, otherResult) {
                        var searchResult = '';
                        if (otherResult instanceof Object) {
                            var icon = 'fal fa-star',
                                linkStart = '',
                                linkEnd = '',
                                subTitle = '';
                            if (otherResult.icon.length) {
                                icon = otherResult.icon;
                            }
                            if (otherResult.href.length) {
                                linkStart = '<a href="' + otherResult.href + '">';
                                linkEnd = '</a>';
                            }
                            if (otherResult.subTitle.length) {
                                subTitle = '<em>' + otherResult.subTitle + '</em>';
                            }
                            searchResult = linkStart;
                            searchResult += '<span class="icon"><i class="' + icon + '"></i></span>';
                            searchResult += '<strong>' + otherResult.title + '</strong>';
                            searchResult +=  subTitle + linkEnd;
                        } else {
                            searchResult = '<span class="icon"><i class="fal fa-star"></i></span>' + otherResult;
                        }
                        $target.find('ul.others').append('<li>' + searchResult + '</li>').prev('h5').show();
                    });
                }

                $target.find('ul').each(function() {
                    if ($(this).find('li').length > 0) {
                        $(this).prev('h5').find('.count').html($(this).find('li').length);
                    } else {
                        $(this).prev('h5').hide();
                    }
                });

                $target.find('.search-result-count').html($target.find('ul li').length);
                if ($target.find('ul li').length === 0) {
                    $target.find('.search-results').hide();
                    $target.find('.session-expired').hide();
                    $target.find('.session-warning').hide();
                    $target.find('.error').hide();
                    if (!$target.find('.search-no-results').is(':visible')) {
                        $target.find('.search-no-results').fadeIn();
                    }
                } else {
                    $target.find('.search-results').show();
                    $target.find('.search-no-results,.session-expired,.search-in-progress,.search-warning,.error').hide();
                }
                $("#inputIntelliSearchValue").css("background-image","url('" + whmcsAdminBasePath + "/images/icons/search.png')");
            },
            warning: function(warningMsg) {
                $target.find('.search-results,.search-in-progress,.search-no-results,.search-more-results,.session-expired').hide();
                $target.find('.search-warning').find('.warning-msg').html(warningMsg).end().fadeIn();
                $("#inputIntelliSearchValue").css("background-image","url('" + whmcsAdminBasePath + "/images/icons/search.png')");
            },
            error: function(errorMsg) {
                $target.find('.search-results,.search-in-progress,.search-no-results,.search-more-results,.session-expired,.search-warning').hide();
                $target.find('.error').fadeIn();
                $("#inputIntelliSearchValue").css("background-image","url('" + whmcsAdminBasePath + "/images/icons/search.png')");
            },
            fail: function(failMsg) {
                $target.find('.search-results,.search-in-progress,.search-no-results,.search-more-results,.error,.search-warning').hide();
                $target.find('.session-expired').fadeIn();
                $("#inputIntelliSearchValue").css("background-image","url('" + whmcsAdminBasePath + "/images/icons/search.png')");
            },
            always: function() {
                intelligentSearching = false;
                $('#intelliSearchMoreOf').val('');
            }
        });
    }

    function closeIntelligentSearch()
    {
        if ($('#intelligentSearchResults').is(':visible')) {
            $('#inputIntelliSearchValue').val('');
            $('#intelligentSearchResults').slideUp();
            $('#intelliSearchMoreOf').val('');
            $('#intelligentSearchResults').find('.search-result-count').html('0');
            clearTimeout(typingTimer);
        }
    }

    $('#intelligentSearchResults .close').click(function(e) {
        closeIntelligentSearch();
    });
    $('#intelligentSearchResults h5').click(function(e) {
        var list = $(this).next('ul');
        if (list.is(':visible')) {
            list.slideUp();
            $(this).find('i').css('transform', 'rotate(180deg)');
        } else {
            list.slideDown();
            $(this).find('i').css('transform', 'rotate(0)');
        }
    });
    $('#intelligentSearchResults .collapse-all').click(function(e) {
        e.preventDefault();
        $('#intelligentSearchResults h5').each(function() {
            $(this).next('ul').slideUp();
            $(this).find('i').css('transform', 'rotate(180deg)');
        });
        $('#intelligentSearchResults .collapse-all').fadeOut('', function(e) {
            $('#intelligentSearchResults .expand-all').hide().removeClass('hidden').fadeIn();
        });
    });
    $('#intelligentSearchResults .expand-all').click(function(e) {
        e.preventDefault();
        $('#intelligentSearchResults h5').each(function() {
            $(this).next('ul').slideDown();
            $(this).find('i').css('transform', '0');
        });
        $('#intelligentSearchResults .expand-all').fadeOut('', function(e) {
            $('#intelligentSearchResults .collapse-all').fadeIn();
        });
    });
    $('#intelliSearchRealtime').bootstrapSwitch()
        .on('switchChange.bootstrapSwitch', function(event, state) {
            WHMCS.http.jqClient.post($(this).data('url'), 'token=' + csrfToken + '&autosearch=' + state);
        });
    $('#intelliSearchHideInactiveSwitch').bootstrapSwitch()
        .on('switchChange.bootstrapSwitch', function(event, state) {
            var valueOfField = state ? 1 : 0;
            $('#intelliSearchHideInactive').attr('value', valueOfField);
            doIntelligentSearch();
        });

    $(document).keyup(function(e) {
        if (e.keyCode === 27) {
            closeIntelligentSearch();
        }
    });

    // This function was deprecated in WHMCS 7.7 and will be removed in a future version.
    $("#frmintellisearch").submit(function(e) {
        e.preventDefault();
        $("#intellisearchval").css("background-image","url('images/loading.gif')");
        WHMCS.http.jqClient.post(whmcsBaseUrl + adminBaseRoutePath + "/search.php", $("#frmintellisearch").serialize(),
        function(data){
            $("#searchresultsscroller").html(data);
            $("#searchresults").slideDown("slow",function(){
                    $("#intellisearchval").css("background-image","url('" + whmcsBaseUrl + adminBaseRoutePath + "/images/icons/search.png')");
                });
        });
    });

    $('div.modal').on('shown.bs.modal', function() {
        var inputs = $(this).find('input,button.btn-primary');

        if (inputs.length > 0) {
            $(inputs).first().focus();
        }
    });

    $('#btnClientLimitNotificationDismiss').click(function(e) {
        e.preventDefault();
        $('#clientLimitNotification').fadeOut();
        WHMCS.http.jqClient.post(window.location.href, 'clientlimitdismiss=1&name=' + $('#clientLimitNotification').find('.panel-title span').html());
    });

    $('#btnClientLimitNotificationDontShowAgain').click(function(e) {
        e.preventDefault();
        $('#clientLimitNotification').fadeOut();
        WHMCS.http.jqClient.post(window.location.href, 'clientlimitdontshowagain=1&name=' + $('#clientLimitNotification').find('.panel-title span').html());
    });

    $('.client-limit-notification-form form').submit(function(e) {
        e.preventDefault();
        var $this = $(this);
        var $fetchUrl = $this.data('fetchUrl');
        var $submit = $this.find('button[type="submit"]');
        var $submitLabel = $submit.html();
        $submit.css('width', $submit.css('width')).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        WHMCS.http.jqClient.post($fetchUrl, $this.serialize(),
            function(data) {
                $this.find('.input-license-key').val(data.license_key);
                $this.find('.input-member-data').val(data.member_data);
                $this.off('submit').submit();
                $submit.html($submitLabel).removeProp('disabled');
            }, 'json');
    });
});
