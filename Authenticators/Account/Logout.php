<?php


namespace myConf\Authenticators\Account;

use myConf\Errors\Authentications as E_AUTH;

/**
 * Class Logout
 *
 * @package myConf\Authenticators\Account
 */
class Logout extends Account
{
    public static function authLogout()
    {
        /* Only if the current user has logged in, operation logout is allowed. */
        parent::checkLoggedIn();
    }
}