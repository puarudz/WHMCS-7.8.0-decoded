<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Helper;

class SortableTable
{
    protected static $tables = array();
    protected static $sortData = array();
    protected $tableId = 0;
    protected $orderBy = "";
    protected $orderDirection = "";
    protected $page = 0;
    protected $totalPages = 0;
    protected $rowLimit = 1;
    protected $sortPage = NULL;
    protected $pagination = false;
    protected $sortableTableCount = 0;
    protected $defaultRowLimit = 50;
    protected $jqueryCode = array();
    protected $rowsOfData = 0;
    protected $request = NULL;
    public function __construct(\WHMCS\Http\Message\ServerRequest $request)
    {
        $this->setRequest($request);
        $tableId = count(static::$tables) + 1;
        $this->setTableId($tableId);
        static::$tables[$tableId] = $this;
    }
    public function fetchUserSortData()
    {
        if (static::$sortData) {
            return static::$sortData;
        }
        $sortData = isset($_COOKIE["sortdata"]) ? $_COOKIE["sortdata"] : "";
        $sortData = @json_decode(@base64_decode($sortData), true);
        if (!is_array($sortData)) {
            $sortData = array();
        }
        static::$sortData = $sortData;
        return $sortData;
    }
    public function storeUserSortData(array $data = array())
    {
        static::$sortData = $data;
        setcookie("sortdata", base64_encode(json_encode($data)));
    }
    public function factorySimpleTable($page = 0, $limit = NULL)
    {
        $new = new static($this->getRequest());
        if (!$page) {
            $page = 0;
        }
        if ($limit < 1) {
            $limit = $this->defaultRowLimit;
        }
        $new->setPagination(false)->setPage((int) $page)->setRowLimit((int) $limit);
        return $new;
    }
    public function factoryPaginatedTable($tableNamespace, $columnOrderBy, $defaultOrderDirection = NULL, $page = 0, $limit = NULL)
    {
        $new = new static($this->getRequest());
        $newTableId = $new->getTableId();
        $sortData = $this->fetchUserSortData();
        $fallbackOrderBy = $columnOrderBy;
        $previousOrderBy = !empty($sortData[$tableNamespace . $newTableId . "orderby"]) ? $sortData[$tableNamespace . $newTableId . "orderby"] : $fallbackOrderBy;
        $previousOrderDirection = !empty($sortData[$tableNamespace . $newTableId . "order"]) ? $sortData[$tableNamespace . $newTableId . "order"] : $defaultOrderDirection;
        if ($newTableId != (int) $this->getRequest()->get("table", 0)) {
            $columnOrderBy = "";
        }
        if ($previousOrderBy && $previousOrderBy == $columnOrderBy) {
            if ($previousOrderDirection == "ASC") {
                $orderDirection = "DESC";
            } else {
                $orderDirection = "ASC";
            }
        } else {
            if ($defaultOrderDirection) {
                $orderDirection = $defaultOrderDirection;
            } else {
                if ($previousOrderDirection) {
                    $orderDirection = $previousOrderDirection;
                } else {
                    $orderDirection = "ASC";
                }
            }
        }
        if (!$columnOrderBy) {
            $columnOrderBy = $previousOrderBy;
        }
        $orderBy = $columnOrderBy;
        $orderBy = trim(preg_replace("/[^a-z0-9_]/", "", strtolower($orderBy)));
        if (!in_array($orderDirection, array("ASC", "DESC"))) {
            $orderDirection = "ASC";
        }
        if ($orderBy) {
            $sortData[$tableNamespace . $newTableId . "orderby"] = $orderBy;
        } else {
            if (!isset($sortData[$tableNamespace . $newTableId . "orderby"])) {
                $sortData[$tableNamespace . $newTableId . "orderby"] = $fallbackOrderBy;
            }
        }
        $sortData[$tableNamespace . $newTableId . "order"] = $orderDirection;
        $this->storeUserSortData($sortData);
        if (!$page) {
            $page = 0;
        }
        if ($limit < 1) {
            $limit = $this->defaultRowLimit;
        }
        $new->setPagination(true)->setPage((int) $page)->setRowLimit((int) $limit)->setOrderBy($orderBy)->setOrderDirection($orderDirection);
        return $new;
    }
    public function getHtml($columns, $tableData, $formUrl = "", $formButtons = "", $topButtons = "")
    {
        if (!is_array($tableData)) {
            $tableData = array();
        }
        $tableDataRange = array();
        $rowsOfData = $this->getRowsOfData();
        if (!$rowsOfData) {
            $rowsOfData = count($tableData);
            foreach ($tableData as $data) {
                if (isset($data[0]) && $data[0] == "dividingline") {
                    $rowsOfData--;
                }
            }
            $page = $this->getPage();
            if ($page) {
                $tableDataRange = array("start" => $page * $this->getRowLimit(), "end" => $page * $this->getRowLimit() + $this->getRowLimit());
            } else {
                $tableDataRange = array("start" => 0, "end" => $this->getRowLimit());
            }
            $this->setRowsOfData($rowsOfData);
        }
        $totalPages = ceil($rowsOfData / $this->getRowLimit());
        if ($totalPages == 0) {
            $totalPages = 1;
        }
        $this->setTotalPages($totalPages);
        $html = "";
        if ($this->hasPagination()) {
            $html .= $this->getPaginationForm();
        }
        if ($formUrl) {
            $html .= "<form method=\"post\" action=\"" . $formUrl . "\">" . $this->getHiddenInputHtml() . PHP_EOL;
        }
        if ($topButtons) {
            $html .= "<div style=\"padding-bottom:2px;\">" . \AdminLang::trans("global.withselected") . ": " . $formButtons . "</div>";
        }
        $html .= "<div class=\"tablebg\">" . PHP_EOL . "<table id=\"sortabletbl" . $this->getTableId() . "\" class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" " . "cellpadding=\"3\">" . "<tr>";
        foreach ($columns as $column) {
            if (is_array($column)) {
                $sortableHeader = true;
                list($columnId, $columnName, $width) = $column;
                if (!$columnId) {
                    $sortableHeader = false;
                }
            } else {
                $sortableHeader = false;
                $columnId = $width = "";
                $columnName = $column;
            }
            if (!$columnName) {
                $html .= "<th width=\"20\"></th>";
            } else {
                if ($columnName == "checkall") {
                    $js = "\$(\"#checkall" . $this->getTableId() . "\").click(function () { " . PHP_EOL . "\$(\"#sortabletbl" . $this->getTableId() . " .checkall\").attr(\"checked\",this.checked);" . PHP_EOL . "});";
                    $this->addJqueryCode($js);
                    $html .= "<th width=\"20\">" . "<input type=\"checkbox\" id=\"checkall" . $this->getTableId() . "\"></th>";
                } else {
                    if ($width) {
                        $html .= "<th width=\"" . $width . "\"\">";
                    } else {
                        $html .= "<th>";
                    }
                    if ($sortableHeader) {
                        $basePath = requestedRoutableQueryUriPath($this->getRequest());
                        $html .= "<a href=\"" . $basePath;
                        foreach ($_REQUEST as $key => $value) {
                            if ($key != "orderby" && $key != "PHPSESSID" && $key != "rp" && $key != "table" && $key != "tab" && $value) {
                                $html .= "&" . $key . "=" . $value;
                            }
                        }
                        $html .= "&table=" . $this->getTableId() . "&orderby=" . $columnId . "\">";
                    }
                    $html .= $columnName;
                    if ($sortableHeader) {
                        $html .= "</a>";
                        if ($this->getOrderBy() == $columnId) {
                            $html .= " <img src=\"images/" . strtolower($this->getOrderDirection()) . ".gif\" class=\"absmiddle\" />";
                        }
                    }
                    $html .= "</th>";
                }
            }
        }
        $html .= "</tr>" . PHP_EOL;
        $totalColumns = count($columns);
        if (is_array($tableData) && count($tableData)) {
            $rowIteration = 0;
            foreach ($tableData as $tableDataValues) {
                if ($tableDataValues[0] == "dividingline") {
                    $html .= "<tr>" . "<td colspan=\"" . $totalColumns . "\" style=\"background-color:#efefef;\">" . "<div align=\"left\"><b>" . $tableDataValues[1] . "</b></div>" . "</td></tr>" . PHP_EOL;
                } else {
                    if (empty($tableDataRange) || $tableDataRange["start"] <= $rowIteration && $rowIteration < $tableDataRange["end"]) {
                        $html .= "<tr>";
                        foreach ($tableDataValues as $value) {
                            $html .= "<td>" . $value . "</td>";
                        }
                        $html .= "</tr>" . PHP_EOL;
                    }
                    $rowIteration++;
                }
            }
        } else {
            $html .= "<tr>" . "<td colspan=\"" . $totalColumns . "\">" . \AdminLang::trans("global.norecordsfound") . "</td></tr>" . PHP_EOL;
        }
        $html .= "</table>" . PHP_EOL . "</div>" . PHP_EOL;
        if ($formButtons) {
            $html .= \AdminLang::trans("global.withselected") . ": " . $formButtons;
        }
        if ($formUrl) {
            $html .= "</form>";
        }
        if ($this->hasPagination()) {
            $html .= $this->getPaginationSelector();
        }
        return $html;
    }
    protected function getHiddenInputHtml()
    {
        $hiddenInputs = array();
        $requestVariables = $_REQUEST;
        foreach (array("orderby", "page", "PHPSESSID", "token", "table", "rp", "tab") as $nonPropagatingParam) {
            if (isset($requestVariables[$nonPropagatingParam])) {
                unset($requestVariables[$nonPropagatingParam]);
            }
        }
        foreach ($requestVariables as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($v) {
                        $hiddenInputs[] = "<input type=\"hidden\" name=\"" . $key . "[" . $k . "]\" " . "value=\"" . $v . "\" />";
                    }
                }
            } else {
                $hiddenInputs[] = "<input type=\"hidden\" name=\"" . $key . "\" " . "value=\"" . $value . "\" />";
            }
        }
        $hiddenInputs[] = "<input type=\"hidden\" name=\"table\" " . "value=\"" . $this->getTableId() . "\" />";
        return implode(PHP_EOL, $hiddenInputs);
    }
    protected function getPaginationSelector()
    {
        $html = "<ul class=\"pager\">";
        $currentPage = $this->getPage();
        if (0 < $currentPage) {
            $previousOffset = $currentPage - 1;
            $basePath = requestedRoutableQueryUriPath($this->getRequest());
            $html .= "<li class=\"previous\">" . "<a href=\"" . $basePath;
            foreach ($_REQUEST as $key => $value) {
                if ($key != "orderby" && $key != "page" && $key != "PHPSESSID" && $key != "rp" && $key != "table" && $key != "tab" && $value) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            if ($v) {
                                $html .= $key . "[" . $k . "]=" . $v . "&";
                            }
                        }
                    } else {
                        $html .= (string) $key . "=" . $value . "&";
                    }
                }
            }
            $html .= "table=" . $this->getTableId() . "&page=" . $previousOffset . "\">&laquo; " . \AdminLang::trans("global.previouspage") . "</a></li>";
        } else {
            $html .= "<li class=\"previous disabled\">" . "<a href=\"#\">&laquo; " . \AdminLang::trans("global.previouspage") . "</a></li>";
        }
        $rowLimit = $this->getRowLimit();
        $expectedLastPage = ($currentPage * $rowLimit + $rowLimit) / $rowLimit;
        if ($expectedLastPage == $this->getTotalPages()) {
            $html .= "<li class=\"next disabled\"><a href=\"#\">" . \AdminLang::trans("global.nextpage") . " &raquo;</a></li>";
        } else {
            $newOffset = $currentPage + 1;
            $basePath = requestedRoutableQueryUriPath($this->getRequest());
            $html .= "<li class=\"next\"><a href=\"" . $basePath;
            foreach ($_REQUEST as $key => $value) {
                if ($key != "orderby" && $key != "page" && $key != "PHPSESSID" && $key != "rp" && $key != "table" && $key != "tab" && $value) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            if ($v) {
                                $html .= $key . "[" . $k . "]=" . $v . "&";
                            }
                        }
                    } else {
                        $html .= (string) $key . "=" . $value . "&";
                    }
                }
            }
            $html .= "table=" . $this->getTableId() . "&page=" . $newOffset . "\">" . \AdminLang::trans("global.nextpage") . " &raquo;</a></li>";
        }
        $html .= "</ul>";
        return $html;
    }
    protected function getPaginationForm()
    {
        $currentPage = $this->getPage();
        $totalPages = $this->getTotalPages();
        $basePath = requestedRoutableQueryUriPath($this->getRequest());
        $html = "<form method=\"get\" action=\"" . $basePath . "\">" . PHP_EOL . $this->getHiddenInputHtml() . PHP_EOL . "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">" . "<tr>" . "<td width=\"50%\" align=\"left\">" . $this->getRowsOfData() . " " . \AdminLang::trans("global.recordsfound") . ", " . \AdminLang::trans("global.page") . " " . ($currentPage + 1) . " " . \AdminLang::trans("global.of") . " " . $totalPages . "</td>" . "<td width=\"50%\" align=\"right\">" . \AdminLang::trans("global.jumppage") . ": <select name=\"page\" onchange=\"submit()\">" . PHP_EOL;
        for ($i = 1; $i <= $totalPages; $i++) {
            $newPage = $i - 1;
            $html .= "<option value=\"" . $newPage . "\"";
            if ($currentPage == $newPage) {
                $html .= " selected";
            }
            $html .= ">" . $i . "</option>" . PHP_EOL;
        }
        $html .= "</select> <input type=\"submit\" value=\"" . \AdminLang::trans("global.go") . "\" class=\"btn btn-xs btn-default\" /></td>" . "</tr></table>" . PHP_EOL . "</form>" . PHP_EOL;
        return $html;
    }
    public function getTableId()
    {
        return $this->tableId;
    }
    public function setTableId($tableId)
    {
        $this->tableId = $tableId;
        return $this;
    }
    public function getOrderBy()
    {
        return $this->orderBy;
    }
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }
    public function setOrderDirection($orderDirection)
    {
        $this->orderDirection = $orderDirection;
        return $this;
    }
    public function getPage()
    {
        return $this->page;
    }
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }
    public function getRowLimit()
    {
        return $this->rowLimit;
    }
    public function setRowLimit($rowLimit)
    {
        $this->rowLimit = $rowLimit;
        return $this;
    }
    public function getSortPage()
    {
        return $this->sortPage;
    }
    public function setSortPage($sortPage)
    {
        $this->sortPage = $sortPage;
        return $this;
    }
    public function hasPagination()
    {
        return $this->pagination;
    }
    public function setPagination($pagination)
    {
        $this->pagination = $pagination;
        return $this;
    }
    public function getSortableTableCount()
    {
        return $this->sortableTableCount;
    }
    public function setSortableTableCount($sortableTableCount)
    {
        $this->sortableTableCount = $sortableTableCount;
        return $this;
    }
    public function getDefaultRowLimit()
    {
        return $this->defaultRowLimit;
    }
    public function setDefaultRowLimit($defaultRowLimit)
    {
        $this->defaultRowLimit = $defaultRowLimit;
        return $this;
    }
    public function getJqueryCode()
    {
        return $this->jqueryCode;
    }
    public function addJqueryCode($jqueryCode)
    {
        $this->jqueryCode[] = $jqueryCode;
        return $this;
    }
    public function getTotalPages()
    {
        return $this->totalPages;
    }
    public function setTotalPages($totalPages)
    {
        $this->totalPages = $totalPages;
        return $this;
    }
    public function getRowsOfData()
    {
        return $this->rowsOfData;
    }
    public function setRowsOfData($rowsOfData)
    {
        $this->rowsOfData = $rowsOfData;
        return $this;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }
}

?>