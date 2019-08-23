<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\Crypto\Cipher;

use InvalidArgumentException;
use LogicException;
/**
 * An implementation of the CBC cipher for use with an AesEncryptingStream or
 * AesDecrypting stream.
 */
class Cbc implements CipherMethod
{
    const BLOCK_SIZE = 16;
    /**
     * @var string
     */
    private $baseIv;
    /**
     * @var string
     */
    private $iv;
    /**
     * @var int
     */
    private $keySize;
    /**
     * @param string $iv Base Initialization Vector for the cipher.
     * @param int $keySize Size of the encryption key, in bits, that will be
     *                     used.
     *
     * @throws InvalidArgumentException Thrown if the passed iv does not match
     *                                  the iv length required by the cipher.
     */
    public function __construct($iv, $keySize = 256)
    {
        $this->baseIv = $this->iv = $iv;
        $this->keySize = $keySize;
        if (strlen($iv) !== openssl_cipher_iv_length($this->getOpenSslName())) {
            throw new InvalidArgumentException('Invalid initialization vector');
        }
    }
    public function getOpenSslName()
    {
        return "aes-{$this->keySize}-cbc";
    }
    public function getAesName()
    {
        return 'AES/CBC/PKCS5Padding';
    }
    public function getCurrentIv()
    {
        return $this->iv;
    }
    public function requiresPadding()
    {
        return true;
    }
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($offset === 0 && $whence === SEEK_SET) {
            $this->iv = $this->baseIv;
        } else {
            throw new LogicException('CBC initialization only support being' . ' rewound, not arbitrary seeking.');
        }
    }
    public function update($cipherTextBlock)
    {
        $this->iv = substr($cipherTextBlock, self::BLOCK_SIZE * -1);
    }
}

?>