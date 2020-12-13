<?php


namespace myConf\Authenticators\Account;

/* Imports for error handler. */
use myConf\Errors as E;
use myConf\Errors\Authentications as E_AUTH;

/**
 * Authenticator class Login.
 *
 * @package myConf\Authenticators\Account
 */
class Login extends Account
{
    /**
     * Authenticator for login submit.
     */
    public static function authLogin()
    {
        /* If the current user has not logged in, operation login is allowed. */
        parent::checkLoggedOut();
    }
}