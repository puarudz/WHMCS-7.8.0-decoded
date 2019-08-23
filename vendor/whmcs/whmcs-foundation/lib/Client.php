<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Client
{
    protected $userid = "";
    protected $clientModel = NULL;
    public function __construct($user)
    {
        if ($user instanceof User\Client) {
            $this->clientModel = $user;
            $this->setID($user->id);
        } else {
            $this->setID($user);
            $this->clientModel = User\Client::find($this->getID());
        }
        return $this;
    }
    public function getClientModel()
    {
        return $this->clientModel;
    }
    public function setID($userid)
    {
        $this->userid = (int) $userid;
    }
    public function getID()
    {
        return $this->userid;
    }
    public function getUneditableClientProfileFields()
    {
        global $whmcs;
        return explode(",", $whmcs->get_config("ClientsProfileUneditableFields"));
    }
    public function isEditableField($field)
    {
        $uneditablefields = defined("CLIENTAREA") ? $this->getUneditableClientProfileFields() : array();
        return !in_array($field, $uneditablefields) ? true : false;
    }
    public static function formatPhoneNumber($details)
    {
        $phone = trim($details["phonenumber"]);
        $phonePrefix = "";
        if (substr($phone, 0, 1) == "+") {
            $phoneParts = explode(".", ltrim($phone, "+"), 2);
            if (count($phoneParts) == 2) {
                list($phonePrefix, $phoneNumber) = $phoneParts;
            } else {
                $phoneNumber = $phoneParts[0];
            }
        } else {
            $phoneNumber = $phone;
        }
        $phonePrefix = preg_replace("/[^0-9]/", "", $phonePrefix);
        $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
        $countries = new Utility\Country();
        if (!$phonePrefix) {
            $phonePrefix = $countries->getCallingCode($details["countrycode"]);
        }
        $trimmedPhoneNumber = $phoneNumber;
        if ($phonePrefix != $countries->getCallingCode("IT")) {
            $trimmedPhoneNumber = ltrim($trimmedPhoneNumber, "0");
        }
        $fullyFormattedPhoneNumber = $phonePrefix ? "+" . $phonePrefix . "." . $trimmedPhoneNumber : $phoneNumber;
        $details["phonenumber"] = $phoneNumber;
        $details["phonecc"] = $phonePrefix;
        $details["phonenumberformatted"] = $phoneNumber ? $fullyFormattedPhoneNumber : $phoneNumber;
        $details["telephoneNumber"] = Config\Setting::getValue("PhoneNumberDropdown") ? $details["phonenumberformatted"] : $phone;
        return $details;
    }
    public function getDetails($contactid = "")
    {
        if (is_null($this->clientModel)) {
            return false;
        }
        $countries = new Utility\Country();
        if (!function_exists("convertStateToCode")) {
            require ROOTDIR . "/includes/clientfunctions.php";
        }
        if (!function_exists("getCustomFields")) {
            require ROOTDIR . "/includes/customfieldfunctions.php";
        }
        $details = array();
        $details["userid"] = $this->clientModel->id;
        $details["id"] = $details["userid"];
        $billingContact = false;
        if ($contactid == "billing") {
            $contactid = $this->clientModel->billingContactId;
            $billingContact = true;
        } else {
            $contactid = (int) $contactid;
        }
        $contact = null;
        if (0 < $contactid) {
            try {
                $contact = $this->clientModel->contacts()->whereId($contactid)->firstOrFail();
                $details["firstname"] = $contact->firstName;
                $details["lastname"] = $contact->lastName;
                $details["companyname"] = $contact->companyName;
                $details["email"] = $contact->email;
                $details["address1"] = $contact->address1;
                $details["address2"] = $contact->address2;
                $details["city"] = $contact->city;
                $details["fullstate"] = $contact->state;
                $details["state"] = $details["fullstate"];
                $details["postcode"] = $contact->postcode;
                $details["countrycode"] = $contact->country;
                $details["country"] = $details["countrycode"];
                $details["phonenumber"] = $contact->phoneNumber;
                $details["tax_id"] = $contact->taxId;
                if (empty($details["tax_id"])) {
                    $details["tax_id"] = $this->clientModel->taxId;
                }
                $details["password"] = $contact->passwordHash;
                $details["domainemails"] = $contact->receivesDomainEmails;
                $details["generalemails"] = $contact->receivesGeneralEmails;
                $details["invoiceemails"] = $contact->receivesInvoiceEmails;
                $details["productemails"] = $contact->receivesProductEmails;
                $details["supportemails"] = $contact->receivesSupportEmails;
                $details["affiliateemails"] = $contact->receivesAffiliateEmails;
                $details["model"] = $contact;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                if ($billingContact) {
                    $this->clientModel->billingcid = 0;
                    $this->clientModel->save();
                }
            }
        }
        if (is_null($contact)) {
            $details["uuid"] = $this->clientModel->uuid;
            $details["firstname"] = $this->clientModel->firstName;
            $details["lastname"] = $this->clientModel->lastName;
            $details["companyname"] = $this->clientModel->companyName;
            $details["email"] = $this->clientModel->email;
            $details["address1"] = $this->clientModel->address1;
            $details["address2"] = $this->clientModel->address2;
            $details["city"] = $this->clientModel->city;
            $details["fullstate"] = $this->clientModel->state;
            $details["state"] = $details["fullstate"];
            $details["postcode"] = $this->clientModel->postcode;
            $details["countrycode"] = $this->clientModel->country;
            $details["country"] = $details["countrycode"];
            $details["phonenumber"] = $this->clientModel->phoneNumber;
            $details["tax_id"] = $this->clientModel->taxId;
            $details["password"] = $this->clientModel->passwordHash;
            $details["model"] = $this->clientModel;
        }
        $details["fullname"] = $details["firstname"] . " " . $details["lastname"];
        if (!$details["uuid"]) {
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $details["uuid"] = $uuid->toString();
        }
        if ($details["country"] == "GB") {
            $postcode = $origpostcode = $details["postcode"];
            $postcode = strtoupper($postcode);
            $postcode = preg_replace("/[^A-Z0-9]/", "", $postcode);
            if (strlen($postcode) == 5) {
                $postcode = substr($postcode, 0, 2) . " " . substr($postcode, 2);
            } else {
                if (strlen($postcode) == 6) {
                    $postcode = substr($postcode, 0, 3) . " " . substr($postcode, 3);
                } else {
                    if (strlen($postcode) == 7) {
                        $postcode = substr($postcode, 0, 4) . " " . substr($postcode, 4);
                    } else {
                        $postcode = $origpostcode;
                    }
                }
            }
            $postcode = trim($postcode);
            $details["postcode"] = $postcode;
        }
        $details["statecode"] = convertStateToCode($details["state"], $details["country"]);
        $details["countryname"] = $countries->getName($details["countrycode"]);
        $details = self::formatPhoneNumber($details);
        if (!function_exists("getClientDefaultCardDetails")) {
            require_once ROOTDIR . "/includes/ccfunctions.php";
        }
        $defaultPayMethod = getClientDefaultCardDetails($this->userid);
        $details["billingcid"] = $this->clientModel->billingContactId;
        $details["notes"] = $this->clientModel->notes;
        $details["twofaenabled"] = $this->clientModel->twoFactorAuthModule ? true : false;
        $details["currency"] = $this->clientModel->currencyId;
        $details["defaultgateway"] = $this->clientModel->defaultPaymentGateway;
        $details["cctype"] = $defaultPayMethod["cardtype"];
        $details["cclastfour"] = $defaultPayMethod["cardlastfour"];
        $details["gatewayid"] = $defaultPayMethod["gatewayid"];
        $details["securityqid"] = $this->clientModel->securityQuestionId;
        $details["securityqans"] = $this->clientModel->securityQuestionAnswer;
        $details["groupid"] = $this->clientModel->groupId;
        $details["status"] = $this->clientModel->status;
        $details["credit"] = $this->clientModel->credit;
        $details["taxexempt"] = $this->clientModel->taxExempt;
        $details["latefeeoveride"] = $this->clientModel->overrideLateFee;
        $details["overideduenotices"] = $this->clientModel->overrideOverdueNotices;
        $details["separateinvoices"] = $this->clientModel->separateInvoices;
        $details["disableautocc"] = $this->clientModel->disableAutomaticCreditCardProcessing;
        $details["emailoptout"] = $this->clientModel->emailOptOut;
        $details["marketing_emails_opt_in"] = $this->clientModel->marketingEmailsOptIn;
        $details["overrideautoclose"] = $this->clientModel->overrideAutoClose;
        $details["allowSingleSignOn"] = $this->clientModel->allowSso;
        $details["language"] = $this->clientModel->language;
        $details["isOptedInToMarketingEmails"] = $this->clientModel->isOptedInToMarketingEmails();
        $lastlogin = $this->clientModel->lastLoginDate->format("Y-m-d H:i:s");
        $details["lastlogin"] = $lastlogin == "1970-01-01 00:00:00" ? "No Login Logged" : "Date: " . fromMySQLDate($lastlogin, "time") . "<br>IP Address: " . $this->clientModel->lastLoginIp . "<br>Host: " . $this->clientModel->lastLoginHostname;
        $customfields = getCustomFields("client", "", $this->clientModel->id, true);
        foreach ($customfields as $i => $value) {
            $details["customfields" . ($i + 1)] = $value["value"];
            $details["customfields"][] = array("id" => $value["id"], "value" => $value["value"]);
        }
        return $details;
    }
    public function getCurrency()
    {
        return getCurrency($this->getID());
    }
    public function updateClient()
    {
        global $whmcs;
        $exinfo = $this->getDetails();
        $isAdmin = false;
        if (defined("ADMINAREA")) {
            $updatefieldsarray = array();
            $isAdmin = true;
        } else {
            $updatefieldsarray = array("firstname" => "First Name", "lastname" => "Last Name", "companyname" => "Company Name", "email" => "Email Address", "address1" => "Address 1", "address2" => "Address 2", "city" => "City", "state" => "State", "postcode" => "Postcode", "country" => "Country", "phonenumber" => "Phone Number", "billingcid" => "Billing Contact", "tax_id" => Billing\Tax\Vat::getLabel());
        }
        $changelist = array();
        $updateqry = array();
        $emailWasUpdated = false;
        foreach ($updatefieldsarray as $field => $displayname) {
            if ($this->isEditableField($field)) {
                $value = $whmcs->get_req_var($field);
                $existingValue = $exinfo[$field];
                if ($field == "phonenumber" && $value) {
                    $value = str_replace(array(" ", "-"), "", \App::formatPostedPhoneNumber());
                    $existingValue = $exinfo["phonenumberformatted"];
                }
                $this->clientModel->{$field} = $value;
                if ($value != $existingValue) {
                    $changelist[] = (string) $displayname . ": '" . $existingValue . "' to '" . $value . "'";
                    if ($field == "email") {
                        $emailWasUpdated = true;
                    }
                }
            }
        }
        if ($emailWasUpdated && Config\Setting::getValue("EnableEmailVerification")) {
            $this->clientModel->sendEmailAddressVerification();
            $this->clientModel->emailVerified = 0;
        }
        if (Config\Setting::getValue("AllowClientsEmailOptOut")) {
            $marketingoptin = (bool) \App::getFromRequest("marketingoptin");
            if ($this->clientModel->isOptedInToMarketingEmails() && !$marketingoptin) {
                $this->clientModel->marketingEmailOptOut();
                $changelist[] = "Opted Out of Marketing Emails";
            } else {
                if (!$this->clientModel->isOptedInToMarketingEmails() && $marketingoptin) {
                    $this->clientModel->marketingEmailOptIn();
                    $changelist[] = "Opted In to Marketing Emails";
                }
            }
        }
        if (Config\Setting::getValue("TaxEUTaxValidation")) {
            $taxExempt = Billing\Tax\Vat::setTaxExempt($this->clientModel);
            $this->clientModel->taxExempt = $taxExempt;
        }
        $this->clientModel->save();
        $customfieldsarray = array();
        $old_customfieldsarray = getCustomFields("client", "", $this->getID(), "", "");
        $customfields = getCustomFields("client", "", $this->getID(), "", "");
        foreach ($customfields as $v) {
            $k = $v["id"];
            $customfieldsarray[$k] = $_POST["customfield"][$k];
        }
        saveCustomFields($this->getID(), $customfieldsarray, "client", $isAdmin);
        $paymentmethod = $whmcs->get_req_var("paymentmethod");
        if ($paymentmethod == "none") {
            $paymentmethod = "";
        }
        clientChangeDefaultGateway($this->getID(), $paymentmethod);
        if ($paymentmethod != $exinfo["defaultgateway"]) {
            $changelist[] = "Default Payment Method: '" . getGatewayName($exinfo["defaultgateway"]) . "' to '" . getGatewayName($paymentmethod) . "'\n";
        }
        run_hook("ClientEdit", array_merge(array("userid" => $this->getID(), "isOptedInToMarketingEmails" => $this->clientModel->isOptedInToMarketingEmails(), "olddata" => $exinfo), $updateqry));
        if (!defined("ADMINAREA")) {
            foreach ($old_customfieldsarray as $values) {
                if ($values["value"] != $_POST["customfield"][$values["id"]]) {
                    $changelist[] = $values["name"] . ": '" . $values["value"] . "' to '" . $_POST["customfield"][$values["id"]] . "'";
                }
            }
            if (0 < count($changelist)) {
                if (Config\Setting::getValue("SendEmailNotificationonUserDetailsChange")) {
                    $adminurl = \App::getSystemURL();
                    $adminurl .= \App::get_admin_folder_name() . "/clientssummary.php?userid=" . $this->getID();
                    sendAdminNotification("account", "WHMCS User Details Change", "<p>Client ID: <a href=\"" . $adminurl . "\">" . $this->getID() . " - " . $exinfo["firstname"] . " " . $exinfo["lastname"] . "</a> has requested to change his/her details as indicated below:<br><br>" . implode("<br />\n", $changelist) . "<br>If you are unhappy with any of the changes, you need to login and revert them - this is the only record of the old details.</p><p>This change request was submitted from " . Utility\Environment\CurrentUser::getIPHost() . " (" . Utility\Environment\CurrentUser::getIP() . ")</p>");
                }
                logActivity("Client Profile Modified - " . implode(", ", $changelist) . " - User ID: " . $this->getID(), $this->getID());
            }
        }
        return true;
    }
    public function getContactsWithAddresses()
    {
        $where = array();
        $where["userid"] = $this->userid;
        $where["address1"] = array("sqltype" => "NEQ", "value" => "");
        return $this->getContactsData($where);
    }
    public function getContacts()
    {
        $where = array();
        $where["userid"] = $this->userid;
        return $this->getContactsData($where);
    }
    private function getContactsData($where)
    {
        $contactsarray = array();
        $result = select_query("tblcontacts", "id,firstname,lastname,email", $where, "firstname` ASC,`lastname", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $contactsarray[] = array("id" => $data["id"], "name" => $data["firstname"] . " " . $data["lastname"], "email" => $data["email"]);
        }
        return $contactsarray;
    }
    public function getContact($contactid)
    {
        $result = select_query("tblcontacts", "", array("userid" => $this->userid, "id" => $contactid));
        $data = mysql_fetch_assoc($result);
        $data["permissions"] = explode(",", $data["permissions"]);
        return isset($data["id"]) ? $data : false;
    }
    public function deleteContact($contactid)
    {
        $contactInfo = $this->getContact($contactid);
        $name = $contactInfo["firstname"] . " " . $contactInfo["lastname"];
        $email = $contactInfo["email"];
        delete_query("tblcontacts", array("userid" => $this->userid, "id" => $contactid));
        update_query("tblclients", array("billingcid" => ""), array("billingcid" => $contactid, "id" => $this->userid));
        update_query("tblorders", array("contactid" => "0"), array("contactid" => $contactid));
        delete_query("tblauthn_account_links", array("client_id" => $this->userid, "contact_id" => $contactid));
        run_hook("ContactDelete", array("userid" => $this->userid, "contactid" => $contactid));
        logActivity("Deleted Contact - User ID: " . $this->userid . " - Contact ID: " . $contactid . " - Contact Name: " . $name . " - Contact Email: " . $email, $this->userid);
    }
    public function getFiles()
    {
        $where = array("userid" => $this->userid);
        if (!defined("ADMINAREA")) {
            $where["adminonly"] = "";
        }
        $files = array();
        $result = select_query("tblclientsfiles", "", $where, "title", "ASC");
        while ($data = mysql_fetch_assoc($result)) {
            $id = $data["id"];
            $title = $data["title"];
            $adminonly = $data["adminonly"];
            $filename = $data["filename"];
            $filename = substr($filename, 11);
            $date = fromMySQLDate($data["dateadded"], 0, 1);
            $files[] = array("id" => $id, "date" => $date, "title" => $title, "adminonly" => $adminonly, "filename" => $filename);
        }
        return $files;
    }
    public function resetSendPW()
    {
        sendMessage("Automated Password Reset", $this->userid);
        return true;
    }
    public function sendEmailTpl($tplname)
    {
        return sendMessage($tplname, $this->userid);
    }
    public function getEmailTemplates()
    {
        return Mail\Template::where("type", "=", "general")->where("language", "=", "")->where("name", "!=", "Password Reset Validation")->orderBy("name")->get();
    }
    public function sendCustomEmail($subject, $message)
    {
        Mail\Template::where("name", "=", "Client Custom Email Msg")->delete();
        $customTemplate = new Mail\Template();
        $customTemplate->type = "general";
        $customTemplate->name = "Client Custom Email msg";
        $customTemplate->subject = $subject;
        $customTemplate->message = $message;
        $customTemplate->disabled = false;
        $customTemplate->plaintext = false;
        sendMessage($customTemplate, $this->userid);
        return true;
    }
}

?>