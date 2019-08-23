<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Pagination extends TableQuery
{
    protected $page = 1;
    protected $defaultsort = "ASC";
    protected $defaultorderby = "id";
    protected $name = "default";
    protected $sort = "";
    protected $orderby = "";
    protected $numResults = 0;
    protected $hiddenResults = 0;
    protected $pagination = true;
    protected $validOrderByValues = array();
    protected $basePath = NULL;
    public function __construct($name = "", $defaultorderby = "", $defaultsort = "")
    {
        if ($name) {
            $this->name = $name;
        }
        if ($defaultorderby) {
            $this->setDefaultOrderBy($defaultorderby);
        }
        if ($defaultsort) {
            $this->setDefaultSortDirection($defaultsort);
        }
        return $this;
    }
    public function setBasePath($path)
    {
        $this->basePath = $path;
    }
    public function digestCookieData()
    {
        $sortdata = Cookie::get("SD", true);
        $name = $this->name;
        if (array_key_exists($name, $sortdata)) {
            $orderby = $sortdata[$name]["orderby"];
            if ($orderby) {
                $this->setOrderBy($orderby);
            }
            $orderbysort = $sortdata[$name]["sort"];
            if ($orderbysort) {
                $this->setSortDirection($orderbysort);
            }
        }
        if ($orderby = \App::getFromRequest("orderby")) {
            $this->setOrderBy($orderby);
            $sortdata[$name] = array("orderby" => $this->orderby, "sort" => $this->sort);
            Cookie::set("SD", $sortdata);
            $vars = array();
            if (\App::isInRequest("userid")) {
                $vars["userid"] = (int) \App::getFromRequest("userid");
            }
            $vars["filter"] = 1;
            if (\App::isInRequest("rp")) {
                $vars["rp"] = \App::getFromRequest("rp");
            }
            $uriPath = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
            if (is_string($uriPath)) {
                $queryDelimiter = strpos($uriPath, "?");
                if ($queryDelimiter !== false) {
                    $uriPath = substr($uriPath, 0, $queryDelimiter);
                }
            }
            \App::redirect($uriPath, $vars);
        }
        if ($page = \App::getFromRequest("page")) {
            $this->setPage($page);
        }
        $this->setRecordLimit(\App::get_config("NumRecordstoDisplay"));
    }
    public function setPage($page)
    {
        $this->page = (int) $page;
        return true;
    }
    public function getPage()
    {
        $page = (int) $this->page;
        $totalpages = $this->getTotalPages();
        if ($page < 1) {
            $page = 1;
        }
        if ($totalpages < $page) {
            $page = $totalpages;
        }
        return $page;
    }
    public function setNumResults($num)
    {
        $this->numResults = $num;
    }
    public function getNumResults()
    {
        return (int) $this->numResults;
    }
    public function getTotalPages()
    {
        $pages = ceil($this->getNumResults() / $this->getRecordLimit());
        if ($pages < 1) {
            $pages = 1;
        }
        return $pages;
    }
    public function getStartingNumber()
    {
        $pageNumber = $this->getPage();
        return ($pageNumber - 1) * $this->getRecordLimit() + 1;
    }
    public function getEndingNumber()
    {
        $pageNumber = $this->getPage();
        $number = $pageNumber * $this->getRecordLimit();
        if ($this->getNumResults() < $number) {
            $number = $this->getNumResults();
        }
        return $number;
    }
    public function getPrevPage()
    {
        $page = $this->getPage();
        $pages = $this->getTotalPages();
        if ($page <= 1 || $pages <= 1) {
            return "";
        }
        return $page - 1;
    }
    public function getNextPage()
    {
        $page = $this->getPage();
        $pages = $this->getTotalPages();
        if ($pages <= $page) {
            return "";
        }
        return $page + 1;
    }
    public function setDefaultOrderBy($field)
    {
        $this->defaultorderby = \App::sanitize("a-z", $field);
    }
    public function setDefaultSortDirection($sort)
    {
        $this->defaultsort = strtoupper($sort) == "DESC" ? "DESC" : "ASC";
    }
    public function setOrderBy($field)
    {
        if ($this->orderby == $field) {
            $this->reverseSortDirection();
        } else {
            $this->orderby = $field;
        }
        return true;
    }
    public function setValidOrderByValues($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $this->validOrderByValues = $array;
        return true;
    }
    public function getValidOrderByValues()
    {
        return $this->validOrderByValues;
    }
    public function isValidOrderBy($field)
    {
        return in_array($field, $this->getValidOrderByValues());
    }
    public function getOrderBy()
    {
        if ($this->isValidOrderBy($this->orderby)) {
            return $this->orderby;
        }
        $this->setSortDirection("");
        return $this->defaultorderby;
    }
    public function setSortDirection($sort)
    {
        $this->sort = $sort;
        return true;
    }
    public function reverseSortDirection()
    {
        if ($this->sort == "ASC") {
            $this->sort = "DESC";
        } else {
            $this->sort = "ASC";
        }
        return true;
    }
    public function getSortDirection()
    {
        if (in_array($this->sort, array("ASC", "DESC"))) {
            return $this->sort;
        }
        return $this->defaultsort;
    }
    public function setPagination($boolean)
    {
        $this->pagination = $boolean;
    }
    public function isPaginated()
    {
        return $this->pagination ? true : false;
    }
    public function setHiddenCount($hidden)
    {
        $this->hiddenResults = (int) $hidden;
    }
    public function getHiddenCount()
    {
        return (int) $this->hiddenResults;
    }
    public function getBasePath($withPage = true)
    {
        $basePath = $this->basePath;
        if (!$basePath) {
            $basePath = \App::getPhpSelf();
        }
        if (stristr($basePath, "?") !== false) {
            $basePath .= "&";
        } else {
            $basePath .= "?";
        }
        if (1 < $this->getPage() && $withPage) {
            $basePath .= "page=" . $this->getPage() . "&";
        }
        return $basePath;
    }
    public function getName()
    {
        return $this->name;
    }
}

?>