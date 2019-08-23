<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Console;

use Closure;
trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * @param  string    $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;
        $shouldConfirm = $callback instanceof Closure ? call_user_func($callback) : $callback;
        if ($shouldConfirm) {
            if ($this->option('force')) {
                return true;
            }
            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->comment('*     ' . $warning . '     *');
            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->output->writeln('');
            $confirmed = $this->confirm('Do you really wish to run this command?');
            if (!$confirmed) {
                $this->comment('Command Cancelled!');
                return false;
            }
        }
        return true;
    }
    /**
     * Get the default confirmation callback.
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return $this->getLaravel()->environment() == 'production';
        };
    }
}

?>