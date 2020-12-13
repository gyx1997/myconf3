<?php

namespace myConf;

/**
 * Provides error number constants.
 *
 * @package myConf
 */
class Errors
{
    /**
     * Get the last error occurred in current layer.
     * @return int returns error no. If no error occurs in the current layer, returns 0;
     */
    public static function getLastError() {
        return $GLOBALS['myConf']['err']['internal_code'];
    }

    /**
     * @return int
     */
    public static function getLastErrorHttpCode () {
        return $GLOBALS['myConf']['err']['http_code'];
    }

    /**
     * Set an error in current layer.
     *
     * @param int    $errorNo  The error no.
     * @param int    $httpCode The http code assigned with the error.
     * @param string $shortMessage The error's short message.
     * @param string $description The error's description.
     */
    public static function setError(int $errorNo, int $httpCode = 500, string $shortMessage = 'UNKNOWN_ERROR', string
    $description = 'Unknown internal server error occurred.') {
        $GLOBALS['myConf']['err']['internal_code'] = $errorNo;
        $GLOBALS['myConf']['err']['http_code'] = $httpCode;
        $GLOBALS['myConf']['err']['status_str'] = $shortMessage;
        $GLOBALS['myConf']['err']['description'] = $description;
    }

    public static function clearError() {
        $GLOBALS['myConf']['err'] = array(
            'internal_code' => 0,
            'http_code' => 200,
            'status_str' => 'SUCCESS',
            'description' => '',
        );
    }
}