<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("loginonly");
if ($a == "savenotes") {
    check_token("WHMCS.admin.default");
    update_query("tbladmins", array("notes" => $notes), array("id" => $_SESSION["adminid"]));
    exit;
}
if ($a == "minsidebar") {
    WHMCS\Cookie::set("MinSidebar", "1");
    exit;
}
if ($a == "maxsidebar") {
    WHMCS\Cookie::delete("MinSidebar");
    exit;
}
$matches = $tempmatches = $invoicematches = $ticketmatches = "";
if ($intellisearch) {
    check_token("WHMCS.admin.default");
    $value = trim($_POST["value"]);
    if (strlen($value) < 3 && !is_numeric($value)) {
        exit;
    }
    $value = db_escape_string($value);
    if (checkPermission("List Clients", true) || checkPermission("View Clients Summary", true)) {
        $query = "SELECT id,firstname,lastname,companyname,email,status FROM tblclients WHERE concat(firstname,' ',lastname) LIKE '%" . $value . "%' OR companyname LIKE '%" . $value . "%' OR address1 LIKE '%" . $value . "%' OR address2 LIKE '%" . $value . "%' OR postcode LIKE '%" . $value . "%' OR phonenumber LIKE '%" . $value . "%'";
        if (is_numeric($value)) {
            $query .= " OR id='" . $value . "'";
        }
        if (is_numeric($value) && strlen($value) == 4) {
            $query .= " OR cardlastfour='" . $value . "'";
        } else {
            $query .= " OR city LIKE '%" . $value . "%' OR state LIKE '%" . $value . "%' OR email LIKE '%" . $value . "%'";
        }
        $query .= " LIMIT 0,10";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $userid = $data["id"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $email = $data["email"];
            $status = $data["status"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $tempmatches .= "<div class=\"searchresult\"><a href=\"clientssummary.php?userid=" . $userid . "\"><strong>" . $firstname . " " . $lastname . $companyname . "</strong> #" . $userid . " <span class=\"label " . strtolower($status) . "\">" . $status . "</span><br /><span class=\"desc\">" . $email . "</span></a></div>";
        }
        if ($tempmatches) {
            $matches .= "<div class=\"searchresultheader\">Clients</div>" . $tempmatches;
        }
        $tempmatches = "";
        $query = "SELECT id,userid,firstname,lastname,companyname,email FROM tblcontacts WHERE concat(firstname,' ',lastname) LIKE '%" . $value . "%' OR companyname LIKE '%" . $value . "%' OR address1 LIKE '%" . $value . "%' OR address2 LIKE '%" . $value . "%' OR postcode LIKE '%" . $value . "%' OR phonenumber LIKE '%" . $value . "%'";
        if (is_numeric($value)) {
            $query .= " OR id='" . $value . "'";
        } else {
            $query .= " OR city LIKE '%" . $value . "%' OR state LIKE '%" . $value . "%' OR email LIKE '%" . $value . "%'";
        }
        $query .= " LIMIT 0,10";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $contactid = $data["id"];
            $userid = $data["userid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $email = $data["email"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $tempmatches .= "<div class=\"searchresult\"><a href=\"clientscontacts.php?userid=" . $userid . "&contactid=" . $contactid . "\"><strong>" . $firstname . " " . $lastname . $companyname . "</strong> #" . $contactid . "<br /><span class=\"desc\">" . $email . "</span></a></div>";
        }
        if ($tempmatches) {
            $matches .= "<div class=\"searchresultheader\">Contacts</div>" . $tempmatches;
        }
    }
    if (checkPermission("List Services", true) || checkPermission("View Clients Products/Services", true)) {
        $tempmatches = "";
        $query = "SELECT tblclients.firstname,tblclients.lastname,tblclients.companyname,tblhosting.id,tblhosting.userid,tblhosting.domain,tblproducts.name,tblhosting.domainstatus FROM tblhosting INNER JOIN tblclients ON tblclients.id=tblhosting.userid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid WHERE ";
        if (is_numeric($value)) {
            $query .= "tblhosting.id='" . $value . "' OR";
        }
        $query .= " domain LIKE '%" . $value . "%' OR username LIKE '%" . $value . "%' OR dedicatedip LIKE '%" . $value . "%' or assignedips LIKE '%" . $value . "%' OR tblhosting.notes LIKE '%" . $value . "%'";
        $query .= " LIMIT 0,10";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $productid = $data["id"];
            $userid = $data["userid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $domain = $data["domain"];
            $productname = $data["name"];
            if (!$domain) {
                $domain = "No Domain";
            }
            $status = $data["domainstatus"];
            $tempmatches .= "<div class=\"searchresult\"><a href=\"clientshosting.php?userid=" . $userid . "&id=" . $productid . "\"><strong>" . $productname . " - " . $domain . "</strong> <span class=\"label " . strtolower($status) . "\">" . $status . "</span><br /><span class=\"desc\">" . $firstname . " " . $lastname . $companyname . " #" . $userid . "</span></a></div>";
        }
        if ($tempmatches) {
            $matches .= "<div class=\"searchresultheader\">Products/Services</div>" . $tempmatches;
        }
    }
    if (checkPermission("List Domains", true) || checkPermission("View Clients Domains", true)) {
        $tempmatches = "";
        $query = "SELECT tblclients.firstname,tblclients.lastname,tblclients.companyname,tbldomains.id,tbldomains.userid,tbldomains.domain,tbldomains.status FROM tbldomains INNER JOIN tblclients ON tblclients.id=tbldomains.userid WHERE ";
        if (is_numeric($value)) {
            $query .= "tbldomains.id='" . $value . "' OR";
        }
        $query .= " domain LIKE '%" . $value . "%' OR tbldomains.additionalnotes LIKE '%" . $value . "%'";
        $query .= " LIMIT 0,10";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $domainid = $data["id"];
            $userid = $data["userid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $domain = $data["domain"];
            if (!$domain) {
                $domain = "No Domain";
            }
            $status = $data["status"];
            $tempmatches .= "<div class=\"searchresult\"><a href=\"clientsdomains.php?userid=" . $userid . "&domainid=" . $domainid . "\"><strong>" . $domain . "</strong> <span class=\"label " . strtolower($status) . "\">" . $status . "</span><br /><span class=\"desc\">" . $firstname . " " . $lastname . $companyname . " #" . $userid . "</span></a></div>";
        }
        if ($tempmatches) {
            $matches .= "<div class=\"searchresultheader\">Domains</div>" . $tempmatches;
        }
    }
    if (checkPermission("List Invoices", true) || checkPermission("Manage Invoice", true)) {
        $query = "SELECT tblclients.firstname,tblclients.lastname,tblclients.companyname,tblinvoices.id,tblinvoices.invoicenum,tblinvoices.userid,tblinvoices.status FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE";
        if (is_numeric($value)) {
            $query .= " tblinvoices.id = '" . $value . "' OR";
        }
        $query .= " tblinvoices.invoicenum LIKE '%" . $value . "%'";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $invoiceid = $data["id"];
            $userid = $data["userid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $status = $data["status"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $id = $data["id"];
            $invoiceNumber = $data["invoicenum"] ? $data["invoicenum"] . " (" . $id . ")" : $id;
            $invoicematches .= "<div class=\"searchresult\"><a href=\"invoices.php?action=edit&id=" . $invoiceid . "\"><strong>Invoice #" . $invoiceNumber . "</strong> <span class=\"label " . strtolower($status) . "\">" . $status . "</span><br><span class=\"desc\">" . $firstname . " " . $lastname . $companyname . " #" . $userid . "</span></a></div>";
        }
    }
    if (checkPermission("List Support Tickets", true) || checkPermission("View Support Ticket", true)) {
        $query = "SELECT id,tid,title FROM tbltickets WHERE tbltickets.tid='" . $value . "' OR tbltickets.title LIKE '%" . $value . "%' ORDER BY lastreply DESC LIMIT 0,10";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $ticketid = $data["id"];
            $tid = $data["tid"];
            $title = $data["title"];
            $ticketmatches .= "<div class=\"searchresult\"><a href=\"supporttickets.php?action=viewticket&id=" . $ticketid . "\"><strong>Ticket #" . $tid . "</strong><br /><span class=\"desc\">" . $title . "</span></a></div>";
        }
    }
    if ($invoicematches) {
        $matches .= "<div class=\"searchresultheader\">Invoices</div>" . $invoicematches;
    }
    if ($ticketmatches) {
        $matches .= "<div class=\"searchresultheader\">Support Tickets</div>" . $ticketmatches;
    }
    $responses = run_hook("IntelligentSearch", array("searchTerm" => $value, "numResults" => 10));
    $hookSearchResults = array();
    foreach ($responses as $response) {
        foreach ($response as $result) {
            if (is_string($result)) {
                $hookSearchResults[] = $result;
            }
        }
    }
    if (0 < count($hookSearchResults)) {
        $matches .= "<div class=\"searchresultheader\">Other Search Results</div>" . "<div class=\"searchresult\">" . implode("</div><div class=\"searchresult\">", $hookSearchResults) . "</div>";
    }
    if (!$matches) {
        $matches = "<div class=\"searchresultheader\">No Matches Found!</div>";
    }
    echo $matches;
    exit;
} else {
    if ($clientsearch || $ticketclientsearch) {
        check_token("WHMCS.admin.default");
        if ($clientsearch && !checkPermission("List Clients", true)) {
            exit("Access Denied");
        }
        if ($ticketclientsearch && !checkPermission("List Support Tickets", true)) {
            exit("Access Denied");
        }
        $value = trim($_POST["value"]);
        if (strlen($value) < 3 || is_numeric($value)) {
            exit;
        }
        $value = db_escape_string($value);
        $tempmatches = "";
        $query = "SELECT id,firstname,lastname,companyname,email FROM tblclients WHERE concat(firstname,' ',lastname) LIKE '%" . $value . "%' OR companyname LIKE '%" . $value . "%' OR email LIKE '%" . $value . "%' LIMIT 0,5";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $userid = $data["id"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $email = $data["email"];
            if ($companyname) {
                $companyname = " (" . $companyname . ")";
            }
            $tempmatches .= "<div class=\"searchresult\"><a href=\"#\" onclick=\"searchselectclient('" . $userid . "','" . escapeJSSingleQuotes($firstname . " " . $lastname . $companyname) . "','" . escapeJSSingleQuotes($email) . "');return false\"><strong>" . $firstname . " " . $lastname . $companyname . "</strong> #" . $userid . "<br /><span class=\"desc\">" . $email . "</span></a></div>";
        }
        if ($tempmatches) {
            $matches .= "<div class=\"searchresultheader\">Search Results</div>" . $tempmatches;
        }
        if (!$matches) {
            $matches = "<div class=\"searchresultheader\">No Matches Found!</div>";
        }
        echo $matches;
        exit;
    }
    $searchin = "search";
    if ($type == "clients") {
        if ($field == "ID" || $field == "Client ID") {
            $searchin = "userid";
        } else {
            if ($field == "First Name" || $field == "Last Name" || $field == "Client Name") {
                $searchin = "name";
            } else {
                if ($field == "Company Name") {
                    $searchin = "name";
                } else {
                    if ($field == "Email Address") {
                        $searchin = "email";
                    } else {
                        if ($field == "Address 1") {
                            $searchin = "address1";
                        } else {
                            if ($field == "Address 2") {
                                $searchin = "address2";
                            } else {
                                if ($field == "City") {
                                    $searchin = "city";
                                } else {
                                    if ($field == "State") {
                                        $searchin = "state";
                                    } else {
                                        if ($field == "Postcode") {
                                            $searchin = "postcode";
                                        } else {
                                            if ($field == "Country") {
                                                $searchin = "country";
                                            } else {
                                                if ($field == "Phone Number") {
                                                    $searchin = "phonenumber";
                                                } else {
                                                    if ($field == "CC Last Four") {
                                                        $searchin = "cclastfour";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        redir((string) $searchin . "=" . $q, "clients.php");
    } else {
        if ($type == "orders") {
            if ($field == "Order ID") {
                $searchin = "orderid";
            } else {
                if ($field == "Order #") {
                    $searchin = "ordernum";
                } else {
                    if ($field == "Order Date") {
                        $searchin = "orderdate";
                    } else {
                        if ($field == "Client Name") {
                            $searchin = "clientname";
                        } else {
                            if ($field == "Amount") {
                                $searchin = "amount";
                            }
                        }
                    }
                }
            }
            redir((string) $searchin . "=" . $q, "orders.php");
        } else {
            if ($type == "services") {
                if ($field == "ID" || $field == "Service ID") {
                    $searchin = "id";
                } else {
                    if ($field == "Domain") {
                        $searchin = "domain";
                    } else {
                        if ($field == "Client Name") {
                            $searchin = "clientname";
                        } else {
                            if ($field == "Package" || $field == "Product") {
                                $searchin = "productname";
                            } else {
                                if ($field == "Billing Cycle") {
                                    $searchin = "billingcycle";
                                } else {
                                    if ($field == "Next Due Date") {
                                        $searchin = "nextduedate";
                                    } else {
                                        if ($field == "Status") {
                                            $searchin = "status";
                                        } else {
                                            if ($field == "Username") {
                                                $searchin = "username";
                                            } else {
                                                if ($field == "Dedicated IP") {
                                                    $searchin = "dedicatedip";
                                                } else {
                                                    if ($field == "Assigned IPs") {
                                                        $searchin = "assignedips";
                                                    } else {
                                                        if ($field == "Subscription ID") {
                                                            $searchin = "subscriptionid";
                                                        } else {
                                                            if ($field == "Notes") {
                                                                $searchin = "notes";
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                App::redirectToRoutePath("admin-services-index", array(), array($searchin => $q));
            } else {
                if ($type == "domains") {
                    if ($field == "ID" || $field == "Domain ID") {
                        $searchin = "id";
                    } else {
                        if ($field == "Domain") {
                            $searchin = "domain";
                        } else {
                            if ($field == "Client Name") {
                                $searchin = "clientname";
                            } else {
                                if ($field == "Registrar") {
                                    $searchin = "registrar";
                                } else {
                                    if ($field == "Status") {
                                        $searchin = "status";
                                    } else {
                                        if ($field == "Subscription ID") {
                                            $searchin = "subscriptionid";
                                        } else {
                                            if ($field == "Notes") {
                                                $searchin = "notes";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    App::redirectToRoutePath("admin-domains-index", array(), array($searchin => $q));
                } else {
                    if ($type == "invoices") {
                        if ($field == "Invoice #") {
                            $searchin = "invoicenum";
                        } else {
                            if ($field == "Client Name") {
                                $searchin = "clientname";
                            } else {
                                if ($field == "Line Item") {
                                    $searchin = "lineitem";
                                } else {
                                    if ($field == "Invoice Date") {
                                        $searchin = "invoicedate";
                                    } else {
                                        if ($field == "Due Date") {
                                            $searchin = "duedate";
                                        } else {
                                            if ($field == "Date Paid") {
                                                $searchin = "datepaid";
                                            } else {
                                                if ($field == "Total Due") {
                                                    redir("totalfrom=" . $q . "&totalto=" . $q, "invoices.php");
                                                } else {
                                                    if ($field == "Status") {
                                                        $searchin = "status";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        redir((string) $searchin . "=" . $q, "invoices.php");
                    } else {
                        if ($type == "tickets") {
                            if ($field == "Ticket #") {
                                $searchin = "ticketid";
                            } else {
                                if ($field == "Tag") {
                                    $searchin = "tag";
                                } else {
                                    if ($field == "Subject") {
                                        $searchin = "subject";
                                    } else {
                                        if ($field == "Email Address") {
                                            $searchin = "email";
                                        } else {
                                            if ($field == "Client Name") {
                                                $searchin = "clientname";
                                            }
                                        }
                                    }
                                }
                            }
                            redir((string) $searchin . "=" . $q, "supporttickets.php");
                        }
                    }
                }
            }
        }
    }
}

?>