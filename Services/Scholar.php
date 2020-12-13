<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/24
     * Time: 11:35
     */

    namespace myConf\Services;

    /**
     * Class Scholar
     * @package myConf\Services
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     */
    class Scholar extends \myConf\BaseService {

        /**
         * Scholar constructor.
         */
        public function __construct() {
            parent::__construct();
        }

        /**
         * @param string $email
         * @return array
         */
        public function get(string $email) : array {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $this->models()->Scholar->getByEmail($email);
        }

        /**
         * @param string $email
         * @return bool
         */
        public function exists(string $email) : bool {
            return $this->Models->Scholar->exist_by_email($email);
        }

        /**
         * @param string $email
         * @param string $first_name
         * @param string $last_name
         * @param string $chn_full_name
         * @param string $institute
         * @param string $department
         * @param string $address
         * @param string $prefix
         * @return int
         */
        public function add(string $email, string $first_name = '', string $last_name = '', string $chn_full_name = '', string $institute = '', string $department = '', string $address = '', string $prefix = '') : int {
            return $this->Models->Scholar->create_new($email, $first_name, $last_name, $chn_full_name, $address, $institute, $department, $prefix);
        }
    }