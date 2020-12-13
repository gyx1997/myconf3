<?php


namespace myConf\Authenticators\Conference;

use myConf\Authenticators\Conference\Conference as ConferenceAuthenticator;
use myConf\Errors as Err;
use myConf\Errors\Authentications as E_AUTH;

use myConf\Models;
use myConf\Services;

class Management extends ConferenceAuthenticator
{
    public static function authenticate()
    {
        /* Call parent authenticator. */
        parent::authenticate();
    }
}