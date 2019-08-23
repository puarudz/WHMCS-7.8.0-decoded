<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($errorMsg) {
    echo "    <div class=\"alert alert-danger\">\n        ";
    echo $errorMsg;
    echo "    </div>\n";
}
echo "\n<div class=\"form-group\">\n    <label for=\"inputPrimaryList\">Primary Subscription List</label>\n    <select name=\"primary_list\" class=\"form-control\" id=\"inputPrimaryList\">\n        ";
if (0 < count($lists)) {
    echo "            ";
    foreach ($lists as $list) {
        echo "                <option value=\"";
        echo $list["id"];
        echo "-";
        echo $list["name"];
        echo "\"";
        echo $primaryList == $list["id"] ? " selected" : "";
        echo ">\n                    ";
        echo $list["name"];
        echo "                </option>\n            ";
    }
    echo "            <option value=\"\">---</option>\n            <option value=\"new\"";
    echo $primaryList == "new" ? " selected" : "";
    echo ">Create new list</option>\n        ";
} else {
    echo "            <option value=\"new\">Create new list</option>\n        ";
}
echo "    </select>\n    <p class=\"help-block\">Choose the primary list you wish to subscribe users to. E-commerce integration will be configured for use with this list. We recommend creating a new list.</p>\n</div>\n<div id=\"inputsNewList\"";
echo $primaryList != "new" ? " class=\"hidden\"" : "";
echo ">\n    <div class=\"form-group\">\n        <label for=\"inputNewListName\">New List Name</label>\n        <input type=\"text\" name=\"new_list_name\" class=\"form-control\" id=\"inputNewListName\" value=\"";
echo $newListName;
echo "\">\n        <p class=\"help-block\">The name for the new list to be created.</p>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputNewListName\">Default from email address</label>\n        <input type=\"text\" name=\"from_email\" class=\"form-control\" id=\"inputNewListName\" value=\"";
echo $fromEmail;
echo "\">\n        <p class=\"help-block\">This is the address people will reply to.</p>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputNewListName\">Default from name</label>\n        <input type=\"text\" name=\"from_name\" class=\"form-control\" id=\"inputNewListName\" value=\"";
echo $fromName;
echo "\">\n        <p class=\"help-block\">This is who your emails will come from. Use something your customers will recognise.</p>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputNewListName\">Permission Reminder</label>\n        <input type=\"text\" name=\"permission_reminder\" class=\"form-control\" id=\"inputNewListName\" value=\"";
echo $permissionReminder;
echo "\">\n        <p class=\"help-block\">A short reminder about why the recipient of emails is subscribed to this list. Example: \"You are receiving this email because you registered on our website.\"</p>\n    </div>\n    <div class=\"form-group form-group-company\">\n        <label for=\"inputNewListName\">Contact Information</label>\n        <input type=\"text\" name=\"contact_company\" class=\"form-control\" id=\"inputNewListName\" placeholder=\"Company Name\" value=\"";
echo $contactCompany;
echo "\">\n        <input type=\"text\" name=\"contact_addr1\" class=\"form-control\" id=\"inputNewListName\" placeholder=\"Address Line 1\" value=\"";
echo $contactAddr1;
echo "\">\n        <input type=\"text\" name=\"contact_city\" class=\"form-control\" id=\"inputNewListName\" placeholder=\"City\" value=\"";
echo $contactCity;
echo "\">\n        <input type=\"text\" name=\"contact_state\" class=\"form-control\" id=\"inputNewListName\" placeholder=\"State\" value=\"";
echo $contactState;
echo "\">\n        <input type=\"text\" name=\"contact_zip\" class=\"form-control\" id=\"inputNewListName\" placeholder=\"ZIP Code\" value=\"";
echo $contactZip;
echo "\">\n        <select name=\"contact_country\" class=\"form-control\">\n            ";
foreach ($countries as $code => $displayName) {
    echo "                <option value=\"";
    echo $code;
    echo "\"";
    echo $code == $contactCountry ? " selected" : "";
    echo ">";
    echo $displayName;
    echo "</option>\n            ";
}
echo "        </select>\n        <p class=\"help-block\">Contact information displayed in campaign footers to comply with international spam laws.</p>\n    </div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\">Continue</button>\n\n<input type=\"hidden\" name=\"action\" value=\"validateprimarylist\">\n\n<script>\n\$(document).ready(function(e) {\n    \$('#inputPrimaryList').change(function(e) {\n        if (\$(this).val() == 'new') {\n            \$('#inputsNewList').hide().removeClass('hidden').slideDown();\n        } else {\n            \$('#inputsNewList').slideUp();\n        }\n    });\n})\n</script>\n";

?>