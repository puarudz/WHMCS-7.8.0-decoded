jQuery(document).ready(function(){
    if(typeof window.langPasswordWeak === 'undefined'){
        window.langPasswordWeak = "Weak";
    }
    if(typeof window.langPasswordModerate === 'undefined'){
        window.langPasswordModerate = "Moderate";
    }
    if(typeof window.langPasswordStrong === 'undefined'){
        window.langPasswordStrong = "Strong";
    }

    jQuery("#newpw").keyup(function () {
        var pwvalue = jQuery("#newpw").val();
        var pwstrength = getPasswordStrength(pwvalue);
        jQuery("#pwstrength").html(langPasswordStrong);
        jQuery("#pwstrengthpos").css("background-color","#33CC00");

        var errorThreshold = !isNaN(parseInt(jQuery(this).data('error-threshold'))) ? jQuery(this).data('error-threshold') : 50;
        var warningThreshold = !isNaN(parseInt(jQuery(this).data('warning-threshold'))) ? jQuery(this).data('warning-threshold') : 75;

        if (pwstrength<warningThreshold) {
            jQuery("#pwstrength").html(langPasswordModerate);
            jQuery("#pwstrengthpos").css("background-color","#ff6600");
        }
        if (pwstrength<errorThreshold) {
            jQuery("#pwstrength").html(langPasswordWeak);
            jQuery("#pwstrengthpos").css("background-color","#cc0000");
        }
        jQuery("#pwstrengthpos").css("width",pwstrength);
        jQuery("#pwstrengthneg").css("width",100-pwstrength);
    });
});

function registerFormPasswordStrengthFeedback()
{
    passwordStrength = getPasswordStrength(jQuery(this).val());

    var errorThreshold = !isNaN(parseInt(jQuery(this).data('error-threshold'))) ? jQuery(this).data('error-threshold') : 50;
    var warningThreshold = !isNaN(parseInt(jQuery(this).data('warning-threshold'))) ? jQuery(this).data('warning-threshold') : 75;

    if (passwordStrength >= warningThreshold) {
        textLabel = langPasswordStrong;
        cssClass = 'success';
    } else if (passwordStrength >= errorThreshold) {
        textLabel = langPasswordModerate;
        cssClass = 'warning';
    } else {
        textLabel = langPasswordWeak;
        cssClass = 'danger';
    }
    jQuery("#passwordStrengthTextLabel").html(langPasswordStrength + ': ' + passwordStrength + '% ' + textLabel);
    jQuery("#passwordStrengthMeterBar").css('width', passwordStrength + '%').attr('aria-valuenow', passwordStrength);
    jQuery("#passwordStrengthMeterBar").removeClass('progress-bar-success progress-bar-warning progress-bar-danger').addClass('progress-bar-' + cssClass);
}

function getPasswordStrength(pw){
    var pwlength=(pw.length);
    if(pwlength>5)pwlength=5;
    var numnumeric=pw.replace(/[0-9]/g,"");
    var numeric=(pw.length-numnumeric.length);
    if(numeric>3)numeric=3;
    var symbols=pw.replace(/\W/g,"");
    var numsymbols=(pw.length-symbols.length);
    if(numsymbols>3)numsymbols=3;
    var numupper=pw.replace(/[A-Z]/g,"");
    var upper=(pw.length-numupper.length);
    if(upper>3)upper=3;
    var pwstrength=((pwlength*10)-20)+(numeric*10)+(numsymbols*15)+(upper*10);
    if(pwstrength<0){pwstrength=0}
    if(pwstrength>100){pwstrength=100}
    return pwstrength;
}

function showStrengthBar() {
    if(typeof window.langPasswordStrength === 'undefined'){
        window.langPasswordStrength = "Password Strength";
    }
    if(typeof window.langPasswordWeak === 'undefined'){
        window.langPasswordWeak = "Weak";
    }
    document.write('<table align="center" style="width:auto;"><tr><td>'+langPasswordStrength+':</td><td><div id="pwstrengthpos" style="position:relative;float:left;width:0px;background-color:#33CC00;border:1px solid #000;border-right:0px;">&nbsp;</div><div id="pwstrengthneg" style="position:relative;float:left;width:100px;background-color:#efefef;border:1px solid #000;border-left:0px;">&nbsp;</div></td><td><div id="pwstrength">'+langPasswordWeak+'</div></td></tr></table>');
}
