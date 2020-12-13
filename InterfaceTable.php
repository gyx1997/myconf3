<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/21
     * Time: 23:41
     */

    namespace myConf;

    interface InterfaceTable {
        public static function primary_key() : string;

        public static function table() : string;

        public static function fields() : array;
    }