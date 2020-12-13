<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/22
     * Time: 15:44
     */

    namespace myConf\Caches;

    use Redis;

    class RedisDriver implements ICacheDriver {

        /**
         * @var Redis Redis实例对象
         */
        private $_redis_object;

        private static $_instance = null;

        /**
         * 返回一个redis驱动器实例
         * @return RedisDriver
         */
        public static function instance() : RedisDriver {
            if (self::$_instance === null) {
                self::$_instance = new RedisDriver();
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
         * RedisDriver constructor.
         * 禁止外部调用构造函数。
         */
        private function __construct() {
            $this->_redis_object = new Redis();
            //要求安装本地redis服务器
            $this->_redis_object->pconnect('127.0.0.1');
        }

        /**
         * @param string $prefix
         * @param string $key
         * @param $value
         * @param int $ttl 生存时间。ttl为0时为永久保存（内存不足时仍会被redis踢出）
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function set(string $prefix, string $key, $value, int $ttl = 0) : void {
            //组装新key
            $tk = $this->_make_key($prefix, $key);
            $data = serialize($value);
            $result = ($ttl === 0 ? $this->_redis_object->set($tk, $data) : $this->_redis_object->setex($tk, $ttl, $data));
            if ($result === false) {
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
            if (!$this->_redis_object->exists($tk)) {
                throw $this->_exceptions_miss($tk);
            }
            $result = $this->_redis_object->get($tk);
            if ($result === false) {
                throw $this->_exceptions_driver($tk, 'get');
            }
            return unserialize($result);
        }

        /**
         * @param string $prefix
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function clear(string $prefix) : void {
            if ($this->_redis_object->flushAll() === false) {
                throw $this->_exceptions_driver('', 'clear');
            }
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
            if ($this->_redis_object->incr($tk) === false) {
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
            if ($this->_redis_object->decr($tk) === false) {
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
            if ($this->_redis_object->delete($tk) === false) {
                throw $this->_exceptions_driver($tk, 'delete');
            }
        }

        /**
         * @return array
         */
        public function info() : array {
            // TODO: Implement info() method.
            return array('redis' => $this->_redis_object->info());
        }

        private function _exceptions_miss(string $key) : \myConf\Exceptions\CacheMissException {
            return new \myConf\Exceptions\CacheMissException('CACHE_MISS', 'Key "' . $key . '" does not in the cache.');
        }

        private function _exceptions_driver(string $key, string $operation) : \myConf\Exceptions\CacheDriverException {
            return new \myConf\Exceptions\CacheDriverException('REDIS_DRIVER_ERROR', 'An error occurred when trying to ' . $operation . ' the key "' . $key . '". Message : ' . $this->_redis_object->getLastError());
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