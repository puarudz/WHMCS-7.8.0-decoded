<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Log\Register;

class DailyCronReport extends \WHMCS\Log\Register
{
    public function __construct(array $attributes = array())
    {
        if (empty($attributes["namespace"])) {
            $attributes["namespace"] = "cron.dailyreport";
        }
        if (empty($attributes["namespace_id"])) {
            $attributes["namespace_id"] = 0;
        }
        if (empty($attributes["name"])) {
            $attributes["name"] = "Daily Cron Report";
        }
        parent::__construct($attributes);
    }
    public function getValue()
    {
        $value = parent::getValue();
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        return $value;
    }
    public function setValue($value)
    {
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        return parent::setValue($value);
    }
    public function write($value)
    {
        $this->setValue($value);
        parent::save();
    }
    protected function taskToArray(\WHMCS\Scheduling\Task\TaskInterface $task, $completed = true)
    {
        return array("id" => $task->id, "name" => $task->getName(), "output_ids" => $task->getLatestOutputs()->pluck("id"), "completed" => $completed);
    }
    public function start()
    {
        $dateTime = \WHMCS\Carbon::now()->toDateTimeString();
        $this->write(array("startTime" => $dateTime, "tasks" => array(), "finishTime" => $dateTime));
    }
    public function finish()
    {
        $current = $this->getValue();
        $current["finishTime"] = \WHMCS\Carbon::now()->toDateTimeString();
        $this->write($current);
    }
    public function completed(\WHMCS\Scheduling\Task\TaskInterface $task)
    {
        $current = $this->getValue();
        $current["tasks"][] = $this->taskToArray($task, true);
        $this->write($current);
        return $this;
    }
    public function notCompleted(\WHMCS\Scheduling\Task\TaskInterface $task)
    {
        $current = json_decode($this->getValue(), true);
        $current["tasks"][] = $this->taskToArray($task, false);
        $this->write(json_encode($current));
        return $this;
    }
    public function taskDataToHtml(array $taskData)
    {
        $html = "";
        if (empty($taskData["completed"])) {
        }
        $template = "<table width=\"90%\" class=\"grid-block-inner\" cellpadding=\"0\" cellspacing=\"0\">\n    <tr><td align=\"center\" style=\"text-align:center;font-size:18px;font-weight:100;color:#555;\">%task_name%</td></tr>\n    <tr><td align=\"center\" style=\"text-align:center;font-size:46px;color:#333;height:50px;\">%value%</td></tr>\n    <tr><td align=\"center\" style=\"text-align:center;font-size:14px;font-weight:100;color:#555;\">%success_keyword%</td></tr>\n</table>";
        $value = 0;
        if (!empty($taskData["output_ids"])) {
            $task = \WHMCS\Scheduling\Task\AbstractTask::find($taskData["id"]);
            if (!$task) {
                return "";
            }
            if ($task instanceof \WHMCS\Cron\Task\CheckForWhmcsUpdate) {
                return "";
            }
            if ($task->isBooleanStatusItem()) {
                return "";
            }
            $successOutputIds = $task->getSuccessCountIdentifier();
            if (!is_array($successOutputIds)) {
                $successOutputIds = array($successOutputIds);
            }
            foreach ($taskData["output_ids"] as $id) {
                $output = (new \WHMCS\Log\Register())->where("id", $id)->first();
                if (!$output) {
                    continue;
                }
                $outputNamespace = preg_replace("/^" . $task->getSystemName() . "\\./", "", $output->getNamespace());
                if (in_array($outputNamespace, $successOutputIds)) {
                    $value += (int) $output->getValue();
                }
            }
            $html .= str_replace(array("%task_name%", "%value%", "%success_keyword%"), array($task->getName(), $value, $task->getSuccessKeyword()), $template);
        }
        return $html;
    }
    public function toHtmlDigest()
    {
        $data = $this->getValue();
        $html = "%s\n%s\n<table class=\"grid-block\" cellpadding=\"0\" cellspacing=\"0\">\n    <tr>\n        <td class=\"grid-block\" width=\"558\" style=\"padding-bottom: 8px;\">\n            <table cellpadding=\"0\" cellspacing=\"0\">\n                <tr>\n                    <td class=\"row three\" style=\"padding-top: 8px;\">\n                        <table cellpadding=\"0\" cellspacing=\"0\">\n                            <tr>\n                                %s\n                            </tr>\n                        </table>\n                    </td>\n                </tr>\n            </table>\n        </td>\n    </tr>\n</table>\n%s\n<br>\n<div style=\"text-align:center;\">\n    <a href=\"%s\" style=\"display:inline-block;padding:10px 15px;background-color:#336699;color:#fff;border-radius:4px;text-decoration:none;font-weight:normal;\">\n        View Full Summary\n    </a>\n</div>";
        return sprintf($html, $this->cronStatus($data), $this->updateAvailableInfoAlert($data), $this->taskStatus($data), $this->moduleQueueStatus($data), $this->viewSummaryLink());
    }
    protected function updateAvailableInfoAlert($data)
    {
        $updateAvailable = false;
        $updateVersion = new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION);
        $html = "";
        if (!empty($data["tasks"])) {
            foreach ($data["tasks"] as $taskDetail) {
                if (!empty($taskDetail["output_ids"])) {
                    $task = \WHMCS\Scheduling\Task\AbstractTask::find($taskDetail["id"]);
                    if (!$task || !$task instanceof \WHMCS\Cron\Task\CheckForWhmcsUpdate) {
                        continue;
                    }
                    foreach ($taskDetail["output_ids"] as $id) {
                        $output = (new \WHMCS\Log\Register())->where("id", $id)->first();
                        if (!$output) {
                            continue;
                        }
                        if (strpos($output->getNamespace(), "update.available") !== false) {
                            $updateAvailable = (bool) $output->getValue();
                        } else {
                            if (strpos($output->getNamespace(), "update.version") !== false) {
                                $version = $output->getValue();
                                if (\WHMCS\Version\SemanticVersion::isSemantic($version)) {
                                    $updateVersion = new \WHMCS\Version\SemanticVersion($version);
                                }
                            }
                        }
                        if ($updateAvailable) {
                            if (\WHMCS\Version\SemanticVersion::compare($updateVersion, new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION), "==")) {
                                $newVersionText = "A new update is available. ";
                            } else {
                                $newVersionText = "A new update is available: " . $updateVersion->getCasual() . ". ";
                            }
                            $info_css = "background-color:#d9edf7;" . "color:#333;" . "margin: 8px 0px 0px;";
                            $whmcs = \DI::make("app");
                            $updateLink = $whmcs->getSystemURL() . $whmcs->get_admin_folder_name() . "/update.php";
                            $html = "<div style=\"padding:10px 20px;text-align:center;" . $info_css . "\">" . $newVersionText . " <a href=\"" . $updateLink . "\" target=\"_blank\">Click here</a> to update now." . "</div>";
                        }
                    }
                }
            }
        }
        return $html;
    }
    protected function cronStatus($data)
    {
        $completed = $incomplete = 0;
        if (!empty($data["tasks"])) {
            foreach ($data["tasks"] as $taskDetail) {
                if ($taskDetail["completed"]) {
                    $completed++;
                } else {
                    $incomplete++;
                }
            }
            if ($incomplete < 1) {
                $message = "All cron automation tasks completed successfully";
                $success_css = "background-color:#d4f1ce;color:#3d841a;";
            } else {
                if ($completed < 1) {
                    $message = "No cron automation tasks performed";
                    $success_css = "background-color:#f7c3c3;color:#b70e0e;";
                } else {
                    $message = "Only " . $completed . " cron automation tasks completed";
                    $success_css = "background-color:#f7c3c3;color:#b70e0e;";
                }
            }
        } else {
            $message = "No cron automation tasks performed";
            $success_css = "background-color:##f7c3c3;color:#b70e0e;";
        }
        $html = "<div style=\"padding:10px 20px;text-align:center;" . $success_css . "\">" . $message . "</div>";
        return $html;
    }
    protected function taskStatus($data)
    {
        $body = "";
        $columnCount = 0;
        foreach ($data["tasks"] as $taskData) {
            if (count($taskData["output_ids"]) == 0) {
                continue;
            }
            $taskContent = $this->taskDataToHtml($taskData);
            if (!$taskContent) {
                continue;
            }
            $body .= "<td class=\"section\" width=\"180\" height=\"124\" align=\"center\" valign=\"middle\" style=\"font-family: arial,sans-serif; color: #555; background: #efefef;\">" . $taskContent . "</td>";
            $columnCount++;
            if ($columnCount <= 2) {
                $body .= "<td class=\"spacer\" width=\"9\" style=\"font-size: 1px;\">&nbsp;</td>";
            } else {
                if ($columnCount == 3) {
                    $body .= "</tr>\n                        </table>\n                    </td>\n                </tr>\n                <tr>\n                    <td class=\"row three row-nopadding\" style=\"padding-top: 8px;\">\n                        <table cellpadding=\"0\" cellspacing=\"0\">\n                            <tr>";
                    $columnCount = 0;
                }
            }
        }
        return $body;
    }
    protected function viewSummaryLink()
    {
        $whmcs = \DI::make("app");
        return $whmcs->getSystemURL() . $whmcs->get_admin_folder_name() . "/automationstatus.php?date=" . date("Y-m-d");
    }
    protected function moduleQueueStatus($data)
    {
        $queueCount = \WHMCS\Module\Queue::incomplete()->count();
        $html = "";
        if ($queueCount) {
            $whmcs = \DI::make("app");
            $link = $whmcs->getSystemURL() . $whmcs->get_admin_folder_name() . "/modulequeue.php";
            $html = "<div style=\"padding:10px 20px;text-align:center;background-color:#fff3d2;color:#b7973d;\">" . "<strong>" . $queueCount . "</strong> Pending Module Actions in Queue." . " <a href=\"" . $link . "\" style=\"color:#b7973d;\">Go to Module Queue &raquo;</a>" . "</div>";
        }
        return $html;
    }
}

?>