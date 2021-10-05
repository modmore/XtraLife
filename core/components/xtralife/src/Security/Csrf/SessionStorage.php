<?php

namespace modmore\XtraLife\Security\Csrf;

class SessionStorage implements StorageInterface {
    private const SESSION_KEY = 'csrf_tokens';

    public function get(string $key)
    {
        if (array_key_exists(self::SESSION_KEY, $_SESSION) && array_key_exists($key, $_SESSION[self::SESSION_KEY])) {
            return $_SESSION[self::SESSION_KEY][$key];
        }
        return false;
    }

    public function set(string $key, string $token)
    {
        if (!array_key_exists(self::SESSION_KEY, $_SESSION)) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$key] = $token;
    }
}