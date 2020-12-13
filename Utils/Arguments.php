<?php


namespace myConf\Utils;

use myConf\BaseLayer;
use myConf\TopLayers;

/**
 * This class is used to exchange data between <strong>Controllers</strong> and <strong>Services</strong>.
 * It can also be used to get data from <strong>Http request</strong>.
 *
 * @package myConf\Utils
 * @see \CI_Input
 */
class Arguments
{
    /**
     * @var \CI_Controller
     */
    private static $CI;

    /**
     * Get data from $_GET or $_POST array which is sent by Http request.
     * @param string $keyName The argument's name in request Url(using Http Get method) or form (using Http Post method).
     * @param bool $isPost If $isPost is true, returns $_POST[$keyName]. Otherwise, returns $_GET[$keyName].
     *
     * @return null|mixed
     */
    public static function getHttpArg($keyName, $isPost = false) {
        $value = $isPost === false ? self::$CI->input->get($keyName) : self::$CI->input->post($keyName);
        return is_null($value) ? null : $value;
    }

    /**
     * Initializer of static class Arguments.
     */
    public static function init() {
        self::$CI = &get_instance();
    }

    /**
     * Add a function argument. This function should be used in Controllers to send data to Services.
     * @param $keyName
     * @param $value
     * @deprecated
     */
    public static function sendFuncArg($keyName, $value) {
        TopLayers::setGlobal($keyName, $value);
    }

    /**
     * Get an argument. This function should be used in Services to receive data from Controllers.
     * @param $keyName
     *
     * @return null|mixed
     * @deprecated
     */
    public static function getFuncArg($keyName) {
        return TopLayers::getGlobal($keyName);
    }
}