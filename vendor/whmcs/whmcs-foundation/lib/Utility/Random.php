<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Random
{
    private $numbers = "0123456789";
    private $lowercase = "abcdefghijklmnopqrstuvwxyz";
    private $uppercase = "ABCDEFGHIJKLMNOPQRSTUVYWXYZ";
    private $symbols = "!@#*-+()[];:.";
    private function selectCharacters($from, $number)
    {
        $str = "";
        $totalItems = strlen($this->{$from}) - 1;
        for ($i = 0; $i < $number; $i++) {
            $str .= substr($this->{$from}, rand(0, $totalItems), 1);
        }
        return $str;
    }
    public function string($lowercase, $uppercase, $numbers, $symbols)
    {
        $characters = $this->selectCharacters("numbers", $numbers) . $this->selectCharacters("lowercase", $lowercase) . $this->selectCharacters("uppercase", $uppercase) . $this->selectCharacters("symbols", $symbols);
        $password = "";
        $length = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            if (\WHMCS\Environment\Php::isFunctionAvailable("random_int")) {
                $randomPos = random_int(0, strlen($characters) - 1);
            } else {
                $randomPos = rand(0, strlen($characters) - 1);
            }
            $password .= substr($characters, $randomPos, 1);
            $characters = substr($characters, 0, $randomPos) . substr($characters, $randomPos + 1);
        }
        return $password;
    }
    public function number($length)
    {
        return $this->string(0, 0, $length, 0);
    }
}

?>