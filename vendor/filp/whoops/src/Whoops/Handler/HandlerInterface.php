<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */
namespace Whoops\Handler;

use Whoops\Exception\Inspector;
use Whoops\RunInterface;
interface HandlerInterface
{
    /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle();
    /**
     * @param  RunInterface  $run
     * @return void
     */
    public function setRun(RunInterface $run);
    /**
     * @param  \Throwable $exception
     * @return void
     */
    public function setException($exception);
    /**
     * @param  Inspector $inspector
     * @return void
     */
    public function setInspector(Inspector $inspector);
}

?>