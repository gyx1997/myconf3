<?php


namespace myConf\Authenticators\Account;


class Settings extends Account
{
    public static function authMySettings()
    {
        /* The use can see and change his/her settings only if he/she has logged in.*/
        parent::checkLoggedIn();
    }
}