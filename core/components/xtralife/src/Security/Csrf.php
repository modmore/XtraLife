<?php

namespace modmore\XtraLife\Security;

use modmore\XtraLife\Security\Csrf\StorageInterface;

class Csrf {
    protected StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Gets or generates a key - makes for multi-use tokens.
     *
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        $token = $this->storage->get($key);
        if ($token === false) {
            $token = $this->generate($key);
        }

        if ($this->isExpired($token)) {
            $token = $this->generate($key);
        }

        return $token;
    }

    /**
     * Generates a key - makes for single-use tokens.
     *
     * @param string $key
     * @return string
     */
    public function generate(string $key): string
    {
        $parts = [];

        // Generation time
        $parts[] = time();

        // Random bytes
        $parts[] = bin2hex(random_bytes(64));

        $token = implode('--', $parts);
        $token = base64_encode($token);

        $this->storage->set($key, $token);

        return $token;
    }

    /**
     * Checks if the token is valid.
     *
     * @param string $key
     * @param string $token
     * @return bool
     */
    public function check(string $key, string $token): bool
    {
        $expected = $this->storage->get($key);
        if ($expected !== $token) {
            return false;
        }
        return true;
    }

    public function checkOnce(string $key, string $token): bool
    {
        $result = $this->check($key, $token);
        $this->generate($key);
        return $result;
    }

    private function isExpired($token): bool
    {
        $token = base64_decode($token);
        $token = explode('--', $token);
        $timestamp = reset($token);

        // Make sure we have a timestamp
        if (!is_numeric($timestamp)) {
            return true;
        }

        // Make sure it's no older than 1 day
        $ttl = 24 * 60 * 60; // tokens valid for 1 day
        if ($timestamp + $ttl < time()) {
            return true;
        }

        // And also isn't in the future
        if ($timestamp > time()) {
            return true;
        }

        return false;
    }
}
