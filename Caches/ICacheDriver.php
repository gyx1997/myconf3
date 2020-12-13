<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/22
     * Time: 15:45
     */

    namespace myConf\Caches;

    /**
     * Interface ICacheDriver
     * @package myConf\Caches
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     */
    interface ICacheDriver {
        /**
         * @param string $prefix
         * @param string $key
         * @return mixed
         * @throws \myConf\Exceptions\CacheMissException
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function get(string $prefix, string $key);

        /**
         * @param string $prefix
         * @param string $key
         * @param $value
         * @param int $ttl
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function set(string $prefix, string $key, $value, int $ttl = 0) : void;

        /**
         * @param string $prefix
         * @param string $key
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function increase(string $prefix, string $key) : void;

        /**
         * @param string $prefix
         * @param string $key
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function decrease(string $prefix, string $key) : void;

        /**
         * @param string $prefix
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function clear(string $prefix) : void;

        /**
         *
         */
        public function optimize() : void;

        /**
         *
         */
        public function info() : array;

        /**
         * @param string $prefix
         * @param string $key
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function delete(string $prefix, string $key) : void;
    }