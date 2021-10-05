<?php

namespace modmore\XtraLife\Security\Csrf;

interface StorageInterface {
    /**
     * @param string $key
     * @return string|bool
     */
    public function get(string $key);

    /**
     * @param string $key
     * @param string $token
     * @return void
     */
    public function set(string $key, string $token);
}