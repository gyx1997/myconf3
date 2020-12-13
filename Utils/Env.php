<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/26
     * Time: 16:08
     */

    namespace myConf\Utils;

    class Env {
        /**
         * get the request ip
         * @return string
         */
        public static function get_ip() {
            $ip = '0.0.0.0';
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                return ip2long($_SERVER['HTTP_CLIENT_IP']) !== false ? $_SERVER['HTTP_CLIENT_IP'] : $ip;
            } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return ip2long($_SERVER['HTTP_X_FORWARDED_FOR']) !== false ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
            } else {
                return ip2long($_SERVER['REMOTE_ADDR']) !== false ? $_SERVER['REMOTE_ADDR'] : $ip;
            }
        }

        /**
         * 获取当前请求的完整URL
         * @return string
         */
        public static function get_current_url() : string {
            $sys_protocol = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
            $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
            $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
            $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
            return $sys_protocol . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
        }

        /**
         * @return string
         */
        public static function get_redirect() : string {
            $CI = &get_instance();
            $redirect = $CI->input->get('redirect');
            return isset($redirect) ? $redirect : '/';
        }
    }