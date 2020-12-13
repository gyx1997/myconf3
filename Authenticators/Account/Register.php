<?php


namespace myConf\Authenticators\Account;


class Register extends Account
{
    public static function authRegister()
    {
        /* The user can only register an account when he/she is not logged in. */
        parent::checkLoggedOut();
    }
}