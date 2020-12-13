<?php


namespace myConf;

/* Import error definitions. */
use myConf\Errors as Err;
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Errors\Authentications as E_AUTH;

/**
 * Top layers including controller layer and method (authenticator) layer.
 * Controller layers parse the request url and do authentication,
 * while method layers do the actual business logic.
 *
 * @package myConf
 */
class TopLayers extends BaseLayer
{
    /**
     * Generate return value (array) for each method.
     *
     * @param int    $httpCode
     * @param int    $statusCode
     * @param string $statusString
     * @param array  $data
     * @param bool   $dataOutside
     */
    protected static function retArray(int $httpCode, int $statusCode, string $statusString, array $data = array(),
                                       bool $dataOutside = false)
    {
        $dataWithVisibility = array();
        foreach ($data as $key => $value)
        {
            $dataWithVisibility[$key] = array(
                'type' => OUTPUT_VAR_ALL,
                'value' => $value,
                'outside' => $dataOutside
            );
        }
        $GLOBALS['myConf']['ret'] = array(
            'httpCode'   => $httpCode,
            'statusCode' => $statusCode,
            'status'     => $statusString,
            'data'       => $dataWithVisibility,
        );
    }

    /**
     * Generate return value (array) for each method when http errors occurred.
     * @param int    $httpCode Http status code. In this function, it should be 4xx or 5xx.
     * @param int    $statusCode Internal status code.
     * @param string $statusString Status string.
     * @param string $message Error message.
     */
    protected static function retError(int $httpCode, int $statusCode, string $statusString, string $message)
    {
        self::retArray(
            $httpCode,
            $statusCode,
            $statusString,
            array('description' => $message)
        );
    }

    /**
     * Generate return value (array) for each method with success result.
     * @param array $data
     */
    protected static function retSuccess(array $data = array(), bool $dataOutside = false) {
        self::retArray(200, 0, 'SUCCESS', $data, $dataOutside);
    }

    /**
     * Function for return data of methods, including error handler.
     * @param $data
     */
    protected static function return($data = array(), bool $dataOutside = false)
    {
        // Get error no.
        $errNo = Err::getLastError();
        if ($errNo > 0 || $data === false)
        {
            // $errNo > 0 means an error occurred, then get the error message.
            $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
            self::retArray(err::getLastErrorHttpCode(),
                           $errNo,
                           $errStr);
            return;
        }
        // No errors occurred, success returned.
        self::retSuccess($data, $dataOutside);
        return;
    }
}