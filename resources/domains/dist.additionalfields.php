<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/*
 **********************************************************************
 *         Additional Domain Fields (aka Extended Attributes)         *
 **********************************************************************
 *                                                                    *
 * This file contains the default additional domain field definitions *
 * for WHMCS.                                                         *
 *                                                                    *
 * We do not recommend editing this file directly. To customise the   *
 * fields, you should create an overrides file.                       *
 *                                                                    *
 * For more information please refer to the online documentation at   *
 *   https://docs.whmcs.com/Additional_Domain_Fields                   *
 *                                                                    *
 **********************************************************************
 */
// .US
$additionaldomainfields[".us"][] = ["Name" => "Nexus Category", "LangVar" => "ustldnexuscat", "Type" => "dropdown", "Options" => "C11|C11 - United States Citizen,C12|C12 - Permanent Resident of the United States,C21|C21 - A U.S.-based organization formed within the United States of America,C31|C31 - A foreign entity or organization that has a bona fide presence in the United States of America,C32|C32 - An entity or Organisation that has an office or other facility in the United States", "Default" => "C11"];
$additionaldomainfields[".us"][] = ["Name" => "Nexus Country", "LangVar" => "ustldnexuscountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true];
$additionaldomainfields[".us"][] = ["Name" => "Application Purpose", "LangVar" => "ustldapppurpose", "Type" => "dropdown", "Options" => "Business use for profit,Non-profit business,Club,Association,Religious Organization,Personal Use,Educational purposes,Government purposes", "Default" => "Business use for profit"];
// .UK
$additionaldomainfields[".co.uk"][] = array("Name" => "Legal Type", "LangVar" => "uktldlegaltype", "Type" => "dropdown", "Options" => implode(',', ['UK Limited Company', 'UK Public Limited Company', 'UK Partnership', 'UK Limited Liability Partnership', 'Sole Trader|UK Sole Trader', 'UK Industrial/Provident Registered Company', 'Individual|UK Individual (representing self)', 'UK School', 'UK Registered Charity', 'UK Government Body', 'UK Corporation by Royal Charter', 'UK Statutory Body', 'UK Entity (other)|UK Entity that does not fit into any of the above (e.g. clubs&comma; associations&comma; many universities)', 'Non-UK Individual|Non-UK Individual (representing self)', 'Foreign Organization|Non-UK Corporation', 'Other foreign organizations|Non-UK Entity that does not fit into any of the above (e.g. charities&comma; schools&comma; clubs&comma; associations)']), "Default" => "Individual");
$additionaldomainfields[".co.uk"][] = array("Name" => "Company ID Number", "LangVar" => "uktldcompanyid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => false);
$additionaldomainfields[".co.uk"][] = array("Name" => "Registrant Name", "LangVar" => "uktldregname", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true);
$additionaldomainfields[".net.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".org.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".plc.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".ltd.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".co.uk"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "uktldwhoisoptout", "Type" => "tickbox");
$additionaldomainfields[".me.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".uk"] = $additionaldomainfields[".co.uk"];
// .CA
$additionaldomainfields[".ca"][] = array("Name" => "Legal Type", "Required" => true, "LangVar" => "catldlegaltype", "Type" => "dropdown", "Options" => "Corporation,Canadian Citizen,Permanent Resident of Canada,Government,Canadian Educational Institution,Canadian Unincorporated Association,Canadian Hospital,Partnership Registered in Canada,Trade-mark registered in Canada,Canadian Trade Union,Canadian Political Party,Canadian Library Archive or Museum,Trust established in Canada,Aboriginal Peoples,Legal Representative of a Canadian Citizen,Official mark registered in Canada", "Default" => "Corporation", "Description" => "Legal type of registrant contact");
$additionaldomainfields[".ca"][] = array("Name" => "CIRA Agreement", "Required" => true, "LangVar" => "catldciraagreement", "Type" => "tickbox", "Description" => "Tick to confirm you agree to the CIRA Registration Agreement shown below<br /><blockquote>You have read, understood and agree to the terms and conditions of the Registrant Agreement, and that CIRA may, from time to time and at its discretion, amend any or all of the terms and conditions of the Registrant Agreement, as CIRA deems appropriate, by posting a notice of the changes on the CIRA website and by sending a notice of any material changes to Registrant. You meet all the requirements of the Registrant Agreement to be a Registrant, to apply for the registration of a Domain Name Registration, and to hold and maintain a Domain Name Registration, including without limitation CIRA's Canadian Presence Requirements for Registrants, at: www.cira.ca/assets/Documents/Legal/Registrants/CPR.pdf. CIRA will collect, use and disclose your personal information, as set out in CIRA's Privacy Policy, at: www.cira.ca/assets/Documents/Legal/Registrants/privacy.pdf</blockquote>");
$additionaldomainfields[".ca"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "catldwhoisoptout", "Type" => "tickbox", "Description" => "Tick to hide your contact information in CIRA WHOIS (only available to individuals)");
// .ES
$additionaldomainfields[".es"][] = array("Name" => "ID Form Type", "LangVar" => "estldidformtype", "Type" => "dropdown", "Options" => "Other Identification,Tax Identification Number,Tax Identification Code,Foreigner Identification Number", "Default" => "Other Identification");
$additionaldomainfields[".es"][] = array("Name" => "ID Form Number", "LangVar" => "estldidformnum", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true);
$additionaldomainfields[".es"][] = array("Name" => "Legal Form", "LangVar" => "estldlegalform", "Type" => "dropdown", "Options" => implode(',', array('1|Individual', '39|Economic Interest Grouping', '47|Association', '59|Sports Association', '68|Professional Association', '124|Savings Bank', '150|Community Property', '152|Community of Owners', '164|Order or Religious Institution', '181|Consulate', '197|Public Law Association', '203|Embassy', '229|Local Authority', '269|Sports Federation', '286|Foundation', '365|Mutual Insurance Company', '434|Regional Government Body', '436|Central Government Body', '439|Political Party', '476|Trade Union', '510|Farm Partnership', '524|Public Limited Company', '554|Civil Society', '560|General Partnership', '562|General and Limited Partnership', '566|Cooperative', '608|Worker-owned Company', '612|Limited Company', '713|Spanish Office', '717|Temporary Alliance of Enterprises', '744|Worker-owned Limited Company', '745|Regional Public Entity', '746|National Public Entity', '747|Local Public Entity', '877|Others', '878|Designation of Origin Supervisory Council', '879|Entity Managing Natural Areas')), "Default" => "1|Individual");
// .SG
$additionaldomainfields[".sg"][] = array("Name" => "RCB Singapore ID", "DisplayName" => "RCB/Singapore ID", "LangVar" => "sgtldrcbid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true);
$additionaldomainfields[".sg"][] = array("Name" => "Registrant Type", "LangVar" => "sgtldregtype", "Type" => "dropdown", "Options" => "Individual,Organisation", "Default" => "Individual");
$additionaldomainfields['.sg'][] = ['Name' => 'Admin Personal ID', 'LangVar' => 'sgTldAdminPersonalId', 'Type' => 'text', 'Size' => '30', 'Required' => true, 'Description' => 'This is the personal ID of the administrative contact for this domain'];
$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".edu.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".net.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".org.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".per.sg"] = $additionaldomainfields[".sg"];
// .TEL
$additionaldomainfields[".tel"][] = array("Name" => "Legal Type", "LangVar" => "teltldlegaltype", "Type" => "dropdown", "Options" => "Natural Person,Legal Person", "Default" => "Natural Person");
$additionaldomainfields[".tel"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "teltldwhoisoptout", "Type" => "tickbox");
// .IT
$additionaldomainfields[".it"][] = array("Name" => "Legal Type", "LangVar" => "ittldlegaltype", "Type" => "dropdown", "Options" => "Italian and foreign natural persons,Companies/one man companies,Freelance workers/professionals,non-profit organizations,public organizations,other subjects,non natural foreigners", "Default" => "Italian and foreign natural persons", "Description" => "Legal type of registrant");
$additionaldomainfields[".it"][] = array("Name" => "Tax ID", "LangVar" => "ittldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true);
$additionaldomainfields[".it"][] = array("Name" => "Publish Personal Data", "LangVar" => "ittlddata", "Type" => "tickbox");
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 3 of .IT registrar contract", "LangVar" => "ittldsec3", "Type" => "tickbox");
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 5 of .IT registrar contract", "LangVar" => "ittldsec5", "Type" => "tickbox");
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 6 of .IT registrar contract", "LangVar" => "ittldsec6", "Type" => "tickbox");
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 7 of .IT registrar contract", "LangVar" => "ittldsec7", "Type" => "tickbox");
// .DE
$additionaldomainfields[".de"][] = array("Name" => "Tax ID", "LangVar" => "detldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true);
$additionaldomainfields[".de"][] = array("Name" => "Address Confirmation", "LangVar" => "detldaddressconfirm", "Type" => "tickbox", "Description" => "Please tick to confirm you have a valid German address");
$additionaldomainfields[".de"][] = array("Name" => "Agree to DE Terms", "LangVar" => "deTLDTermsAgree", "Type" => "tickbox", "Description" => $_LANG['domains']['deTermsDescription1'] . "<br />" . $_LANG['domains']['deTermsDescription2'], "Required" => true);
// .AU
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant Name", "LangVar" => "autldregname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID", "LangVar" => "autldregid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID Type", "LangVar" => "autldregidtype", "Type" => "dropdown", "Options" => "ABN,ACN,Business Registration Number", "Default" => "ABN");
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Name", "LangVar" => "autldeligname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID", "LangVar" => "autldeligid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID Type", "LangVar" => "autldeligidtype", "Type" => "dropdown", "Options" => ",Australian Company Number (ACN),ACT Business Number,NSW Business Number,NT Business Number,QLD Business Number,SA Business Number,TAS Business Number,VIC Business Number,WA Business Number,Trademark (TM),Other - Used to record an Incorporated Association number,Australian Business Number (ABN)", "Default" => "");
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Type", "LangVar" => "autldeligtype", "Type" => "dropdown", "Options" => "Charity,Citizen/Resident,Club,Commercial Statutory Body,Company,Incorporated Association,Industry Body,Non-profit Organisation,Other,Partnership,Pending TM Owner  ,Political Party,Registered Business,Religious/Church Group,Sole Trader,Trade Union,Trademark Owner,Child Care Centre,Government School,Higher Education Institution,National Body,Non-Government School,Pre-school,Research Organisation,Training Organisation", "Default" => "Company");
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Reason", "LangVar" => "autldeligreason", "Type" => "radio", "Options" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.,Close and substantial connection between the domain name and the operations of your Entity.", "Default" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.");
$additionaldomainfields[".net.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".org.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".asn.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".id.au"] = $additionaldomainfields[".com.au"];
// .ASIA
$additionaldomainfields[".asia"][] = array("Name" => "Legal Type", "LangVar" => "asialegaltype", "Type" => "dropdown", "Options" => "naturalPerson,corporation,cooperative,partnership,government,politicalParty,society,institution", "Default" => "naturalPerson");
$additionaldomainfields[".asia"][] = array("Name" => "Identity Form", "LangVar" => "asiaidentityform", "Type" => "dropdown", "Options" => "passport,certificate,legislation,societyRegistry,politicalPartyRegistry", "Default" => "passport");
$additionaldomainfields[".asia"][] = array("Name" => "Identity Number", "LangVar" => "asiaidentitynumber", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true);
// .PRO
$additionaldomainfields[".pro"][] = array("Name" => "Profession", "LangVar" => "proprofession", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "Indicated professional association recognized by government body");
$additionaldomainfields[".pro"][] = array("Name" => "License Number", "LangVar" => "prolicensenumber", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false, "Description" => "The license number of the registrant's credentials, if applicable.");
$additionaldomainfields[".pro"][] = array("Name" => "Authority", "LangVar" => "proauthority", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false, "Description" => "The name of the authority from which the registrant receives their professional credentials.");
$additionaldomainfields[".pro"][] = array("Name" => "Authority Website", "LangVar" => "proauthoritywebsite", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false, "Description" => "The URL to an online resource for the authority, preferably, a member search directory.");
// .COOP
$additionaldomainfields[".coop"][] = array("Name" => "Contact Name", "LangVar" => "coopcontactname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "A sponsor is required to register .coop domains. Please enter the information here");
$additionaldomainfields[".coop"][] = array("Name" => "Contact Company", "LangVar" => "cooopcontactcompany", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "Contact Email", "LangVar" => "coopcontactemail", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "Address 1", "LangVar" => "coopaddress1", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "Address 2", "LangVar" => "coopaddress2", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "City", "LangVar" => "coopcity", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "State", "LangVar" => "coopstate", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "ZIP Code", "LangVar" => "coopzip", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
$additionaldomainfields[".coop"][] = array("Name" => "Country", "LangVar" => "coopcountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "Please enter your country code (eg. FR, IT, etc...)");
$additionaldomainfields[".coop"][] = array("Name" => "Phone CC", "LangVar" => "coopphonecc", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "Phone Country Code eg 1 for US & Canada, 44 for UK");
$additionaldomainfields[".coop"][] = array("Name" => "Phone", "LangVar" => "coopphone", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "");
// .CN
$additionaldomainfields[".cn"][] = array("Name" => "cnhosting", "DisplayName" => "Hosted in China?", "LangVar" => "cnhosting", "Type" => "tickbox");
$additionaldomainfields[".cn"][] = array("Name" => "cnhregisterclause", "DisplayName" => "Agree to the .CN <a href=\"http://www1.cnnic.cn/PublicS/fwzxxgzcfg/201208/t20120830_35735.htm\" target=\"_blank\">Register Agreement</a>", "LangVar" => "ittldsec3", "Type" => "tickbox", "Required" => true);
// .FR
$additionaldomainfields[".fr"][] = array("Name" => "Legal Type", "LangVar" => "fr_legaltype", "Type" => "dropdown", "Options" => "Individual,Company", "Default" => "Individual");
$additionaldomainfields[".fr"][] = array("Name" => "Info", "LangVar" => "fr_info", "Type" => "display", "Default" => "{$_LANG['enomfrregistration']['Heading']}\n        <ul>\n            <li><strong>{$_LANG['enomfrregistration']['French Individuals']['Name']}</strong>: {$_LANG['enomfrregistration']['French Individuals']['Requirements']}</li>\n            <li><strong>{$_LANG['enomfrregistration']['EU Non-French Individuals']['Name']}</strong>: {$_LANG['enomfrregistration']['EU Non-French Individuals']['Requirements']}</li>\n            <li><strong>{$_LANG['enomfrregistration']['French Companies']['Name']}</strong>: {$_LANG['enomfrregistration']['French Companies']['Requirements']}</li>\n            <li><strong>{$_LANG['enomfrregistration']['EU Non-French Companies']['Name']}</strong>: {$_LANG['enomfrregistration']['EU Non-French Companies']['Requirements']}</li>\n        </ul>\n        <em>{$_LANG['enomfrregistration']['Non-EU Warning']}</em>");
$additionaldomainfields[".fr"][] = array("Name" => "Birthdate", 'LangVar' => 'fr_indbirthdate', "Type" => "text", "Size" => "16", "Default" => "1900-01-01", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "Birthplace City", 'LangVar' => 'fr_indbirthcity', "Type" => "text", "Size" => "25", "Default" => "", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "Birthplace Country", 'LangVar' => 'fr_indbirthcountry', "Type" => "text", "Size" => "2", "Default" => "", "Required" => false, "Description" => "Please enter your country code (eg. FR, IT, etc...)");
$additionaldomainfields[".fr"][] = array("Name" => "Birthplace Postcode", 'LangVar' => 'fr_indbirthpostcode', "Type" => "text", "Size" => "6", "Default" => "", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "SIRET Number", 'LangVar' => 'fr_cosiret', "Type" => "text", "Size" => "50", "Default" => "", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "DUNS Number", 'LangVar' => 'fr_coduns', "Type" => "text", "Size" => "50", "Default" => "", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "VAT Number", 'LangVar' => 'fr_vat', "Type" => "text", "Size" => "50", "Default" => "", "Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "Trademark Number", 'LangVar' => 'fr_trademarknumber', "Type" => "text", "Size" => "50", "Default" => "", "Required" => false);
$additionaldomainfields[".re"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".pm"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".tf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".wf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".yt"] = $additionaldomainfields[".fr"];
/**
 * .NU extended domain attributes
 */
$additionaldomainfields['.nu'][] = array('Name' => 'Identification Number', 'LangVar' => 'nu_iis_orgno', 'Type' => 'text', 'Size' => 20, 'Required' => true, 'Description' => 'Personal Identification Number (or Organization number), ' . 'if you are an individual registrant (or organization) in Sweden');
$additionaldomainfields['.nu'][] = array('Name' => 'VAT Number', 'LangVar' => 'nu_iis_vatno', 'Type' => 'text', 'Size' => 20, 'Required' => false, 'Description' => 'Optional VAT Number (for Swedish Organization)');
// .QUEBEC
$additionaldomainfields[".quebec"][] = array("Name" => "Intended Use", 'LangVar' => 'quebec_intendeduse', "Type" => "text", "Size" => "50", "Default" => "", "Required" => true);
$additionaldomainfields[".quebec"][] = array("Name" => "Info", "LangVar" => "quebec_info", "Type" => "display", "Default" => "Intended Use field limited to 2048 characters by the API.  The contents of the field above will be truncated if longer than that when sent to the registrar.");
// .JOBS
$additionaldomainfields['.jobs'][] = array('Name' => 'Website', 'Type' => 'text', 'Size' => '20', 'Required' => true);
// .TRAVEL
$travel_id = array('TRUST|Use Trustee', 'UIN|Use My Information (Requires UIN)');
$additionaldomainfields['.travel'][] = array('Name' => 'Trustee Service', 'DisplayName' => 'Trustee Service <sup style="cursor:help;" title="Trustee service allows you to register domains under the name of the trustee if you do not meet the requiremets.">what\'s this?</sup>', 'Options' => implode(',', $travel_id), 'Type' => 'dropdown', 'Required' => true);
$additionaldomainfields['.travel'][] = array('Name' => '.TRAVEL UIN Code', 'DisplayName' => '.TRAVEL UIN Code <sup style="cursor:help;" title="Travel UIN Code obtained from http://www.authentication.travel/">what\'s this?</sup>', 'Type' => 'text', 'Size' => '30');
$additionaldomainfields['.travel'][] = array('Name' => 'Trustee Service Agreement ', 'Description' => 'I agree to the <a href="http://www.101domain.com/trustee_agreement.htm" target="_BLANK">Trustee Service Agreement</a>', 'Type' => 'tickbox');
$additionaldomainfields['.travel'][] = array('Name' => '.TRAVEL Usage Agreement', 'Description' => 'I agree that .travel domains are restricted to those who are primarily active in the travel industry.', 'Type' => 'tickbox');
// .RU
$ru_type = array('ORG|Organization', 'IND|Individual');
$additionaldomainfields['.ru'][] = array('Name' => 'Registrant Type', 'Type' => 'dropdown', 'Options' => implode(',', $ru_type), 'Required' => true);
$additionaldomainfields['.ru'][] = array('Name' => 'Individuals Birthday', 'DisplayName' => 'Individuals: Birthday (YYYY-MM-DD)', 'Type' => 'text', 'Size' => '10');
$additionaldomainfields['.ru'][] = array('Name' => 'Individuals Passport Number', 'DisplayName' => 'Individuals: Passport Number', 'Type' => 'text', 'Size' => '20');
$additionaldomainfields['.ru'][] = array('Name' => 'Individuals Passport Issuer', 'DisplayName' => 'Individuals: Passport Issuer', 'Type' => 'text', 'Size' => '20');
$additionaldomainfields['.ru'][] = array('Name' => 'Individuals Passport Issue Date', 'DisplayName' => 'Individuals: Passport Issue Date (YYYY-MM-DD)', 'Type' => 'text', 'Size' => '10');
$additionaldomainfields['.ru'][] = array('Name' => 'Individuals: Whois Privacy', 'DisplayName' => 'Individuals Whois Privacy', 'Type' => 'dropdown', 'Options' => 'No,Yes', 'default' => 'No');
$additionaldomainfields['.ru'][] = array('Name' => 'Russian Organizations Taxpayer Number 1', 'DisplayName' => 'Russian Organizations: Taxpayer Number (ИНН)', 'Type' => 'text');
$additionaldomainfields['.ru'][] = array('Name' => 'Russian Organizations Territory-Linked Taxpayer Number 2', 'DisplayName' => 'Russian Organizations: Territory-Linked Taxpayer Number (КПП)', 'Type' => 'text');
$additionaldomainfields['.xn--p1ai'] = $additionaldomainfields['.ru'];
// .RO
$ro_person_type = array('p|Private Person', 'ap|Authorized Person', 'nc|Non-Commercial Organization', 'c|Commercial', 'gi|Government Institute', 'pi|Public Institute', 'o|Other Juridicial');
$additionaldomainfields['.ro'][] = array('Name' => 'CNPFiscalCode', 'Type' => 'text', 'Size' => '20');
$additionaldomainfields['.ro'][] = array('Name' => 'Registration Number', 'Type' => 'text', 'Size' => '20');
$additionaldomainfields['.ro'][] = array('Name' => 'Registrant Type', 'Type' => 'dropdown', 'Options' => implode(',', $ro_person_type), 'Required' => true);
$additionaldomainfields['.arts.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.co.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.com.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.firm.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.info.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.nom.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.nt.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.org.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.rec.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.ro.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.store.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.tm.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.www.ro'] = $additionaldomainfields['.ro'];
// .HK
// this is copied from the base one and modified with custom variables
$hk_industry_type = array('010100|Plastics / Petro-Chemicals / Chemicals - Plastics &amp; Plastic Products ', '010200|Plastics / Petro-Chemicals / Chemicals - Rubber &amp; Rubber Products ', '010300|Plastics / Petro-Chemicals / Chemicals - Fibre Materials &amp; Products ', '010400|Plastics / Petro-Chemicals / Chemicals - Petroleum / Coal &amp; Other Fuels ', '010500|Plastics / Petro-Chemicals / Chemicals - Chemicals &amp; Chemical Products ', '020100|Metals / Machinery / Equipment - Metal Materials &amp; Treatment ', '020200|Metals / Machinery / Equipment - Metal Products ', '020300|Metals / Machinery / Equipment - Industrial Machinery &amp; Supplies ', '020400|Metals / Machinery / Equipment - Precision &amp; Optical Equipment ', '020500|Metals / Machinery / Equipment - Moulds &amp; Dies ', '030100|Printing / Paper / Publishing - Printing / Photocopying / Publishing ', '030200|Printing / Paper / Publishing - Paper / Paper Products ', '040100|Construction / Decoration / Environmental Engineering - Construction Contractors ', '040200|Construction / Decoration / Environmental Engineering - Construction Materials ', '040300|Construction / Decoration / Environmental Engineering - Decoration Materials ', '040400|Construction / Decoration / Environmental Engineering - Construction / Safety Equipment &amp; Supplies ', '040500|Construction / Decoration / Environmental Engineering - Decoration / Locksmiths / Plumbing &amp; Electrical Works ', '040600|Construction / Decoration / Environmental Engineering - Fire Protection Equipment &amp; Services ', '040700|Construction / Decoration / Environmental Engineering - Environmental Engineering / Waste Reduction ', '050100|Textiles / Clothing &amp; Accessories - Textiles / Fabric ', '050200|Textiles / Clothing &amp; Accessories - Clothing ', '050300|Textiles / Clothing &amp; Accessories - Uniforms / Special Clothing ', '050400|Textiles / Clothing &amp; Accessories - Clothing Manufacturing Accessories ', '050500|Textiles / Clothing &amp; Accessories - Clothing Processing &amp; Equipment ', '050600|Textiles / Clothing &amp; Accessories - Fur / Leather &amp; Leather Goods ', '050700|Textiles / Clothing &amp; Accessories - Handbags / Footwear / Optical Goods / Personal Accessories ', '060100|Electronics / Electrical Appliances - Electronic Equipment &amp; Supplies ', '060200|Electronics / Electrical Appliances - Electronic Parts &amp; Components ', '060300|Electronics / Electrical Appliances - Electrical Appliances / Audio-Visual Equipment ', '070100|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Kitchenware / Tableware ', '070200|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Bedding ', '070300|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Bathroom / Cleaning Accessories ', '070400|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Household Goods ', '070500|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Wooden / Bamboo &amp; Rattan Goods ', '070600|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Home Furnishings / Arts &amp; Crafts ', '070700|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Watches / Clocks ', '070800|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Jewellery Accessories ', '070900|Houseware / Watches / Clocks / Jewellery / Toys / Gifts - Toys / Games / Gifts ', '080100|Business &amp; Professional Services / Finance - Accounting / Legal Services ', '080200|Business &amp; Professional Services / Finance - Advertising / Promotion Services ', '080300|Business &amp; Professional Services / Finance - Consultancy Services ', '080400|Business &amp; Professional Services / Finance - Translation / Design Services ', '080500|Business &amp; Professional Services / Finance - Cleaning / Pest Control Services ', '080600|Business &amp; Professional Services / Finance - Security Services ', '080700|Business &amp; Professional Services / Finance - Trading / Business Services ', '080800|Business &amp; Professional Services / Finance - Employment Services ', '080900|Business &amp; Professional Services / Finance - Banking / Finance / Investment ', '081000|Business &amp; Professional Services / Finance - Insurance ', '081100|Business &amp; Professional Services / Finance - Property / Real Estate ', '090100|Transportation / Logistics - Land Transport / Motorcars ', '090200|Transportation / Logistics - Sea Transport / Boats ', '090300|Transportation / Logistics - Air Transport ', '090400|Transportation / Logistics - Moving / Warehousing / Courier &amp; Logistics Services ', '090500|Transportation / Logistics - Freight Forwarding ', '100100|Office Equipment / Furniture / Stationery / Information Technology - Office / Commercial Equipment &amp; Supplies ', '100200|Office Equipment / Furniture / Stationery / Information Technology - Office &amp; Home Furniture ', '100300|Office Equipment / Furniture / Stationery / Information Technology - Stationery &amp; Educational Supplies ', '100400|Office Equipment / Furniture / Stationery / Information Technology - Telecommunication Equipment &amp; Services ', '100500|Office Equipment / Furniture / Stationery / Information Technology - Computers / Information Technology ', '110100|Food / Flowers / Fishing &amp; Agriculture - Food Products &amp; Supplies ', '110200|Food / Flowers / Fishing &amp; Agriculture - Beverages / Tobacco ', '110300|Food / Flowers / Fishing &amp; Agriculture - Restaurant Equipment &amp; Supplies ', '110400|Food / Flowers / Fishing &amp; Agriculture - Flowers / Artificial Flowers / Plants ', '110500|Food / Flowers / Fishing &amp; Agriculture - Fishing ', '110600|Food / Flowers / Fishing &amp; Agriculture - Agriculture ', '120100|Medical Services / Beauty / Social Services - Medicine &amp; Herbal Products ', '120200|Medical Services / Beauty / Social Services - Medical &amp; Therapeutic Services ', '120300|Medical Services / Beauty / Social Services - Medical Equipment &amp; Supplies ', '120400|Medical Services / Beauty / Social Services - Beauty / Health ', '120500|Medical Services / Beauty / Social Services - Personal Services ', '120600|Medical Services / Beauty / Social Services - Organizations / Associations ', '120700|Medical Services / Beauty / Social Services - Information / Media ', '120800|Medical Services / Beauty / Social Services - Public Utilities ', '120900|Medical Services / Beauty / Social Services - Religion / Astrology / Funeral Services ', '130100|Culture / Education - Music / Arts ', '130200|Culture / Education - Learning Instruction &amp; Training ', '130300|Culture / Education - Elementary Education ', '130400|Culture / Education - Tertiary Education / Other Education Services ', '130500|Culture / Education - Sporting Goods ', '130600|Culture / Education - Sporting / Recreational Facilities &amp; Venues ', '130700|Culture / Education - Hobbies / Recreational Activities ', '130800|Culture / Education - Pets / Pets Services &amp; Supplies ', '140101|Dining / Entertainment / Shopping / Travel - Restaurant Guide - Chinese ', '140102|Dining / Entertainment / Shopping / Travel - Restaurant Guide - Asian ', '140103|Dining / Entertainment / Shopping / Travel - Restaurant Guide - Western ', '140200|Dining / Entertainment / Shopping / Travel - Catering Services / Eateries ', '140300|Dining / Entertainment / Shopping / Travel - Entertainment Venues ', '140400|Dining / Entertainment / Shopping / Travel - Entertainment Production &amp; Services ', '140500|Dining / Entertainment / Shopping / Travel - Entertainment Equipment &amp; Facilities ', '140600|Dining / Entertainment / Shopping / Travel - Shopping Venues ', '140700|Dining / Entertainment / Shopping / Travel - Travel / Hotels &amp; Accommodation ');
$hk_org_doctype = array('BR|Business Registration Certificate', 'CI|Certificate of Incorporation', 'CRS|Certificate of Registration of a School', 'HKSARG|Hong Kong Special Administrative Region Gov\'t Dept.', 'HKORDINANCE|Ordinance of Hong Kong');
$hk_ind_doctype = array('HKID|Hong Kong Identity Number', 'OTHID|Other Country Identity Number', 'PASSNO|Passport No.', 'BIRTHCERT|Birth Certificate');
$hk_ind_type = array('ind|Individual', 'org|Organization');
$additionaldomainfields[".hk"][] = array("Name" => "Registrant Type", "Type" => "dropdown", 'Options' => implode(',', $hk_ind_type), "Default" => "ind", 'Required' => true);
$additionaldomainfields[".hk"][] = array('Name' => 'Organizations Name in Chinese', 'DisplayName' => 'Organizations: Name in Chinese', 'Type' => 'text', 'Size' => 20);
$additionaldomainfields[".hk"][] = array('Name' => 'Organizations Supporting Documentation', 'DisplayName' => 'Organizations: Supporting Documentation', 'Type' => 'dropdown', 'Options' => implode(',', $hk_org_doctype));
$additionaldomainfields[".hk"][] = array('Name' => 'Organizations Document Number', 'DisplayName' => 'Organizations: Document Number', 'Type' => 'text', 'Size' => 20);
$additionaldomainfields[".hk"][] = array('Name' => 'Organizations Issuing Country', 'DisplayName' => 'Organizations: Issuing Country', 'Type' => 'dropdown', 'Options' => '{Countries}');
$additionaldomainfields[".hk"][] = array('Name' => 'Organizations Industry Type', 'DisplayName' => 'Organizations: Industry Type', 'Type' => 'dropdown', 'Options' => implode(',', $hk_industry_type));
$additionaldomainfields[".hk"][] = array('Name' => 'Individuals Supporting Documentation', 'DisplayName' => 'Individuals: Supporting Documentation', 'Type' => 'dropdown', 'Options' => implode(',', $hk_ind_doctype));
$additionaldomainfields[".hk"][] = array('Name' => 'Individuals Document Number', 'DisplayName' => 'Individuals: Document Number', 'Type' => 'text', 'Size' => 20);
$additionaldomainfields[".hk"][] = array('Name' => 'Individuals Issuing Country', 'DisplayName' => 'Individuals: Issuing Country', 'Type' => 'dropdown', 'Options' => '{Countries}');
$additionaldomainfields[".hk"][] = array('Name' => 'Individuals Under 18', 'DisplayName' => 'Individuals: Under 18 Years old?', 'Type' => 'dropdown', 'Options' => 'Yes,No', 'Default' => 'No');
$additionaldomainfields['.com.hk'] = $additionaldomainfields['.hk'];
$additionaldomainfields['.edu.hk'] = $additionaldomainfields['.hk'];
$additionaldomainfields['.gov.hk'] = $additionaldomainfields['.hk'];
$additionaldomainfields['.idv.hk'] = $additionaldomainfields['.hk'];
$additionaldomainfields['.net.hk'] = $additionaldomainfields['.hk'];
$additionaldomainfields['.org.hk'] = $additionaldomainfields['.hk'];
// .AERO
$additionaldomainfields['.aero'][] = array('Name' => '.AERO ID', "LangVar" => "aeroid", 'DisplayName' => '.AERO ID <sup style="cursor:help;" title="Obtain from http://www.information.aero/">what\'s this?</sup>', 'Type' => 'text', 'Size' => '20', 'Required' => true);
$additionaldomainfields['.aero'][] = array('Name' => '.AERO Key', "LangVar" => "aerokey", 'DisplayName' => '.AERO Key <sup style="cursor:help;" title="Obtain from http://www.information.aero/">what\'s this?</sup>', 'Type' => 'text', 'Size' => '20');
// .PL
$additionaldomainfields['.pl'][] = array('Name' => 'Publish Contact in .PL WHOIS', 'LangVar' => 'publishpl', 'Type' => 'dropdown', 'Options' => 'yes,no', 'Default' => 'yes', 'Size' => '20', 'Required' => true);
$additionaldomainfields['.pc.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.miasta.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.atm.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.rel.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.gmina.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.szkola.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.sos.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.media.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.edu.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.auto.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.agro.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.turystyka.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.gov.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.aid.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.nieruchomosci.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.com.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.priv.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.tm.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.travel.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.info.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.org.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.net.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.sex.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.sklep.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.powiat.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.mail.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.realestate.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.shop.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.mil.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.nom.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.gsm.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.tourism.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.targi.pl'] = $additionaldomainfields['.pl'];
$additionaldomainfields['.biz.pl'] = $additionaldomainfields['.pl'];
// .SE
$additionaldomainfields['.se'][] = array('Name' => 'Identification Number', 'DisplayName' => 'Identification Number <sup style="cursor:help;" title="For Sweedish Residents: Personal or Organization Number; For residents of other countries: Civic Registration Number, Company Registration Number or Passport Number">what\'s this?</sup>', 'Type' => 'text', 'Size' => '20', 'Required' => true);
$additionaldomainfields['.se'][] = array('Name' => 'VAT', 'DisplayName' => 'VAT <sup style="cursor:help;" title="Required for EU companies not located in Sweeden">what\'s this?</sup>', 'Type' => 'text', 'Size' => '20');
$additionaldomainfields['.tm.se'] = $additionaldomainfields['.se'];
$additionaldomainfields['.org.se'] = $additionaldomainfields['.se'];
$additionaldomainfields['.pp.se'] = $additionaldomainfields['.se'];
$additionaldomainfields['.parti.se'] = $additionaldomainfields['.se'];
$additionaldomainfields['.presse.se'] = $additionaldomainfields['.se'];
// .VOTE
$additionaldomainfields['.vote'] = array('Name' => 'Agreement', 'Type' => 'tickbox', 'Description' => 'I confirm a bona fide intention to use the domain name, ' . 'during the current/relevant election cycle, in connection with ' . 'a clearly identified political/democratic process at the time of registration.', 'Required' => true);
// .VOTO
$additionaldomainfields['.voto'] = $additionaldomainfields['.vote'];
// .SWISS
$additionaldomainfields['.swiss'][] = ['Name' => 'Core Intended Use', 'DisplayName' => 'Intended Use', 'Type' => 'text', 'Size' => 20, 'Required' => true];
$additionaldomainfields['.swiss'][] = ['Name' => 'Registrant Enterprise ID', 'DisplayName' => 'Swiss Registrant Enterprise ID', 'Type' => 'text', 'Size' => 20, 'Required' => true];
//.COM.ES
$additionaldomainfields['.com.es'][] = ['Name' => 'Entity Type', 'DisplayName' => 'Entity Type', 'LangVar' => 'comEsTldEntityType', 'Type' => 'dropdown', 'Options' => ['ALLIANCE_TEMPORARY|Temporary Alliance of Enterprises', 'ASSOCIATION|Association', 'ASSOCIATION_LAW|Public Law Association', 'BANK_SAVINGS|Savings Bank', 'CIVIL_SOCIETY|Civil Society', 'COMMUNITY_OF_OWNERS|Community of Owners', 'COMMUNITY_PROPERTY|Community Property', 'COMPANY_LIMITED|Limited Company', 'COMPANY_LIMITED_PUBLIC|Public Limited Company', 'COMPANY_LIMITED_SPORTS|Sports Public Limited Company', 'COMPANY_LIMITED_WORKER_OWNED|Worker-owned Limited Company', 'COMPANY_WORKER_OWNED|Worker-owned Company', 'CONSULATE|Consulate', 'COOPERATIVE|Cooperative', 'COUNCIL_SUPERVISORY|Designation of Origin Supervisory Council', 'ECONOMIC_INTEREST_GROUP|Economic Interest Group', 'EMBASSY|Embassy', 'ENTITY_LOCAL|Local Public Entity', 'ENTITY_MANAGING_AREAS|Entity Managing Natural Areas', 'ENTITY_NATIONAL|National Public Entity', 'ENTITY_REGIONAL|Regional Public Entity', 'FEDERATION_SPORT|Sports Federation', 'FOUNDATION|Foundation', 'GOVERNMENT_CENTRAL|Central Government Body', 'GOVERNMENT_REGIONAL|Regional Government Body', 'INDIVIDUAL|Individual', 'INSTITUTION_RELIGIOUS|Order or Religious Institution', 'INSURANCE|Mutual Insurance Company', 'LOCAL_AUTHORITY|Local Authority', 'OTHERS|Others (only for contacts outside of Spain)', 'PARTNERSHIP_FARM|Farm Partnership', 'PARTNERSHIP_GENERAL|General Partnership', 'PARTNERSHIP_GENERAL_LIMITED|General and Limited Partnership', 'POLITICAL_PARTY|Political Party', 'PROFESSIONAL|Professional Association', 'SPANISH_OFFICE|Spanish Office', 'SPORTS|Sports Association', 'UNION_TRADE|Trade Union'], 'Default' => 'INDIVIDUAL|Individual'];
$additionaldomainfields['.com.es'][] = ['Name' => 'ID Form Type', 'DisplayName' => 'Entity Type', 'LangVar' => 'comEsTldIdFormType', 'Type' => 'dropdown', 'Options' => ['CITIZEN|NIF (Spanish citizen)', 'COMPANY|CIF (Spanish Company)', 'OTHER|Other form of ID (Those outside of Spain)', 'RESIDENT|NIE (Legal residents in Spain)'], 'Default' => 'CITIZEN|NIF (Spanish citizen)'];
$additionaldomainfields['.com.es'][] = ['Name' => 'ID Form Number', 'LangVar' => 'comEsTldIdFormNumber', 'Type' => 'text', 'Size' => '30', 'Default' => '', 'Required' => true];
$additionaldomainfields['.nom.es'] = $additionaldomainfields['.com.es'];
$additionaldomainfields['.org.es'] = $additionaldomainfields['.com.es'];
//.EU
$additionaldomainfields['.eu'][] = ['Name' => 'Entity Type', 'LangVar' => 'euTldEntityType', 'Type' => 'dropdown', 'Default' => 'INDIVIDUAL|Individual - Natural persons resident within the European Community', 'Options' => ['COMPANY|Company - Undertakings having their registered office, central ' . 'administration or principal place of business within the European Community', 'INDIVIDUAL|Individual - Natural persons resident within the European Community', 'ORGANIZATION|Organization - Organizations established within the European Community ' . 'without prejudice to the application of national law'], 'Description' => 'EURid Geographical Restrictions. In order to register a .EU domain ' . 'name, you must meet certain eligibility requirements.'];

?>