<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use App;
use WHMCS\Carbon;
use WHMCS\Module\AbstractWidget;
use WHMCS\Session;
/**
 * ToDo Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class ToDo extends AbstractWidget
{
    protected $title = 'To-Do List';
    protected $description = '';
    protected $weight = 60;
    protected $cache = false;
    protected $requiredPermission = 'To-Do List';
    public function getData()
    {
        if (App::getFromRequest('completedtodo')) {
            $results = localApi('UpdateToDoItem', array('itemid' => App::getFromRequest('completedtodo'), 'adminid' => Session::get('adminid'), 'status' => 'Completed'));
        }
        $toDo = localAPI('GetToDoItems', array('status' => 'Incomplete', 'limitstart' => 0, 'limitnum' => 11));
        return isset($toDo['items']['item']) ? $toDo['items']['item'] : [];
    }
    public function generateOutput($data)
    {
        $output = '';
        foreach ($data as $key => $toDoItem) {
            if ($key == 10) {
                $output .= '<a href="todolist.php" class="view-more">View all To-Do list items...</a>';
                continue;
            }
            $date = Carbon::createFromFormat('Y-m-d', $toDoItem['date']);
            if ($toDoItem['duedate'] == '0000-00-00') {
                $duedate = 'Never';
            } else {
                $duedate = Carbon::createFromFormat('Y-m-d', $toDoItem['duedate'])->diffForHumans();
            }
            $id = $toDoItem['id'];
            $title = $toDoItem['title'];
            $description = $toDoItem['description'];
            $admin = $toDoItem['admin'];
            $status = $toDoItem['status'];
            if ($admin == Session::get('adminid')) {
                $assigned = ' <i class="fas fa-user color-green" data-toggle="tooltip" data-placement="bottom" title="Assigned to you"></i>';
            } elseif ($admin > 0) {
                $assigned = ' <i class="fas fa-user color-cyan" data-toggle="tooltip" data-placement="bottom" title="Assigned to somebody else"></i>';
            } else {
                $assigned = ' <i class="fas fa-user color-grey" data-toggle="tooltip" data-placement="bottom" title="Unassigned"></i>';
            }
            $statusColor = 'default';
            if ($status == 'Incomplete') {
                $statusColor = 'danger';
            } elseif ($status == 'New') {
                $statusColor = 'warning';
            } elseif ($status == 'Pending') {
                $statusColor = 'success';
            } elseif ($status == 'In Progress') {
                $statusColor = 'info';
            }
            $status = ' <label class="label label-' . $statusColor . '">' . $status . '</label>';
            $output .= '
                <div class="item">
                    <div class="due">Due ' . $duedate . '</div>
                    <div>
                        <label>
                            <input type="checkbox" value="' . $id . '"> ' . '<a href="todolist.php?action=edit&id=' . $id . '">' . $title . '</a>' . $status . $assigned . '
                        </label>
                    </div>
                </div>';
        }
        if (count($data) == 0) {
            $output = '<div class="text-center">
                No Incomplete To-Do Items.
                <br /><br />
                <a href="todolist.php" class="btn btn-primary btn-sm">Add a To-Do Item</a>
                <br /><br />
            </div>';
        }
        return <<<EOF
<div class="widget-content-padded to-do-items">
    {$output}
</div>
<script>
jQuery(document).ready(function(){
    jQuery('.to-do-items input').iCheck({
        inheritID: true,
        checkboxClass: 'icheckbox_flat-blue'
    });
    jQuery('.widget-todo').on('ifChecked', '.item input', function(event) {
        refreshWidget('ToDo', 'refresh=1&completedtodo=' + \$(this).val());
    });
});
</script>
<style>
.to-do-items {
    margin-top: -2px;
    font-size: 0.9em;
}
.to-do-items .item {
    margin: 2px 0 0 0;
    padding: 3px 10px;
    background-color: #f8f8f8;
    line-height: 26px;
}
.to-do-items label {
    margin-bottom: 0;
    font-weight: normal;
}
.to-do-items .item .icheckbox_flat-blue {
    margin-top: -2px;
    margin-right: 5px;
}
.to-do-items .due {
    float: right;
    font-size: 0.85em;
    color: #999;
}
.to-do-items .view-more {
    display: block;
    padding: 10px 0 0 0;
    text-align: center;
    text-decoration: underline;
    font-size: 1em;
}
</style>
EOF;
    }
}

?>