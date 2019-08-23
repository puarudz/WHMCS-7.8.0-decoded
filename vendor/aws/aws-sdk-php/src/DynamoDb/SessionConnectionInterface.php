<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\DynamoDb;

/**
 * The session connection provides the underlying logic for interacting with
 * Amazon DynamoDB and performs all of the reading and writing operations.
 */
interface SessionConnectionInterface
{
    /**
     * Reads session data from DynamoDB
     *
     * @param string $id Session ID
     *
     * @return array
     */
    public function read($id);
    /**
     * Writes session data to DynamoDB
     *
     * @param string $id        Session ID
     * @param string $data      Serialized session data
     * @param bool   $isChanged Whether or not the data has changed
     *
     * @return bool
     */
    public function write($id, $data, $isChanged);
    /**
     * Deletes session record from DynamoDB
     *
     * @param string $id Session ID
     *
     * @return bool
     */
    public function delete($id);
    /**
     * Performs garbage collection on the sessions stored in the DynamoDB
     *
     * @return bool
     */
    public function deleteExpired();
}

?>