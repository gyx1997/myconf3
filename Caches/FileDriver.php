<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/17
     * Time: 13:38
     */

    namespace myConf\Caches;

    use myConf\Exceptions\CacheDriverException;
    use myConf\Utils\File;

    /**
     * Class Cache
     * @package myConf
     */
    class FileDriver implements \myConf\Caches\ICacheDriver {
        const CACHE_DIR = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        private $_prefix;

        private static $_instance = null;

        /**
         * 返回一个redis驱动器实例
         * @return \myConf\Caches\RedisDriver
         */
        public static function instance() : FileDriver {
            if (self::$_instance === null) {
                self::$_instance = new FileDriver();
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
         * Cache constructor.
         * Use native php file cache.
         * @param string $prefix
         */
        private function __construct() {

        }

        /**
         * Cache constructor.
         * Use native php file cache.
         * @param string $prefix
         */
        public function __construct(string $prefix = 'def') {
            $this->_prefix = substr(md5($prefix), 0, 12);
        }

        /**
         * @param string $key
         * @return mixed
         * @throws CacheDriverException
         * @throws \myConf\Exceptions\CacheMissException
         */
        public function get(string $key) {
            $filename = $this->prepare_path() . DIRECTORY_SEPARATOR . $this->prepare_key($key) . '.cache.php';
            if (!file_exists($filename)) {
                throw self::_exception_cache_miss($key);
            }
            try {
                include($filename);
                if (!isset($c) || intval($c['expires']) < time()) {
                    //自动回收过期缓存
                    @unlink($filename);
                    @rmdir($this->prepare_path());
                    throw self::_exception_cache_miss($key);
                }
                return $c['data'];
            } catch (\Error $e) {
                throw new \myConf\Exceptions\CacheDriverException('CACHE_FILE_CORRUPTED', 'Cache file with specified key has been corrupted.');
            }
        }

        /**
         * @param string $key
         * @param $data
         * @param int $ttl
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function set(string $key, $data, int $ttl = 0) : void {
            if (is_object($data)) {
                throw new \myConf\Exceptions\CacheDriverException('UNSUPPORT_TYPE', 'Trying to store an object into the cache.');
            }
            $path = $this->prepare_path();
            if (!is_dir($path) && !@mkdir($path, 0777, true)) {
                throw new \myConf\Exceptions\CacheDriverException('FILE_IO_ERROR', 'Trying to make cache directory "' . $path . '" but failed');
            }
            $ttl === 0 ? $expires = 0 : $expires = time() + $ttl;
            $cache_str = '<?php $c = ' . var_export(array('data' => $data, 'expires' => $expires), true) . ';';
            file_put_contents($path . DIRECTORY_SEPARATOR . $this->prepare_key($key) . '.cache.php', $cache_str);
            return;
        }

        /**
         * @param string $key
         */
        public function delete(string $key) : void {
            @unlink($this->prepare_path() . DIRECTORY_SEPARATOR . $this->prepare_key($key) . '.cache.php');
        }

        /**
         * @return string
         */
        private function prepare_path() : string {
            return self::CACHE_DIR . $this->_prefix;
        }

        /**
         * @param string $key
         * @return string
         */
        private function prepare_key(string $key) : string {
            return md5($this->_prefix . $key);
        }

        /**
         *
         */
        public function clear() : void {
            self::_deldir(self::CACHE_DIR);
        }

        /**
         * @throws CacheDriverException
         */
        public function optimize() : void {
            $dir_handler_base = opendir(self::CACHE_DIR);
            while ($file = readdir($dir_handler_base)) {
                if ($file !== '.' && $file !== '..') {
                    $full_path = self::CACHE_DIR . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($full_path)) {
                        $dir_handler_sub = opendir($full_path);
                        while ($f = readdir($dir_handler_sub)) {
                            if ($f !== '.' && $f !== '..') {
                                $cache_file = $full_path . DIRECTORY_SEPARATOR . $f;
                                self::has_expired($f) && @unlink($file);
                            }
                        }
                        closedir($dir_handler_sub);
                        @rmdir($full_path);
                    }
                }
            }
            closedir($dir_handler_base);
        }

        private static function _deldir($dir) {
            //先删除目录下的文件：
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        self::_deldir($fullpath);

                    }
                }
            }
            closedir($dh);
            $dir !== self::CACHE_DIR && @rmdir($dir);
        }

        /**
         * @param string $cache_file
         * @return bool
         * @throws CacheDriverException
         */
        private static function has_expired(string $cache_file) : bool {
            try {
                include $cache_file;
                return !(isset($c) && isset($c['expires']) && $c['expires'] < time());
            } catch (\Error $e) {
                throw self::_exception_file_corrupted();
            }
        }

        /**
         * @return CacheDriverException
         */
        private static function _exception_file_corrupted() : \myConf\Exceptions\CacheDriverException {
            return new \myConf\Exceptions\CacheDriverException('CACHE_FILE_CORRUPTED', 'Cache file with specified key has been corrupted.');
        }

        /**
         * @param string $key
         * @return CacheDriverException
         */
        private static function _exception_cache_miss(string $key) : \myConf\Exceptions\CacheMissException {
            return new \myConf\Exceptions\CacheMissException('CACHE_MISS', 'Cache miss when trying to get cache data with key "' . $key . '"');
        }

    }