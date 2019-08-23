<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;
class PostgresProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $results = $query->getConnection()->selectFromWriteConnection($sql, $values);
        $sequence = $sequence ?: 'id';
        $result = (array) $results[0];
        $id = $result[$sequence];
        return is_numeric($id) ? (int) $id : $id;
    }
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        $mapping = function ($r) {
            $r = (object) $r;
            return $r->column_name;
        };
        return array_map($mapping, $results);
    }
}

?>