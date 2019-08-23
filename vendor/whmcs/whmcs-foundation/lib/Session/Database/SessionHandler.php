<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Session\Database;

final class SessionHandler implements \SessionHandlerInterface
{
    protected $config = NULL;
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }
    public function open($savePath, $sessionName)
    {
        return true;
    }
    public function close()
    {
        return true;
    }
    private function logError($message)
    {
        if ($this->config->getLogErrors()) {
            logActivity("Database session handling error: " . $message);
        }
    }
    public function read($sessionId)
    {
        try {
            $dataRow = (object) $this->getQuery()->where("session_id", $sessionId)->first();
            if (!$dataRow) {
                return "";
            }
            if (isset($dataRow->last_activity)) {
                $sessionStaleTimestamp = \Carbon\Carbon::now()->subMinutes($this->config->getLifetime())->getTimestamp();
                if ($dataRow->last_activity < $sessionStaleTimestamp) {
                    return "";
                }
            }
            if (isset($dataRow->payload)) {
                return base64_decode($dataRow->payload);
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
        return "";
    }
    public function write($sessionId, $data)
    {
        try {
            $query = "INSERT INTO `" . $this->config->getTable() . "` SET" . " session_id = ?," . " payload = ?," . " last_activity = ?" . " ON DUPLICATE KEY UPDATE" . " session_id = VALUES(session_id)," . " payload = VALUES(payload)," . " last_activity = VALUES(last_activity)";
            $bindings = array($sessionId, base64_encode($data), \Carbon\Carbon::now()->getTimestamp());
            $this->config->getConnection()->statement($query, $bindings);
            return true;
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    public function destroy($sessionId)
    {
        $this->getQuery()->where("session_id", $sessionId)->delete();
        return true;
    }
    public function gc($lifetime)
    {
        $this->getQuery()->where("last_activity", "<=", \Carbon\Carbon::now()->getTimestamp() - $lifetime)->delete();
    }
    protected function getQuery()
    {
        $config = $this->config;
        return $config->getConnection()->table($config->getTable());
    }
}

?>