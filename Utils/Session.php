<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/26
     * Time: 17:06
     */

    namespace myConf\Utils;

    class Session {

        /**
         * @var \CI_Session $_session_object
         */
        private static $_session_object;

        /**
         * 初始化
         */
        public static function init() : void {
            $CI = &get_instance();
            $CI->load->library('session');
            self::$_session_object = $CI->session;
        }

        /**
         * @param string $key
         * @return mixed
         */
        public static function get_user_data(string $key) {
            return self::$_session_object->userdata($key);
        }

        /**
         * @param string $key
         * @param mixed $value
         */
        public static function set_user_data(string $key, $value) : void {
            self::$_session_object->set_userdata($key, $value);
        }

        /**
         * @param string $key
         */
        public static function unset_user_data(string $key) : void {
            self::$_session_object->unset_userdata($key);
        }

        /**
         * @param string $key
         * @return mixed
         */
        public static function get_temp_data(string $key) {
            return self::$_session_object->tempdata($key);
        }

        /**
         * @param string $key
         * @param $value
         * @param int $ttl
         */
        public static function set_temp_data(string $key, $value, int $ttl = 600) : void {
            self::$_session_object->set_tempdata($key, $value, $ttl);
        }

        /**
         * @param string $key
         */
        public static function unset_temp_data(string $key) : void {
            self::$_session_object->unset_tempdata($key);
        }

        /**
         * 清除session对象。
         */
        public static function destroy() : void {
            self::$_session_object->sess_destroy();
        }
    }
