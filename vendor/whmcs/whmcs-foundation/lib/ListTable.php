<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class ListTable
{
    private $pagination = true;
    private $columns = array();
    private $rows = array();
    private $output = array();
    private $showmassactionbtnstop = false;
    private $massactionurl = "";
    private $massactionbtns = "";
    private $showHidden = false;
    private $hideInactiveTogglePages = array("addons", "clients", "domains", "services");
    private $aInt = NULL;
    private $sortableTableCount = 0;
    private $pageObj = NULL;
    public function __construct(Pagination $obj, $tableCount = 0, Admin $adminInterface = NULL)
    {
        $this->pageObj = $obj;
        $this->sortableTableCount = $tableCount;
        $this->aInt = $adminInterface;
    }
    public function getPageObj()
    {
        return $this->pageObj;
    }
    public function setPagination($boolean)
    {
        $this->pagination = $boolean;
    }
    public function isPaginated()
    {
        return $this->pagination ? true : false;
    }
    public function setMassActionURL($url)
    {
        $this->massactionurl = $url;
        return true;
    }
    public function getMassActionURL()
    {
        $url = $this->massactionurl;
        if (!$url) {
            $url = $_SERVER["PHP_SELF"];
        }
        if (strpos($url, "?")) {
            $url .= "&";
        } else {
            $url .= "?";
        }
        $url .= "filter=1";
        return $url;
    }
    public function setMassActionBtns($btns)
    {
        $this->massactionbtns = $btns;
        return true;
    }
    public function getMassActionBtns()
    {
        return $this->massactionbtns;
    }
    public function setShowMassActionBtnsTop($boolean)
    {
        $this->showmassactionbtnstop = $boolean;
        return true;
    }
    public function getShowMassActionBtnsTop()
    {
        return $this->showmassactionbtnstop ? true : false;
    }
    public function setColumns($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $this->columns = $array;
        $orderbyvals = array();
        foreach ($array as $vals) {
            if (is_array($vals) && $vals[0]) {
                $orderbyvals[] = $vals[0];
            }
        }
        $this->getPageObj()->setValidOrderByValues($orderbyvals);
        return true;
    }
    public function getColumns()
    {
        return $this->columns;
    }
    public function addRow($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $this->rows[] = $array;
        return true;
    }
    public function addExpandRow($output)
    {
        if (!is_string($output)) {
            return false;
        }
        $this->rows[] = array("expandline", $output);
        return true;
    }
    public function getRows()
    {
        return $this->rows;
    }
    public function outputTableHeader()
    {
        $numResults = $this->getPageObj()->getNumResults();
        $basePath = $this->getPageObj()->getBasePath(false);
        $hiddenOutput = $this->getHiddenOutput();
        $showing = "";
        if ($numResults) {
            $showing = ", " . \AdminLang::trans("global.showing", array(":start" => $this->getPageObj()->getStartingNumber(), ":end" => $this->getPageObj()->getEndingNumber()));
        }
        $content = "<form id=\"frmRecordsFound\" method=\"post\" action=\"" . $basePath . "filter=1\">\n    <div class=\"row\">\n        <div class=\"col-md-6 col-sm-12\">\n            " . $numResults . " " . \AdminLang::trans("global.recordsfound") . $showing . "\n        </div>\n        <div class=\"col-md-6 col-sm-12 text-right\">\n            <div class=\"right-margin-5 inline\">" . $hiddenOutput["right"] . "</div>";
        $content .= $this->getPageDropdown();
        $content .= "</div>\n    </div>\n</form>\n";
        $this->addOutput($content);
    }
    public function outputTable()
    {
        $aInt = $this->aInt;
        $orderby = $this->getPageObj()->getOrderBy();
        $sortDirection = $this->getPageObj()->getSortDirection();
        $content = "";
        $massActionForm = false;
        if ($this->getMassActionURL()) {
            $content .= "<form method=\"post\" action=\"" . $this->getMassActionURL() . "\">";
            $massActionForm = true;
        }
        if ($this->getShowMassActionBtnsTop()) {
            $content .= "<div style=\"padding-bottom:2px;\">" . \AdminLang::trans("global.withselected") . ": " . $this->getMassActionBtns() . "</div>";
        }
        $content .= "\n<div class=\"tablebg\">\n<table id=\"sortabletbl" . $this->sortableTableCount . "\" class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr>";
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if (is_array($column)) {
                $sortableheader = true;
                list($columnid, $columnname) = $column;
                $width = isset($column[2]) ? $column[2] : "";
                if (!$columnid) {
                    $sortableheader = false;
                }
            } else {
                $sortableheader = false;
                $columnid = $width = "";
                $columnname = $column;
            }
            $extra = "";
            if (\App::isInRequest("userid")) {
                $extra = "userid=" . (int) \App::getFromRequest("userid") . "&";
            }
            if (!$columnname) {
                $content .= "<th width=\"20\"></th>";
            } else {
                if ($columnname == "checkall") {
                    $aInt->internaljquerycode[] = "\n\$('#checkall" . $this->sortableTableCount . "').click(function (event) {\n    // Starting from the checkbox that got the event,\n    // Climb up the tree until you reach .datatable\n    // Then find all the input elements within just this table\n    // Then make their checked status match the master checkbox.\n    \$(event.target).parents('.datatable').find('input').prop('checked',this.checked);\n});";
                    $content .= "<th width=\"20\"><input type=\"checkbox\" id=\"checkall" . $this->sortableTableCount . "\"></th>";
                } else {
                    $width = $width ? " width=\"" . $width . "\"" : "";
                    $content .= "<th" . $width . ">";
                    if ($sortableheader) {
                        $basePath = $this->getPageObj()->getBasePath();
                        $content .= "<a href=\"" . $basePath . $extra . "orderby=" . $columnid . "\">";
                    }
                    $content .= $columnname;
                    if ($sortableheader) {
                        $content .= "</a>";
                        if ($orderby == $columnid) {
                            $content .= " <img src=\"images/" . strtolower($sortDirection) . ".gif\" class=\"absmiddle\" />";
                        }
                    }
                    $content .= "</th>";
                }
            }
        }
        $content .= "</tr>\n";
        $totalcols = count($columns);
        $rows = $this->getRows();
        if (count($rows)) {
            foreach ($rows as $vals) {
                if ($vals[0] == "dividingline") {
                    $content .= "<tr><td colspan=\"" . $totalcols . "\" style=\"background-color:#efefef;\"><div align=\"left\"><b>" . $vals[1] . "</b></div></td></tr>";
                } else {
                    if ($vals[0] == "expandline") {
                        $content .= "<tr class=\"hidden\"><td colspan=\"" . $totalcols . "\" style=\"background-color:#efefef;\">" . $vals[1] . "</td></tr>";
                    } else {
                        $trAttributes = array();
                        if (is_array($vals[0])) {
                            if (isset($vals[0]["trAttributes"]) && is_array($vals[0]["trAttributes"])) {
                                $trAttributes = $vals[0]["trAttributes"];
                            }
                            $vals[0] = isset($vals[0]["output"]) ? $vals[0]["output"] : "";
                        }
                        $content .= "<tr";
                        foreach ($trAttributes as $trKey => $trValue) {
                            $content .= " " . $trKey . "=\"" . $trValue . "\"";
                        }
                        $content .= ">";
                        foreach ($vals as $val) {
                            $content .= "<td>" . $val . "</td>";
                        }
                        $content .= "</tr>";
                    }
                }
            }
        } else {
            if (0 < $this->getPageObj()->getHiddenCount()) {
                $message = \AdminLang::trans("global.hiddenRecordsFound", array(":numHidden" => $this->getPageObj()->getHiddenCount()));
                $message = "<div class=\"margin-top-bottom-25 no-results\">" . $message . "</div>";
                $content .= "<tr>" . "<td colspan=\"" . $totalcols . "\" class=\"text-center\">" . $message . "</td>" . "</tr>";
            } else {
                $content .= "<tr><td colspan=\"" . $totalcols . "\" class=\"text-center\">" . \AdminLang::trans("global.norecordsfound") . "</td></tr>";
            }
        }
        $content .= "</table>\n</div>";
        if ($this->getMassActionBtns()) {
            $content .= \AdminLang::trans("global.withselected") . ": " . $this->getMassActionBtns() . "\n";
        }
        if ($massActionForm) {
            $content .= "</form>";
        }
        $this->addOutput($content);
    }
    public function outputTablePagination()
    {
        $prevPage = $this->getPageObj()->getPrevPage();
        $nextPage = $this->getPageObj()->getNextPage();
        $thisPage = $this->getPageObj()->getPage();
        $content = "<div class=\"text-center\"><ul class=\"pagination\">";
        $basePath = $this->getPageObj()->getBasePath(false);
        if ($prevPage) {
            $content .= "<li class=\"previous\">" . "<a class=\"page-selector\" href=\"#\" data-page=\"" . ($thisPage - 1) . "\">" . "&laquo; " . \AdminLang::trans("global.previouspage") . "</a>";
        } else {
            $content .= "<li class=\"previous disabled\">" . "<span class=\"page-selector\">" . "&laquo; " . \AdminLang::trans("global.previouspage") . "</span>";
        }
        $content .= "</li>";
        $totalPages = $this->getPageObj()->getTotalPages();
        $halfPages = floor($totalPages / 2);
        $pageList = array_unique(array(1, 2, 3, $thisPage - 1, $thisPage, $thisPage + 1, $totalPages - 2, $totalPages - 1, $totalPages, $halfPages), SORT_NUMERIC);
        sort($pageList, SORT_NUMERIC);
        $pageList = array_filter($pageList, function ($value) use($totalPages) {
            if ($value < 1 || $totalPages < $value) {
                return false;
            }
            return true;
        });
        $previousPage = 0;
        foreach ($pageList as $page) {
            $pageOutput = $page;
            $class = "page-selector";
            $aOrSpan = "a";
            if ($page == $thisPage) {
                $pageOutput = "<strong>" . $page . "</strong>";
                $class .= " active";
                $aOrSpan = "span";
            }
            if ($previousPage && 1 < $page - $previousPage) {
                $content .= "<li class=\"hidden-xs\">" . "<span onclick=\"return false;\">...</span></li>";
            }
            $content .= "<li class=\"hidden-xs\">" . "<" . $aOrSpan . " href=\"#\" class=\"" . $class . "\" data-page=\"" . $page . "\">" . $pageOutput . "</" . $aOrSpan . "></li>";
            $previousPage = $page;
        }
        if ($nextPage) {
            $content .= "<li class=\"next\">" . "<a class=\"page-selector\" href=\"#\" data-page=\"" . ($thisPage + 1) . "\">" . \AdminLang::trans("global.nextpage") . " &raquo;" . "</a>";
        } else {
            $content .= "<li class=\"next disabled\"><span class=\"page-selector\">" . \AdminLang::trans("global.nextpage") . " &raquo;" . "</span>";
        }
        $content .= "</li></ul></div>";
        $this->addOutput($content);
    }
    public function addOutput($content)
    {
        $this->output[] = $content;
    }
    public function output()
    {
        if ($this->isPaginated()) {
            $this->outputTableHeader();
        }
        $this->outputTable();
        if ($this->isPaginated()) {
            $this->outputTablePagination();
        }
        return implode("\n", $this->output);
    }
    protected function getHiddenOutput()
    {
        $left = $right = "";
        $numHidden = $this->getPageObj()->getHiddenCount();
        if (isset($numHidden) && in_array($this->getPageObj()->getName(), $this->hideInactiveTogglePages)) {
            $basePath = $this->getPageObj()->getBasePath();
            $title = \AdminLang::trans("global.hideInactive") . " (" . $numHidden . ")";
            if (\App::isInRequest("show_hidden")) {
                $this->setShowHidden((bool) (int) \App::getFromRequest("show_hidden"));
            }
            $checked = "";
            if (!$this->isShowingHidden()) {
                $checked = " checked=\"checked\"";
                $left = " (" . $numHidden . " " . \AdminLang::trans("global.hidden") . ")";
            }
            $right = "<input type=\"checkbox\"\n                             id=\"checkboxShowHidden\"\n                             class=\"checkbox-switch\"\n                             data-label-text=\"" . $title . "\"\n                             " . $checked . "\n                      /> ";
            $aInt = $this->aInt;
            if ($aInt) {
                $aInt->internaljquerycode[] = "jQuery(\"#checkboxShowHidden\").bootstrapSwitch({\n    size: 'mini',\n    onSwitchChange: function (event, state) {\n        var showHidden = '" . $this->isShowingHidden() . "' ? 0 : 1;\n        window.location = '" . $basePath . "filter=1&show_hidden=' + showHidden;\n    }\n});";
            }
        }
        return array("left" => $left, "right" => $right);
    }
    protected function getPageDropdown()
    {
        $thisPage = $this->getPageObj()->getPage();
        $totalPages = $this->getPageObj()->getTotalPages();
        $pageList = range(1, $totalPages);
        sort($pageList, SORT_NUMERIC);
        $dropdownEntries = "";
        foreach ($pageList as $page) {
            $dropdownEntries .= $this->dropdownOption($page);
        }
        $showHidden = (int) $this->isShowingHidden();
        $hiddenInput = "<input type=\"hidden\" name=\"show_hidden\" value=\"" . $showHidden . "\">";
        $hiddenInput .= "<input type=\"hidden\" name=\"page\" value=\"" . $thisPage . "\">";
        $content = (string) $hiddenInput . "\n<div class=\"btn-group paging-dropdown\">\n    <button type=\"button\"\n            class=\"btn btn-default dropdown-toggle btn-xs\"\n            data-toggle=\"dropdown\"\n            aria-haspopup=\"true\"\n            aria-expanded=\"false\"\n    >\n        <span id=\"currentPage\">" . $thisPage . "</span>\n        <span class=\"fas fa-caret-down fa-fw\"></span>\n        <span class=\"sr-only\">Toggle Dropdown</span>\n    </button>\n    <ul class=\"dropdown-menu dropdown-menu-page dropdown-menu-right\">\n        " . $dropdownEntries . "\n    </ul>\n</div>";
        return $content;
    }
    protected function dropdownOption($pageNumber)
    {
        return "<li class=\"text-center\">\n    <a href=\"#\" class=\"dropdown-page-selector\" data-page=\"" . $pageNumber . "\">\n        " . $pageNumber . "\n    </a>\n</li>";
    }
    public function setShowHidden($state)
    {
        $this->showHidden = $state;
    }
    public function isShowingHidden()
    {
        return $this->showHidden;
    }
}

?>