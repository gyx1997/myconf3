<?php


namespace myConf\Caches;


class APCUDriver implements ICacheDriver
{

    /**
     * @var \Redis Redis实例对象
     */
    private $_redis_object;

    private static $_instance = null;

    /**
     * Returns an instance of APCU Driver.
     * @return APCUDriver
     */
    public static function instance() : APCUDriver {
        if (self::$_instance === null) {
            self::$_instance = new APCUDriver();
        }
        return self::$_instance;
    }

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

    /**
     * @param string $prefix
     * @param string $key
     * @param $value
     * @param int $ttl
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set(string $prefix, string $key, $value, int $ttl = 0) : void {
        //组装新key
        $tk = $this->_make_key($prefix, $key);
        $data = serialize($value);
        if(apcu_store($tk, $data, $ttl) === false) {
            throw $this->_exceptions_driver($tk, 'set');
        }
    }

    /**
     * @param string $prefix
     * @param string $key
     * @return mixed
     * @throws \myConf\Exceptions\CacheDriverException
     * @throws \myConf\Exceptions\CacheMissException
     */
    public function get(string $prefix, string $key) {
        //组装新key。
        $tk = $this->_make_key($prefix, $key);
        if (apcu_exists($tk) === false) {
            throw $this->_exceptions_miss($tk);
        }
        $result = apcu_fetch($tk);
        if ($result === false) {
            throw $this->_exceptions_driver($tk, 'get');
        }
        return unserialize($result);
    }

    /**
     * @param string $prefix
     */
    public function clear(string $prefix) : void {
        apcu_clear_cache();
    }

    /**
     *
     */
    public function optimize() : void {
        // TODO: Implement optimize() method.
        // dummy method
    }

    /**
     * @param string $prefix
     * @param string $key
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function increase(string $prefix, string $key) : void {
        $tk = $this->_make_key($prefix, $key);
        if (apcu_inc($tk) === false) {
            throw $this->_exceptions_driver($tk, 'increase');
        }
    }

    /**
     * @param string $prefix
     * @param string $key
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function decrease(string $prefix, string $key) : void {
        $tk = $this->_make_key($prefix, $key);
        if (apcu_dec($tk) === false) {
            throw $this->_exceptions_driver($tk, 'decrease');
        }
    }

    /**
     * @param string $prefix
     * @param string $key
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function delete(string $prefix, string $key) : void {
        $tk = $this->_make_key($prefix, $key);
        if (apcu_exists($tk) === true && apcu_delete($tk) === false) {
            throw $this->_exceptions_driver($tk, 'delete');
        }
    }

    /**
     * @return array
     */
    public function info() : array {
        // TODO: Implement info() method.

        return array('apcu' => array('info' => 'No Detailed Information about APCU.'));
    }

    private function _exceptions_miss(string $key) : \myConf\Exceptions\CacheMissException {
        return new \myConf\Exceptions\CacheMissException('CACHE_MISS', 'Key "' . $key . '" does not in the cache.');
    }

    private function _exceptions_driver(string $key, string $operation) : \myConf\Exceptions\CacheDriverException {
        return new \myConf\Exceptions\CacheDriverException('REDIS_DRIVER_ERROR', 'An error occurred when trying to ' . $operation . ' the key "' . $key . '".');
    }

    /**
     * @param string $prefix
     * @param string $key
     * @return string
     */
    private function _make_key(string $prefix, string $key) : string {
        return $prefix . $key;
    }
}