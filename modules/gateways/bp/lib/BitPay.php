<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\BP;

class BitPay
{
    protected $params = array();
    public function __construct(array $params)
    {
        $this->params = $params;
        if (empty($params["apiKey"])) {
            $this->generatePairingCodeAndApiKey();
        }
    }
    public function getConnectionClient()
    {
        $privateKey = $this->getPrivateKey();
        $publicKey = $this->getPublicKey($privateKey);
        $client = $this->getClient();
        $adapter = $this->getCurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($this->getNetwork());
        $client->setAdapter($adapter);
        return $client;
    }
    protected function getPrivateKey()
    {
        $hexString = base64_decode(\WHMCS\Config\Setting::getValue("BitPayPrivateKey"));
        if (!$hexString) {
            $privateKey = Key\PrivateKey::create("private.key")->generate();
            $hexString = (string) $privateKey;
            \WHMCS\Config\Setting::setValue("BitPayPrivateKey", base64_encode($hexString));
        }
        $privateKey = new Key\PrivateKey();
        $privateKey->setHex($hexString);
        return $privateKey;
    }
    protected function getPublicKey(Key\PrivateKey $privateKey)
    {
        return $privateKey->getPublicKey();
    }
    private function generatePairingCodeAndApiKey()
    {
        \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "bp")->updateOrInsert(array("gateway" => "bp", "setting" => "pairingCode"), array("value" => ""));
        try {
            $client = $this->getConnectionClient();
            $publicKey = $client->getPublicKey();
            $sin = \Bitpay\SinKey::create()->setPublicKey($publicKey)->generate();
            $token = $client->createToken(array("facade" => "merchant", "label" => "WHMCS - " . \WHMCS\Config\Setting::getValue("CompanyName"), "id" => (string) $sin));
            $this->params["pairingCode"] = $token->getPairingCode();
            $this->params["apiKey"] = $token->getToken();
            \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "bp")->updateOrInsert(array("gateway" => "bp", "setting" => "apiKey"), array("value" => $token->getToken()));
            \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "bp")->updateOrInsert(array("gateway" => "bp", "setting" => "pairingCode"), array("value" => $token->getPairingCode()));
        } catch (\Exception $e) {
            throw $e;
        }
    }
    protected function getClient()
    {
        return new Client();
    }
    protected function getCurlAdapter()
    {
        return new \Bitpay\Client\Adapter\CurlAdapter();
    }
    protected function getNetwork()
    {
        $network = "Bitpay\\Network\\Livenet";
        if ($this->params["testMode"]) {
            $network = "Bitpay\\Network\\Testnet";
        }
        return new $network();
    }
}

?>