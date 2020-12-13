<?php


namespace myConf;

use myConf\Errors as Err;
use myConf\Errors\Authentications as E_AUTH;

/**
 * Parent class of all authenticators.
 *
 * @package myConf
 */
class BaseAuthenticator extends TopLayers
{
    private static $models;

    /**
     * @return Models
     */
    protected static function models()
    {
        if (is_null(self::$models))
        {
            $models = \myConf\Models::instance();
            self::$models = &$models;
        }
        return self::$models;
    }
    
    /**
     * @return bool
     */
    public static function authSuccess()
    {
        $errNo = Err::getLastError();
        return isset($errNo) && $errNo === 0;
    }

    /**
     *
     */
    protected static function checkLoggedIn()
    {
        /* Get global variable user_id. */
        $userId = self::getGlobal('user_id');
        /* Check user_id status. */
        if (isset($userId) === false)
        {
            Errors::setError(-1, 500,'INTERNAL_ERROR', 'Internal Server Error occurred during authenticate login.');
            return;
        }
        /* user_id > 0 means the current user has logged in. */
        if ($userId === 0)
        {
            Errors::setError(E_AUTH::E_USER_NOT_LOGIN, 200, E_AUTH::errorMessage[E_AUTH::E_USER_NOT_LOGIN], 'The current user has not logged in.');
            return;
        }
        return;
    }

    /**
     *
     */
    protected static function checkLoggedOut()
    {
        /* Get global variable user_id. */
        $userId = self::getGlobal('user_id');
        /* Check user_id status. */
        if (isset($userId) === false)
        {
            Errors::setError(-1, 500, 'INTERNAL_ERROR', 'Internal Server Error occurred during authenticate login.');
            return;
        }
        /* user_id > 0 means the current user has logged in. */
        if ($userId > 0)
        {
            Errors::setError(E_AUTH::E_USER_ALREADY_LOGIN, 200, E_AUTH::errorMessage[E_AUTH::E_USER_ALREADY_LOGIN], 'The current user has not logged in.');
            return;
        }
        return;
    }
}