<?php

namespace myConf;
/**
 * Provides common methods for each operation layer, including Controller, Method (Authenticator), Service, Model,
 * Table.
 */
class BaseLayer
{
    /**
     * @var array Return values for top layers.
     */
    protected static $retVal = array();

    /**
     * @param string $key
     * @param        $value
     */
    public static function setGlobal(string $key, $value) {
        $GLOBALS['myConf']['globals'][$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public static function getGlobal(string $key) {
        return $GLOBALS['myConf']['globals'][$key];
    }
}