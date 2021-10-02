<?php

namespace modmore\XtraLife\Security;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use RuntimeException;

class Encryption {
    public const KEY_DEFAULT = 'XTRALIFE_ENCRYPTION_KEY';

    /**
     * @param $plaintext
     * @param $key
     * @return string
     * @throws EnvironmentIsBrokenException
     */
    public static function encrypt($plaintext, $key): string
    {
        return Crypto::encrypt($plaintext, self::getKey($key));
    }

    /**
     * @param $ciphertext
     * @param $key
     * @return string
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public static function decrypt($ciphertext, $key): string
    {
        return Crypto::decrypt($ciphertext, self::getKey($key));
    }

    /**
     * @param $key
     * @return Key
     * @throws EnvironmentIsBrokenException
     */
    private static function getKey($key): Key
    {
        $masterKey = array_key_exists($key, $_ENV) ? base64_decode($_ENV[$key]) : false;
        if (!$masterKey) {
            throw new RuntimeException('Called Encryption::encrypt with invalid key "' . $key . '" that does not exist in environment');
        }
        try {
            $privateEncKey = Key::loadFromAsciiSafeString($masterKey);
        } catch (BadFormatException $e) {
            throw new RuntimeException('Error loading key ' . $key . ' into Defuse\Crypto: ' . $e->getMessage(), 0, $e);
        }
        return $privateEncKey;
    }
}
