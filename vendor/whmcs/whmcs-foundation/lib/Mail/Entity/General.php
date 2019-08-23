<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail\Entity;

class General extends \WHMCS\Mail\Emailer
{
    protected function getEntitySpecificMergeData($userId, $extra)
    {
        if (in_array($this->message->getTemplateName(), array("Quote Delivery with PDF"))) {
            $firstname = $this->getExtra("client_first_name");
            $lastname = $this->getExtra("client_last_name");
            $companyname = $this->getExtra("client_company_name");
            $email = $this->getExtra("client_email");
            $name = $firstname . " " . $lastname;
            if ($userId) {
                $this->setRecipient($userId);
                $this->isNonClientEmail = false;
            } else {
                $this->message->addRecipient("to", $email, $name);
                $this->isNonClientEmail = true;
            }
            $email_merge_fields = array();
            $email_merge_fields["quote_number"] = $this->getExtra("quote_number");
            $email_merge_fields["quote_subject"] = $this->getExtra("quote_subject");
            $email_merge_fields["quote_date_created"] = $this->getExtra("quote_date_created");
            $email_merge_fields["quote_valid_until"] = $this->getExtra("quote_valid_until");
            $email_merge_fields["client_name"] = $name;
            $email_merge_fields["client_first_name"] = $firstname;
            $email_merge_fields["client_last_name"] = $lastname;
            $email_merge_fields["client_name"] = $name;
            $email_merge_fields["client_company_name"] = $companyname;
            $email_merge_fields["client_email"] = $email;
            $email_merge_fields["client_id"] = $this->getExtra("client_id");
            $email_merge_fields["client_address1"] = $this->getExtra("client_address1");
            $email_merge_fields["client_address2"] = $this->getExtra("client_address2");
            $email_merge_fields["client_city"] = $this->getExtra("client_city");
            $email_merge_fields["client_state"] = $this->getExtra("client_state");
            $email_merge_fields["client_postcode"] = $this->getExtra("client_postcode");
            $email_merge_fields["client_country"] = $this->getExtra("client_country");
            $email_merge_fields["client_phonenumber"] = $this->getExtra("client_phonenumber");
            $email_merge_fields["client_language"] = $this->getExtra("client_language");
            $email_merge_fields["client_phonenumber"] = $this->getExtra("client_phonenumber");
            $email_merge_fields["quote_link"] = $this->getExtra("quote_link");
            $this->massAssign($email_merge_fields);
            $this->message->addStringAttachment(\Lang::trans("quotefilename") . $this->getExtra("quote_number") . ".pdf", $this->getExtra("quoteattachmentdata"));
        } else {
            $this->setRecipient($userId);
        }
    }
}

?>