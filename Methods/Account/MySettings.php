<?php


namespace myConf\Methods\Account;

use myConf\BaseMethod;
use myConf\Errors as Err;
use myConf\Errors\Services\Services as E_SERVICE;
use myConf\Exceptions\AvatarNotSelectedException;
use myConf\Exceptions\FileUploadException;
use myConf\Services;
use myConf\Utils\Arguments;
use myConf\Utils\Avatar;

/**
 * Methods of /account/my-settings/
 *
 * @package myConf\Methods\Account
 */
class MySettings extends BaseMethod
{
    /**
     * MySettings constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @requestUrl /account/my-settings/
     */
    public static function showSettings()
    {
        /* Get the current user's identifier. Since it is an
           internal variable, no checks will be done here. In other words,
           we always think it is valid, though there may be incorrectness
           due to cache or session failure. */
        $currentUserId = self::getGlobal('user_id');
        /* Get user's setting. */
        $userSettings = Services::Accounts()->getSettings($currentUserId);
        if ($userSettings !== false)
        {
            /* If no error occurred, return data. */
            self::retSuccess(array(
                    'user_name'    => $userSettings['user_name'],
                    'email'        => $userSettings['user_email'],
                    'avatar'       => $userSettings['user_avatar'],
                    'scholar_info' => $userSettings['user_scholar_data'],
                ));
            return;
        }
        /* Error handler. */
        $errHttpCode = Err::getLastErrorHttpCode();
        $errNo = Err::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        self::retArray($errHttpCode, $errNo, $errStr);
        return;
    }

    /**
     * @requestUrl /account/my-settings/scholar/?do=submit
     */
    public static function updateScholarData()
    {
        $email = Arguments::getHttpArg('scholarEmail', true);
        $first_name = Arguments::getHttpArg('scholarFirstName', true);
        $last_name = Arguments::getHttpArg('scholarLastName', true);
        $institution = Arguments::getHttpArg('scholarInstitution', true);
        $department = Arguments::getHttpArg('scholarDepartment', true);
        $address = Arguments::getHttpArg('scholarAddress', true);
        Services::Accounts()->update_scholar_info($email, $first_name, $last_name, $institution, $department, $address);
    }

    /**
     * @requestUrl /account/my-settings/avatar/?do=submit
     */
    public static function updateAvatar()
    {
        $userId = self::getGlobal('user_id');
        /* Update user's avatar. */
        Services::Accounts()->changeAvatar($userId, 'avatar_image');
        /* Error handler. */
        $errHttpCode = Err::getLastErrorHttpCode();
        $errNo = Err::getLastError();
        $errStr = isset(E_SERVICE::errorMessage[$errNo]) ? E_SERVICE::errorMessage[$errNo] : 'UNKNOWN_ERROR';
        self::retArray($errHttpCode, $errNo, $errStr);
        return;
    }
}