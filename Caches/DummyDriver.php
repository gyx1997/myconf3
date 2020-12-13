<?php


namespace myConf\Caches;


class DummyDriver implements ICacheDriver
{

    private static $_instance;

    /**
     * 禁止克隆
     */
    private function __clone() {
        //do nothing
    }

    /**
     * APCU Constructor
     */
    private function __construct() {
        //do nothing.
    }

    public static function instance() : DummyDriver {
        if (self::$_instance === null) {
            self::$_instance = new DummyDriver();
        }
        return self::$_instance;
    }

    /**
     * @inheritDoc
     */
    public function get(string $prefix, string $key)
    {
        // TODO: Implement get() method.
        throw $this->_exceptions_miss($key);
    }

    /**
     * @inheritDoc
     */
    public function set(string $prefix, string $key, $value, int $ttl = 0): void
    {
        // TODO: Implement set() method.
    }

    /**
     * @inheritDoc
     */
    public function increase(string $prefix, string $key): void
    {
        // TODO: Implement increase() method.
    }

    /**
     * @inheritDoc
     */
    public function decrease(string $prefix, string $key): void
    {
        // TODO: Implement decrease() method.
    }

    /**
     * @inheritDoc
     */
    public function clear(string $prefix): void
    {
        // TODO: Implement clear() method.
    }

    /**
     * @inheritDoc
     */
    public function optimize(): void
    {
        // TODO: Implement optimize() method.
    }

    /**
     * @inheritDoc
     */
    public function info(): array
    {
        // TODO: Implement info() method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function delete(string $prefix, string $key): void
    {
        // TODO: Implement delete() method.
    }

    private function _exceptions_miss(string $key) : \myConf\Exceptions\CacheMissException {
        return new \myConf\Exceptions\CacheMissException('CACHE_MISS', 'Key "' . $key . '" does not in the cache.');
    }
}