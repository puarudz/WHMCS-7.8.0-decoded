<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Search;

class IntelligentSearch implements SearchInterface
{
    protected $searchTerm = "";
    protected $numResults = 10;
    protected $more = "";
    protected $hideInactive = 0;
    const TYPE_CLIENT = "client";
    const TYPE_CONTACT = "contact";
    const TYPE_SERVICE = "service";
    const TYPE_DOMAIN = "domain";
    const TYPE_INVOICE = "invoice";
    const TYPE_TICKET = "ticket";
    const TYPE_OTHER = "other";
    public function search($searchInput = array())
    {
        $searchTerm = $searchInput["term"];
        $numResults = $searchInput["numResults"];
        $this->hideInactive = (int) $searchInput["hideInactive"];
        $searchTerm = trim($searchTerm);
        if (strlen($searchTerm) < 3 && !is_numeric($searchTerm)) {
            throw new \WHMCS\Exception\Information(\AdminLang::trans("search.searchTermTooShort"));
        }
        $this->searchTerm = $searchTerm;
        $this->numResults = $numResults;
        $this->more = $searchInput["more"];
        $searchResults = array(self::TYPE_CLIENT => $this->searchClients(), self::TYPE_CONTACT => $this->searchContacts(), self::TYPE_SERVICE => $this->searchServices(), self::TYPE_DOMAIN => $this->searchDomains(), self::TYPE_INVOICE => $this->searchInvoices(), self::TYPE_TICKET => $this->searchTickets(), self::TYPE_OTHER => array());
        $responses = run_hook("IntelligentSearch", array("searchTerm" => $searchTerm, "hideInactive" => $this->hideInactive, "numResults" => $numResults));
        $hookSearchResults = array();
        foreach ($responses as $response) {
            foreach ($response as $result) {
                if (is_string($result) || is_array($result)) {
                    $hookSearchResults[] = $result;
                }
            }
        }
        if (0 < count($hookSearchResults)) {
            $searchResults[self::TYPE_OTHER] = $hookSearchResults;
        }
        return $searchResults;
    }
    protected function searchClients()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "clients") {
            return $searchResults;
        }
        if (checkPermission("List Clients", true) || checkPermission("View Clients Summary", true)) {
            $matchingClients = \WHMCS\Database\Capsule::table("tblclients")->select(array("id", "firstname", "lastname", "companyname", "email", "status"))->where(function ($whereQuery) use($searchTerm) {
                if (3 < strlen($searchTerm)) {
                    $whereQuery->where(\WHMCS\Database\Capsule::raw("CONCAT(firstname,' ',lastname)"), "LIKE", "%" . $searchTerm . "%")->orWhere("companyname", "LIKE", "%" . $searchTerm . "%")->orWhere("address1", "LIKE", "%" . $searchTerm . "%")->orWhere("address2", "LIKE", "%" . $searchTerm . "%")->orWhere("postcode", "LIKE", "%" . $searchTerm . "%")->orWhere("phonenumber", "LIKE", "%" . $searchTerm . "%")->orWhere("tax_id", "LIKE", "%" . $searchTerm . "%");
                }
                if (is_numeric($searchTerm)) {
                    $whereQuery->orWhere("id", $searchTerm);
                }
                if (is_numeric($searchTerm) && strlen($searchTerm) == 4) {
                    $whereQuery->orWhere("cardlastfour", $searchTerm);
                }
                if (!is_numeric($searchTerm)) {
                    $whereQuery->orWhere("email", "LIKE", "%" . $searchTerm . "%")->orWhere("city", "LIKE", "%" . $searchTerm . "%")->orWhere("state", "LIKE", "%" . $searchTerm . "%");
                }
            });
            if ($this->hideInactive) {
                $matchingClients->where("status", "Active");
            }
            $totalResults = $matchingClients->count();
            if ($this->more != "clients") {
                $matchingClients->limit($numResults);
            } else {
                $matchingClients->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingClients->get() as $client) {
                $searchResults[] = array("id" => $client->id, "name" => $client->firstname . " " . $client->lastname, "company_name" => $client->companyname, "email" => $client->email, "status" => $client->status, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["name"], $secondResult["name"]);
            });
        }
        return $searchResults;
    }
    protected function searchContacts()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "contacts") {
            return $searchResults;
        }
        if (checkPermission("List Clients", true) || checkPermission("View Clients Summary", true)) {
            $matchingContacts = \WHMCS\Database\Capsule::table("tblcontacts")->select(array("tblcontacts.id", "userid", "tblcontacts.firstname", "tblcontacts.lastname", "tblcontacts.companyname", "tblcontacts.email"))->where(function ($whereQuery) use($searchTerm) {
                if (3 < strlen($searchTerm)) {
                    $whereQuery->where(\WHMCS\Database\Capsule::raw("CONCAT(tblcontacts.firstname,' ',tblcontacts.lastname)"), "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.companyname", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.address1", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.address2", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.postcode", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.phonenumber", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.tax_id", "LIKE", "%" . $searchTerm . "%");
                }
                if (is_numeric($searchTerm)) {
                    $whereQuery->orWhere("tblcontacts.id", $searchTerm);
                } else {
                    $whereQuery->orWhere("tblcontacts.email", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.city", "LIKE", "%" . $searchTerm . "%")->orWhere("tblcontacts.state", "LIKE", "%" . $searchTerm . "%");
                }
            });
            if ($this->hideInactive) {
                $matchingContacts->join("tblclients", "tblcontacts.userid", "=", "tblclients.id");
                $matchingContacts->where("tblclients.status", "Active");
            }
            $totalResults = $matchingContacts->count();
            if ($this->more != "contacts") {
                $matchingContacts->limit($numResults);
            } else {
                $matchingContacts->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingContacts->get() as $contact) {
                $searchResults[] = array("id" => $contact->id, "user_id" => $contact->userid, "name" => $contact->firstname . " " . $contact->lastname, "company_name" => $contact->companyname, "email" => $contact->email, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["name"], $secondResult["name"]);
            });
        }
        return $searchResults;
    }
    protected function searchServices()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "services") {
            return $searchResults;
        }
        if (checkPermission("List Services", true) || checkPermission("View Clients Products/Services", true)) {
            $matchingServices = \WHMCS\Database\Capsule::table("tblhosting")->select(array("tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblhosting.id", "tblhosting.userid", "tblhosting.domain", "tblproducts.name", "tblhosting.domainstatus"))->join("tblclients", "tblclients.id", "=", "tblhosting.userid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid");
            $matchingServices->where(function ($whereQuery) use($searchTerm) {
                if (is_numeric($searchTerm)) {
                    $whereQuery->where("tblhosting.id", $searchTerm);
                }
                if (3 < strlen($searchTerm)) {
                    $whereQuery->orWhere("domain", "LIKE", "%" . $searchTerm . "%")->orWhere("username", "LIKE", "%" . $searchTerm . "%")->orWhere("dedicatedip", "LIKE", "%" . $searchTerm . "%")->orWhere("assignedips", "LIKE", "%" . $searchTerm . "%")->orWhere("tblhosting.notes", "LIKE", "%" . $searchTerm . "%");
                }
            });
            if ($this->hideInactive) {
                $matchingServices->where("tblclients.status", "Active");
            }
            $totalResults = $matchingServices->count();
            if ($this->more != "services") {
                $matchingServices->limit($numResults);
            } else {
                $matchingServices->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingServices->get() as $service) {
                $searchResults[] = array("id" => $service->id, "user_id" => $service->userid, "client_name" => $service->firstname . " " . $service->lastname, "client_company_name" => $service->companyname, "product_name" => $service->name, "domain" => $service->domain, "status" => $service->domainstatus, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["domain"], $secondResult["domain"]);
            });
        }
        return $searchResults;
    }
    protected function searchDomains()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "domains") {
            return $searchResults;
        }
        if (checkPermission("List Domains", true) || checkPermission("View Clients Domains", true)) {
            $matchingDomains = \WHMCS\Database\Capsule::table("tbldomains")->select(array("tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tbldomains.id", "tbldomains.userid", "tbldomains.domain", "tbldomains.status"))->join("tblclients", "tblclients.id", "=", "tbldomains.userid")->where(function ($whereQuery) use($searchTerm) {
                if (3 < strlen($searchTerm)) {
                    $whereQuery->where("domain", "LIKE", "%" . $searchTerm . "%")->orWhere("additionalnotes", "LIKE", "%" . $searchTerm . "%");
                }
                if (is_numeric($searchTerm)) {
                    $whereQuery->orWhere("tbldomains.id", $searchTerm);
                }
            });
            if ($this->hideInactive) {
                $matchingDomains->where("tblclients.status", "Active");
            }
            $totalResults = $matchingDomains->count();
            if ($this->more != "domains") {
                $matchingDomains->limit($numResults);
            } else {
                $matchingDomains->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingDomains->get() as $domain) {
                $searchResults[] = array("id" => $domain->id, "user_id" => $domain->userid, "client_name" => $domain->firstname . " " . $domain->lastname, "client_company_name" => $domain->companyname, "domain" => $domain->domain, "status" => $domain->status, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["domain"], $secondResult["domain"]);
            });
        }
        return $searchResults;
    }
    protected function searchInvoices()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "invoices") {
            return $searchResults;
        }
        if (checkPermission("List Invoices", true) || checkPermission("Manage Invoice", true)) {
            $matchingInvoices = \WHMCS\Database\Capsule::table("tblinvoices")->select(array("tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblinvoices.id", "tblinvoices.userid", "tblinvoices.invoicenum", "tblinvoices.date", "tblinvoices.total", "tblinvoices.status", "tblinvoices.paymentmethod"))->join("tblclients", "tblclients.id", "=", "tblinvoices.userid")->where(function ($whereQuery) use($searchTerm) {
                $whereQuery->where("invoicenum", "LIKE", "%" . $searchTerm . "%");
                if (is_numeric($searchTerm)) {
                    $whereQuery->orWhere("tblinvoices.id", $searchTerm);
                }
            });
            if ($this->hideInactive) {
                $matchingInvoices->where("tblclients.status", "Active");
            }
            $totalResults = $matchingInvoices->count();
            if ($this->more != "invoices") {
                $matchingInvoices->limit($numResults);
            } else {
                $matchingInvoices->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingInvoices->get() as $invoice) {
                $searchResults[] = array("id" => $invoice->id, "number" => $invoice->invoicenum ? $invoice->invoicenum : $invoice->id, "user_id" => $invoice->userid, "client_name" => $invoice->firstname . " " . $invoice->lastname, "client_company_name" => $invoice->companyname, "date" => $invoice->date, "paymentmethod" => $invoice->paymentmethod, "total" => $invoice->total, "status" => $invoice->status, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["number"], $secondResult["number"]);
            });
        }
        return $searchResults;
    }
    protected function searchTickets()
    {
        $searchTerm = $this->searchTerm;
        $numResults = $this->numResults;
        $searchResults = array();
        if ($this->more && $this->more != "tickets") {
            return $searchResults;
        }
        if (checkPermission("List Support Tickets", true) || checkPermission("View Support Ticket", true)) {
            $matchingTickets = \WHMCS\Database\Capsule::table("tbltickets")->select(array("tbltickets.id", "tbltickets.tid", "tbltickets.did", "tbltickets.userid", "tbltickets.date", "tbltickets.title", "tbltickets.urgency", "tbltickets.status", "tbltickets.lastreply", "tbltickets.name", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname"))->leftJoin("tblclients", "tblclients.id", "=", "tbltickets.userid")->where(function ($whereQuery) use($searchTerm) {
                $whereQuery->where("tid", $searchTerm)->orWhere("title", "LIKE", "%" . $searchTerm . "%");
            })->orderBy("lastreply", "desc");
            $ticketDepartments = null;
            if ($this->hideInactive) {
                $matchingTickets->where(function ($whereQuery) {
                    $whereQuery->where("tblclients.status", "Active")->orWhereNull("tblclients.status");
                });
            }
            $totalResults = $matchingTickets->count();
            if ($this->more != "tickets") {
                $matchingTickets->limit($numResults);
            } else {
                $matchingTickets->offset($numResults)->limit(PHP_INT_MAX);
            }
            foreach ($matchingTickets->get() as $ticket) {
                if (is_null($ticketDepartments)) {
                    $ticketDepartments = \WHMCS\Support\Department::pluck("name", "id");
                }
                $searchResults[] = array("id" => $ticket->id, "mask" => $ticket->tid, "department_id" => $ticket->did, "department_name" => $ticketDepartments[$ticket->did], "user_id" => $ticket->userid, "client_name" => $ticket->userid ? $ticket->firstname . " " . $ticket->lastname : $ticket->name, "company_name" => $ticket->companyname, "date" => $ticket->date, "subject" => $ticket->title, "priority" => $ticket->urgency, "status" => $ticket->status, "last_reply" => $ticket->lastreply, "totalResults" => $totalResults);
            }
            usort($searchResults, function ($firstResult, $secondResult) {
                return strcasecmp($firstResult["mask"], $secondResult["mask"]);
            });
        }
        return $searchResults;
    }
}

?>