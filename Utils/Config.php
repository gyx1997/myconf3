<?php


namespace myConf\Utils;


class Config
{
    /**
     * @var \CI_Config
     */
    private static $ciConfigObject;

    public static function get(string $key ) {
        return self::$ciConfigObject->item($key);
    }

    public static function init() {
        $ciSuperObject = &get_instance();
        self::$ciConfigObject = $ciSuperObject->config;
        self::$ciConfigObject->load('email');
    }
}

Config::init();