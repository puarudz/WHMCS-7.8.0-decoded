function doEmailCreate() {
    jQuery("#btnCreateLoader").addClass('fa-spinner fa-spin').removeClass('fa-plus');
    jQuery("#emailCreateSuccess").slideUp();
    jQuery("#emailCreateFailed").slideUp();
    WHMCS.http.jqClient.post(
        "clientarea.php",
        "action=productdetails&modop=custom&a=CreateEmailAccount&" + jQuery("#frmCreateEmailAccount").serialize(),
        function( data ) {
            jQuery("#btnCreateLoader").removeClass('fa-spinner fa-spin').addClass('fa-plus');
            if (data.success) {
                jQuery("#emailCreateSuccess").hide().removeClass('hidden')
                    .slideDown();
            } else {
                jQuery("#emailCreateFailedErrorMsg").html(data.errorMsg);
                jQuery("#emailCreateFailed").hide().removeClass('hidden')
                    .slideDown();
            }
        }
    );
}
