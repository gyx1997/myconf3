<?php


namespace myConf\Errors;


class Authentications
{



    /**
     * Represents that the user has already logged in.
     */
    public const E_USER_ALREADY_LOGIN = 0x20000001;
    /**
     * Represents that the user has not logged in.
     */
    public const E_USER_NOT_LOGIN = 0x20000002;

    /**
     * Represents that the user has not registered in the conference.
     */
    public const E_USER_NOT_REGISTER_IN_CONFERENCE = 0x20000003;

    public const E_USER_NO_PERMISSION = 0x20000004;

    /**
     * Error messages.
     */
    public const errorMessage = array(
        self::E_USER_ALREADY_LOGIN => 'ALREADY_LOGIN',
        self::E_USER_NOT_LOGIN => 'NOT_LOGIN',
        self::E_USER_NOT_REGISTER_IN_CONFERENCE => 'NOT_IN_CONFERENCE',
        self::E_USER_NO_PERMISSION => 'ACTION_NO_PERMISSION',
    );
}