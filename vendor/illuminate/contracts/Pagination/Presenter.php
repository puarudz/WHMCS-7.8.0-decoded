<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Contracts\Pagination;

interface Presenter
{
    /**
     * Render the given paginator.
     *
     * @return \Illuminate\Contracts\Support\Htmlable|string
     */
    public function render();
    /**
     * Determine if the underlying paginator being presented has pages to show.
     *
     * @return bool
     */
    public function hasPages();
}

?>