<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

abstract class BaseProjectEntity
{
    public $project = NULL;
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
    public function project()
    {
        return $this->project;
    }
}

?>