<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Contracts\Pagination;

interface LengthAwarePaginator extends Paginator
{
    /**
     * Determine the total number of items in the data store.
     *
     * @return int
     */
    public function total();
    /**
     * Get the page number of the last available page.
     *
     * @return int
     */
    public function lastPage();
}

?>