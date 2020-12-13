<?php


namespace myConf\Methods\Account;

use myConf\Utils\Session;

/**
 * Methods of logout operations.
 *
 * @package myConf\Methods\Account
 */
class Logout extends \myConf\BaseMethod
{
    /**
     * Logout constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * requestUrl : /account/logout/
     */
    public static function logout()
    {
        /* Destroy session data. */
        Session::destroy();
        self::retSuccess();
    }
}