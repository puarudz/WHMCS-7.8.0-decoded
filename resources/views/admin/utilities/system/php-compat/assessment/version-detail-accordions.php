<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$parentAccordionId = "accordion" . $phpVersionId;
echo "\n<div class=\"database-backups\">\n    <div id=\"";
echo $parentAccordionId;
echo "\"\n         class=\"panel-group panel-backup-options\"\n         role=\"tablist\"\n         aria-multiselectable=\"true\"\n    >\n        ";
foreach ($assessmentGroups as $assessment => $group) {
    $rowData = $group->getData();
    $headingId = $group->getHeadingId();
    $collapseId = $group->getCollapseId();
    $tableId = $group->getTableId();
    $title = $group->getTitle();
    $desc = $group->getDescription();
    $titleClass = $group->getTitleCssClass();
    $iconClass = $group->getTitleIconClass();
    $items = $group->getTitleBadgeCount();
    $body = implode("\n", $rowData);
    echo "            <div class=\"panel panel-";
    echo $titleClass;
    echo "\">\n                <div class=\"panel-heading\"\n                     role=\"tab\"\n                     id=\"";
    echo $headingId;
    echo "\"\n                >\n                    <h4 class=\"panel-title\">\n                        <i class=\"fas ";
    echo $iconClass;
    echo "\"></i>&nbsp;&nbsp;\n                        <a class=\"collapsed\"\n                           role=\"button\"\n                           data-toggle=\"collapse\"\n                           data-parent=\"#";
    echo $parentAccordionId;
    echo "\"\n                           href=\"#";
    echo $collapseId;
    echo "\"\n                           aria-expanded=\"false\"\n                           aria-controls=\"";
    echo $collapseId;
    echo "\"\n                        >\n                            ";
    echo $title;
    echo "                        </a>\n                        <span class=\"badge pull-right\">";
    echo $items;
    echo "</span>\n                    </h4>\n                </div>\n                <div id=\"";
    echo $collapseId;
    echo "\"\n                     class=\"panel-collapse collapse\"\n                     role=\"tabpanel\"\n                     aria-labelledby=\"";
    echo $headingId;
    echo "\">\n                    <div class=\"panel-body\">\n                        <div>";
    echo $desc;
    echo "</div>\n                        <table id=\"";
    echo $tableId;
    echo "\"\n                               class=\"table table-condensed data-driven tblcompat\"\n                               data-ordering=\"true\"\n                               data-dom='<\"listtable\"ft>p'\n                               data-searching=\"true\"\n                               data-paging=\"true\"\n                               data-page-length=\"50\"\n                        >\n                            <thead>\n                            <tr>\n                                <th>";
    echo AdminLang::trans("phpCompatUtil.file");
    echo "</th>\n                            </tr>\n                            </thead>\n                            <tbody>\n                                ";
    echo $body;
    echo "                            </tbody>\n                        </table>\n                    </div>\n                </div>\n            </div>\n            ";
}
echo "    </div>\n</div>\n";

?>